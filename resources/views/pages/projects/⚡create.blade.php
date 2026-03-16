<?php

use App\Models\Workspace;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Transition;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('New Project')] class extends Component {

    public string $name = '';
    public string $slug = '';
    public string $description = '';

    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    #[Transition]
    public function create(): void
    {
        $this->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:255', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $workspace = Workspace::current();

        abort_unless($workspace !== null, 403);

        $project = $workspace->projects()->create([
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description ?: null,
        ]);

        $this->redirectRoute('projects.edit', $project->id, navigate: true);
    }
}; ?>

    <div wire:transition="content" class="flex h-full w-full flex-1 flex-col gap-6">

        {{-- Header --}}
        <div class="flex items-center gap-3">
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('projects.index') }}" wire:navigate />
            <div>
                <flux:heading size="xl">New Project</flux:heading>
                <flux:subheading>Create a new project in your workspace</flux:subheading>
            </div>
        </div>

        {{-- Form --}}
        <div class="w-full max-w-xl">
            <form wire:submit="create" class="space-y-5">

                <flux:input
                    wire:model.live="name"
                    label="Project Name"
                    placeholder="My Awesome Project"
                    autofocus
                    required
                />

                <flux:input
                    wire:model="slug"
                    label="Slug"
                    placeholder="my-awesome-project"
                    description="Used in URLs. Auto-generated from name."
                />

                <flux:textarea
                    wire:model="description"
                    label="Description"
                    placeholder="What is this project about?"
                    rows="3"
                />

                <div class="flex items-center gap-3 pt-2">
                    <flux:button type="submit" variant="primary">
                        Create Project
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('projects.index') }}" wire:navigate>
                        Cancel
                    </flux:button>
                </div>

            </form>
        </div>

    </div>
