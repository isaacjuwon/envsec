<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\QueueLog;
use App\Models\User;
use App\Notifications\ImportCompleted; // We can rename this or create ExportCompleted
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ExportSecrets implements ShouldQueue
{
    use Queueable;

    protected QueueLog $queueLog;

    /**
     * Create a new job instance.
     */
    public function __construct(QueueLog $queueLog)
    {
        $this->queueLog = $queueLog;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->queueLog->update(['status' => 'processing', 'started_at' => now()]);

        $projectId = $this->queueLog->data['project_id'] ?? null;
        $userId = $this->queueLog->data['user_id'] ?? null;

        if (!$projectId) {
            $this->failJob("Project ID not found.");
            return;
        }

        $project = Project::find($projectId);
        if (!$project) {
            $this->failJob("Project not found.");
            return;
        }

        try {
            $filename = 'exports/secrets-' . $project->slug . '-' . now()->format('Y-m-d-His') . '.csv';
            $fullPath = storage_path('app/public/' . $filename);

            // Ensure directory exists
            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            $writer = SimpleExcelWriter::create($fullPath);

            $secrets = $project->secrets()->with('values')->get();

            foreach ($secrets as $secret) {
                $row = [
                    'Key' => $secret->key,
                    'Description' => $secret->description,
                ];

                foreach (['Development', 'Staging', 'Production'] as $env) {
                    $value = $secret->values->firstWhere('environment', $env);
                    $row[$env] = $value ? $value->value : '';
                }

                $writer->addRow($row);
            }

            $writer->close();

            $this->queueLog->update([
                'status' => 'completed',
                'finished_at' => now(),
                'message' => "Export completed. File: " . $filename,
                'data' => array_merge($this->queueLog->data, ['file' => $filename, 'download_url' => Storage::url($filename)])
            ]);

            $user = User::find($userId);
            if ($user) {
                // Reuse ImportCompleted for now or create generic JobCompleted notification
                $user->notify(new ImportCompleted(
                    'Secrets Export',
                    Storage::url($filename),
                    $secrets->count(),
                    []
                ));
            }

        } catch (\Exception $e) {
            $this->failJob($e->getMessage());
        }
    }

    protected function failJob(string $message): void
    {
        $this->queueLog->update([
            'status' => 'failed',
            'finished_at' => now(),
            'message' => $message
        ]);
    }
}
