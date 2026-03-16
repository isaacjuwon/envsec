<?php

use App\Models\Workspace;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Transition;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Projects')] class extends Component {

    public string $search = '';

    #[Defer]
    public function getProjectsProperty()
    {
        $workspace = Workspace::current();
        return $workspace
            ? $workspace->projects()
                ->withCount(['members', 'secrets'])
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->latest()
                ->get()
            : collect();
    }
}; ?>

    <div wire:transition="content" class="flex h-full w-full flex-1 flex-col">

        <x-page-toolbar
            title="Projects"
            add-label="New Project"
            add-href="{{ route('projects.create') }}"
            :show-search="true"
            search="search"
        />

        @php $projects = $this->projects; @endphp

        {{-- Empty state --}}
        @if ($projects->isEmpty())
            <div class="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700 py-20 text-center">
                <flux:icon icon="folder-open" class="size-10 text-zinc-400" />
                <div>
                    <flux:heading>No projects yet</flux:heading>
                    <flux:subheading>Create your first project to get started.</flux:subheading>
                </div>
                <flux:button variant="primary" icon="plus" href="{{ route('projects.create') }}" wire:navigate>
                    New Project
                </flux:button>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                    <a
                        href="{{ route('projects.edit', $project->id) }}"
                        wire:navigate
                        wire:key="proj-{{ $project->id }}"
                        class="group flex flex-col gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5 hover:border-zinc-300 dark:hover:border-zinc-600 transition"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span class="font-semibold text-zinc-800 dark:text-zinc-100 group-hover:underline truncate">
                                {{ $project->name }}
                            </span>
                            <flux:icon icon="chevron-right" class="size-4 text-zinc-400 shrink-0 mt-0.5" />
                        </div>

                        @if ($project->description)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $project->description }}</p>
                        @endif

                        <div class="mt-auto flex items-center gap-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 text-xs text-zinc-400">
                            <span class="flex items-center gap-1">
                                <flux:icon icon="users" class="size-3.5" />
                                {{ $project->members_count }} member(s)
                            </span>
                            <span class="flex items-center gap-1">
                                <flux:icon icon="key" class="size-3.5" />
                                {{ $project->secrets_count }} secret(s)
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

    </div>
