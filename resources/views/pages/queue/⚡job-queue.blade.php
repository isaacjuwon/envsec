<?php

use App\Models\QueueLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Job History')] class extends Component {
    use WithPagination;

    public $statusFilter = 'all';
    public $search = '';
    public $perPage = 15;
    public $dateRange = 'all';
    public $selectedJobId = null;
    public $showDetailModal = false;
    public $jobDetail = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingDateRange()
    {
        $this->resetPage();
    }

    public function getStatsProperty()
    {
        return [
            'total' => QueueLog::count(),
            'today' => QueueLog::today()->count(),
            'completed' => QueueLog::completed()->count(),
            'failed' => QueueLog::failed()->count(),
            'processing' => QueueLog::processing()->count(),
            'pending' => QueueLog::pending()->count(),
            'avg_duration' => $this->getAverageDuration(),
        ];
    }

    protected function getAverageDuration()
    {
        $completedJobs = QueueLog::completed()
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->get();

        if ($completedJobs->isEmpty()) {
            return '0s';
        }

        $totalSeconds = $completedJobs->sum(function ($job) {
            return $job->duration_in_seconds ?? 0;
        });

        $avg = $totalSeconds / $completedJobs->count();

        if ($avg < 60) {
            return round($avg, 1) . 's';
        } elseif ($avg < 3600) {
            return round($avg / 60, 1) . 'm';
        }
        return round($avg / 3600, 1) . 'h';
    }

    public function showJobDetail($jobId)
    {
        $this->jobDetail = QueueLog::find($jobId);
        $this->selectedJobId = $jobId;
        $this->showDetailModal = true;
    }

    public function closeModal()
    {
        $this->showDetailModal = false;
        $this->jobDetail = null;
        $this->selectedJobId = null;
    }

    #[Transition]
    public function deleteJob($jobId)
    {
        $job = QueueLog::find($jobId);

        if ($job) {
            $job->delete();
            $this->dispatch('success', message: 'Job log successfully deleted.');
        }
    }

    #[Transition]
    public function clearCompletedLogs()
    {
        $count = QueueLog::completed()->count();
        QueueLog::completed()->delete();
        $this->dispatch('success', message: "{$count} completed logs cleared.");
    }

    #[Transition]
    public function clearAllLogs()
    {
        $count = QueueLog::count();
        QueueLog::truncate();
        $this->dispatch('success', message: "Job history successfully cleared.");
    }

    #[On('refresh-jobs')]
    public function refreshJobs()
    {
        // Triggers re-render
    }
}; ?>

