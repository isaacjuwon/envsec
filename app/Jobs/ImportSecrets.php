<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\QueueLog;
use App\Models\Secret;
use App\Models\SecretValue;
use App\Models\User;
use App\Notifications\ImportCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class ImportSecrets implements ShouldQueue
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

        $file = $this->queueLog->data['file'] ?? null;
        $projectId = $this->queueLog->data['project_id'] ?? null;
        $userId = $this->queueLog->data['user_id'] ?? null;

        if (!$file || !file_exists($file) || !$projectId) {
            $this->failJob("Required data or file not found.");
            return;
        }

        $project = Project::find($projectId);
        if (!$project) {
            $this->failJob("Project not found.");
            return;
        }

        try {
            $rows = SimpleExcelReader::create($file)->getRows();
            $imported = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                try {
                    $key = $row['key'] ?? $row['Key'] ?? null;
                    if (!$key) {
                        throw new \Exception("Key is missing in row " . ($index + 1));
                    }

                    DB::transaction(function () use ($project, $key, $row, &$imported) {
                        $secret = $project->secrets()->updateOrCreate(
                            ['key' => $key],
                            ['description' => $row['description'] ?? $row['Description'] ?? null]
                        );

                        // Expected columns: Development, Staging, Production
                        $environments = ['Development', 'Staging', 'Production'];
                        foreach ($environments as $env) {
                            $val = $row[strtolower($env)] ?? $row[$env] ?? null;
                            if ($val !== null) {
                                $secret->values()->updateOrCreate(
                                    ['environment' => $env],
                                    ['value' => $val]
                                );
                            }
                        }
                        $imported++;
                    });
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            $this->queueLog->update([
                'status' => 'completed',
                'finished_at' => now(),
                'message' => count($errors) > 0 ? implode("\n", $errors) : "Successfully imported {$imported} secrets."
            ]);

            $user = User::find($userId);
            if ($user) {
                $user->notify(new ImportCompleted(
                    'Secrets',
                    route('projects.secrets.index', $project->id),
                    $imported,
                    $errors
                ));
            }

        } catch (\Exception $e) {
            $this->failJob($e->getMessage());
        } finally {
            if (file_exists($file)) {
                @unlink($file);
            }
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
