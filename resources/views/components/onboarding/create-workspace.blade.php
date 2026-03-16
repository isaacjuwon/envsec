<?php

use App\Models\Workspace;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public array $data = [];
    public string $workspace_name = '';
    public string $workspace_slug = '';

    public function mount(array $data)
    {
        $this->data = $data;
        $this->workspace_name = $data['workspace_name'] ?? '';
        $this->workspace_slug = $data['workspace_slug'] ?? '';
    }

    public function updatedWorkspaceName($value)
    {
        $this->workspace_slug = Str::slug($value);
    }

    public function submit()
    {
        $this->validate([
            'workspace_name' => 'required|string|min:2|max:255',
            'workspace_slug' => 'required|string|min:2|max:255|unique:workspaces,slug',
        ]);

        // Create the workspace
        $workspace = Workspace::create([
            'name' => $this->workspace_name,
            'slug' => $this->workspace_slug,
            'owner_id' => auth()->id(),
        ]);

        // Attach the user to the workspace
        $workspace->members()->attach(auth()->id(), ['role' => 'admin']);
        
        // Set as current workspace
        $workspace->use();

        // Update user display name if changed
        if ($this->data['display_name']) {
            auth()->user()->update(['name' => $this->data['display_name']]);
        }

        $this->dispatch('next', stepData: [
            'workspace_id' => $workspace->id,
            'workspace_name' => $this->workspace_name,
            'workspace_slug' => $this->workspace_slug,
        ]);
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Create your workspace</flux:heading>
        <flux:subheading>A workspace is where you manage your projects and secrets.</flux:subheading>
    </div>

    <form wire:submit="submit" class="space-y-6">
        <flux:input label="Workspace Name" wire:model.live.debounce.300ms="workspace_name" placeholder="Acme Corp" autofocus />

        <flux:input label="Workspace Slug" wire:model="workspace_slug" prefix="envsec.app/" />

        <div class="flex justify-end gap-2">
            <flux:button type="submit" variant="primary">Create Workspace</flux:button>
        </div>
    </form>
</div>