<div wire:transition="content" class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Job History</flux:heading>
            <flux:subheading>History of all background jobs and their execution status.</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if(QueueLog::count() > 0)
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="trash"
                    wire:click="clearCompletedLogs"
                    wire:confirm="Clear all completed logs?"
                >Clear Completed</flux:button>
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="fire"
                    class="text-red-500"
                    wire:click="clearAllLogs"
                    wire:confirm="Clear ALL job history? This cannot be undone."
                >Clear All History</flux:button>
            @endif
            <flux:button
                size="sm"
                variant="ghost"
                icon="arrow-path"
                wire:click="$refresh"
            />
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        @php $stats = $this->stats; @endphp
        
        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <div class="text-zinc-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Jobs</div>
            <div class="text-2xl font-bold dark:text-white">{{ $stats['total'] }}</div>
        </div>
        
        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <div class="text-zinc-500 text-xs font-semibold uppercase tracking-wider mb-1">Completed</div>
            <div class="text-2xl font-bold text-green-500">{{ $stats['completed'] }}</div>
        </div>

        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <div class="text-zinc-500 text-xs font-semibold uppercase tracking-wider mb-1">Failed</div>
            <div class="text-2xl font-bold text-red-500">{{ $stats['failed'] }}</div>
        </div>

        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <div class="text-zinc-500 text-xs font-semibold uppercase tracking-wider mb-1">Processing</div>
            <div class="text-2xl font-bold text-blue-500">{{ $stats['processing'] }}</div>
        </div>

        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <div class="text-zinc-500 text-xs font-semibold uppercase tracking-wider mb-1">Avg Duration</div>
            <div class="text-2xl font-bold text-zinc-700 dark:text-zinc-300">{{ $stats['avg_duration'] }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div class="md:col-span-6">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search job name or ID..."
                clearable
            />
        </div>
        <div class="md:col-span-3">
            <flux:select wire:model.live="statusFilter" icon="funnel">
                <flux:select.option value="all">All Status</flux:select.option>
                <flux:select.option value="pending">Pending</flux:select.option>
                <flux:select.option value="processing">Processing</flux:select.option>
                <flux:select.option value="completed">Completed</flux:select.option>
                <flux:select.option value="failed">Failed</flux:select.option>
            </flux:select>
        </div>
        <div class="md:col-span-3">
            <flux:select wire:model.live="dateRange" icon="calendar">
                <flux:select.option value="all">All Time</flux:select.option>
                <flux:select.option value="today">Today</flux:select.option>
                <flux:select.option value="yesterday">Yesterday</flux:select.option>
                <flux:select.option value="week">This Week</flux:select.option>
                <flux:select.option value="month">This Month</flux:select.option>
            </flux:select>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Job</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Started</flux:table.column>
            <flux:table.column>Finished</flux:table.column>
            <flux:table.column>Duration</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @php
                $query = QueueLog::query()->orderBy('created_at', 'desc');
                if ($statusFilter !== 'all') $query->where('status', $statusFilter);
                
                switch ($dateRange) {
                    case 'today': $query->whereDate('created_at', today()); break;
                    case 'yesterday': $query->whereDate('created_at', today()->subDay()); break;
                    case 'week': $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]); break;
                    case 'month': $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year); break;
                }

                if ($this->search) {
                    $query->where(function ($q) {
                        $q->where('job_name', 'like', '%' . $this->search . '%')
                          ->orWhere('job_id', 'like', '%' . $this->search . '%');
                    });
                }
                $jobs = $query->paginate($perPage);
            @endphp

            @foreach($jobs as $job)
                <flux:table.row :key="$job->id">
                    <flux:table.cell>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $job->short_job_name }}</div>
                        <div class="text-xs text-zinc-500 truncate max-w-xs" title="{{ $job->job_name }}">{{ $job->job_name }}</div>
                        @if($job->job_id)
                            <div class="text-[10px] text-zinc-400 font-mono">ID: {{ $job->job_id }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($job->status) {
                            'completed'  => 'green',
                            'failed'     => 'red',
                            'processing' => 'blue',
                            'pending'    => 'yellow',
                            default      => 'zinc',
                        }" inset="top bottom">{{ ucfirst($job->status) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        @if($job->started_at)
                            <div class="text-sm font-medium">{{ $job->started_at->format('d M') }}</div>
                            <div class="text-xs text-zinc-400">{{ $job->started_at->format('H:i:s') }}</div>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        @if($job->finished_at)
                            <div class="text-sm font-medium">{{ $job->finished_at->format('d M') }}</div>
                            <div class="text-xs text-zinc-400">{{ $job->finished_at->format('H:i:s') }}</div>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($job->duration)
                            <span class="font-mono text-sm {{ $job->duration_in_seconds > 60 ? 'text-zinc-500' : 'text-zinc-700 dark:text-zinc-300' }}">{{ $job->duration }}</span>
                        @elseif($job->status === 'processing')
                            <flux:icon icon="arrow-path" class="size-4 animate-spin text-blue-500" />
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button.group>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="eye"
                                wire:click="showJobDetail({{ $job->id }})"
                                title="Details"
                                inset="top bottom"
                            />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                class="text-red-500"
                                wire:click="deleteJob({{ $job->id }})"
                                title="Delete"
                                inset="top bottom"
                            />
                        </flux:button.group>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $jobs->links() }}
    </div>

    {{-- Detail Modal --}}
    <flux:modal name="job-detail-modal" wire:model.self="showDetailModal" class="min-w-[45rem]">
        @if($jobDetail)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Job Details</flux:heading>
                    <flux:text class="mt-2">{{ $jobDetail->short_job_name }}</flux:text>
                </div>

                <div class="grid grid-cols-3 gap-4 border-y border-zinc-100 dark:border-zinc-800 py-4">
                    <div>
                        <flux:label>Status</flux:label>
                        <flux:badge size="sm" :color="match($jobDetail->status) {
                            'completed'  => 'green',
                            'failed'     => 'red',
                            'processing' => 'blue',
                            'pending'    => 'yellow',
                            default      => 'zinc',
                        }" inset="top bottom">{{ ucfirst($jobDetail->status) }}</flux:badge>
                    </div>
                    <div>
                        <flux:label>Started</flux:label>
                        <div class="text-sm font-mono">{{ $jobDetail->started_at?->format('Y-m-d H:i:s') ?? '—' }}</div>
                    </div>
                    <div>
                        <flux:label>Duration</flux:label>
                        <div class="text-sm font-mono font-bold">{{ $jobDetail->duration ?? '—' }}</div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <flux:label>Full Class Name</flux:label>
                        <div class="p-2 bg-zinc-50 dark:bg-zinc-800 rounded font-mono text-xs break-all border border-zinc-200 dark:border-zinc-700">{{ $jobDetail->job_name }}</div>
                    </div>

                    @if($jobDetail->job_id)
                        <div>
                            <flux:label>Laravel Job ID</flux:label>
                            <div class="p-2 bg-zinc-50 dark:bg-zinc-800 rounded font-mono text-xs border border-zinc-200 dark:border-zinc-700">{{ $jobDetail->job_id }}</div>
                        </div>
                    @endif

                    @if($jobDetail->payload)
                        <div>
                            <flux:label>Payload</flux:label>
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg overflow-x-auto border border-zinc-200 dark:border-zinc-700 max-h-64">
                                <pre class="text-xs font-mono dark:text-zinc-300">{{ json_encode($jobDetail->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>
                    @endif

                    @if($jobDetail->message)
                        <div>
                            <flux:label>Result / Error Message</flux:label>
                            <div @class([
                                'p-4 rounded-lg overflow-x-auto border max-h-64',
                                'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-900/30' => $jobDetail->status === 'failed',
                                'bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700' => $jobDetail->status !== 'failed'
                            ])>
                                <pre @class([
                                    'text-xs font-mono whitespace-pre-wrap',
                                    'text-red-700 dark:text-red-400' => $jobDetail->status === 'failed',
                                    'dark:text-zinc-300' => $jobDetail->status !== 'failed'
                                ])>{{ $jobDetail->message }}</pre>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost" wire:click="closeModal">Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
