<?php

use App\Models\FailedJob;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Failed Jobs')] class extends Component {
    use WithPagination;

    public $search = '';
    public $selectedQueue = 'all';
    public $perPage = 15;
    public $selectedJobId = null;
    public $showExceptionModal = false;
    public $exceptionDetails = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedQueue()
    {
        $this->resetPage();
    }

    public function getQueuesProperty()
    {
        return FailedJob::query()
            ->select('queue')
            ->distinct()
            ->orderBy('queue')
            ->pluck('queue')
            ->toArray();
    }

    #[Transition]
    public function retryJob($jobId)
    {
        $job = FailedJob::find($jobId);

        if ($job) {
            try {
                Artisan::call('queue:retry', ['id' => $job->uuid]);
                $job->delete();

                $this->dispatch('success', message: 'Job successfully retried and moved back to queue.');
            } catch (\Exception $e) {
                $this->dispatch('error', message: 'Failed to retry job: ' . $e->getMessage());
            }
        }
    }

    #[Transition]
    public function deleteJob($jobId)
    {
        $job = FailedJob::find($jobId);

        if ($job) {
            try {
                Artisan::call('queue:forget', ['id' => $job->uuid]);
                $job->delete();

                $this->dispatch('success', message: 'Job successfully deleted.');
            } catch (\Exception $e) {
                $this->dispatch('error', message: 'Failed to delete job: ' . $e->getMessage());
            }
        }
    }

    #[Transition]
    public function retryAll()
    {
        try {
            $count = FailedJob::count();

            if ($count > 0) {
                Artisan::call('queue:retry', ['id' => 'all']);
                FailedJob::query()->delete();

                $this->dispatch('success', message: "{$count} jobs successfully retried.");
            }
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to retry all jobs: ' . $e->getMessage());
        }
    }

    #[Transition]
    public function flushAll()
    {
        try {
            $count = FailedJob::count();

            if ($count > 0) {
                Artisan::call('queue:flush');

                $this->dispatch('success', message: "{$count} jobs successfully flushed.");
            }
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to flush all jobs: ' . $e->getMessage());
        }
    }

    public function showException($jobId)
    {
        $job = FailedJob::find($jobId);

        if ($job) {
            $this->selectedJobId = $jobId;
            $this->exceptionDetails = $job->exception;
            $this->showExceptionModal = true;
        }
    }

    public function closeModal()
    {
        $this->showExceptionModal = false;
        $this->exceptionDetails = '';
        $this->selectedJobId = null;
    }

    #[On('refresh-failed-jobs')]
    public function refreshJobs()
    {
        // Triggers re-render
    }
}; ?>

<div wire:transition="content" class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Failed Jobs</flux:heading>
            <flux:subheading>Monitor and manage jobs that failed to execute.</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if(FailedJob::count() > 0)
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="arrow-path"
                    wire:click="retryAll"
                    wire:confirm="Are you sure you want to retry all failed jobs?"
                >Retry All</flux:button>
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="trash"
                    class="text-red-500"
                    wire:click="flushAll"
                    wire:confirm="Are you sure you want to flush all failed jobs? This cannot be undone."
                >Flush All</flux:button>
            @endif
            <flux:button
                size="sm"
                variant="ghost"
                icon="arrow-path"
                wire:click="$refresh"
            />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search job name or exception..."
                clearable
            />
        </div>
        <div>
            <flux:select wire:model.live="selectedQueue" icon="queue-list">
                <flux:select.option value="all">All Queues</flux:select.option>
                @foreach($this->queues as $queue)
                    <flux:select.option :value="$queue">{{ $queue }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Job</flux:table.column>
            <flux:table.column>Queue</flux:table.column>
            <flux:table.column>Failed At</flux:table.column>
            <flux:table.column>Exception</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @php
                $query = FailedJob::query()->orderBy('failed_at', 'desc');
                if ($selectedQueue !== 'all') $query->where('queue', $selectedQueue);
                if ($this->search) {
                    $query->where(function ($q) {
                        $q->where('payload', 'like', '%' . $this->search . '%')
                          ->orWhere('exception', 'like', '%' . $this->search . '%');
                    });
                }
                $jobs = $query->paginate($perPage);
            @endphp

            @foreach($jobs as $job)
                <flux:table.row :key="$job->id">
                    <flux:table.cell>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $job->job_name }}</div>
                        <div class="text-xs text-zinc-500 font-mono">{{ $job->uuid }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" variant="outline" inset="top bottom">{{ $job->queue }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        <div class="text-sm">{{ $job->failed_at->format('d M Y, H:i') }}</div>
                        <div class="text-xs text-zinc-400">({{ $job->failed_at->diffForHumans() }})</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="max-w-md truncate text-sm text-red-500" title="{{ $job->exception_message }}">
                            {{ $job->exception_message }}
                        </div>
                        <flux:link wire:click="showException({{ $job->id }})" class="text-xs cursor-pointer">View full</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button.group>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="arrow-path"
                                wire:click="retryJob({{ $job->id }})"
                                title="Retry"
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

    {{-- Exception Modal --}}
    <flux:modal name="exception-modal" wire:model.self="showExceptionModal" class="min-w-[40rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Exception Details</flux:heading>
                <flux:text class="mt-2 text-sm text-zinc-500">Full stack trace and error message for the failed job.</flux:text>
            </div>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg overflow-x-auto border border-zinc-200 dark:border-zinc-700">
                <pre class="text-xs font-mono whitespace-pre-wrap dark:text-zinc-300">{{ $exceptionDetails }}</pre>
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" wire:click="closeModal">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
