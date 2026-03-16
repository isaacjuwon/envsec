<?php

use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Transition;
use Livewire\Component;

new class extends Component {
    public Workspace $workspace;
    public string $tenantName = '';
    public $members;

    public function mount()
    {
        $this->workspace = Auth::user()->ownedWorkspaces()->first();

        if (!$this->workspace) {
            $this->redirectRoute('dashboard');
            return;
        }

        $this->tenantName = $this->workspace->name;
        $this->loadMembers();
    }

    public function loadMembers(): void
    {
        $this->members = $this->workspace->members()->get();
    }

    #[On('member-invited')]
    public function refreshMembers(): void
    {
        $this->loadMembers();
    }

    #[Transition]
    public function updatePlatform(): void
    {
        $this->validate([
            'tenantName' => ['required', 'string', 'max:255'],
        ]);

        $this->workspace->update([
            'name' => $this->tenantName,
        ]);

        $this->dispatch('platform-updated');
    }
};

?>

<section>
    <x-pages::tenant.settings.layout wire:transition="content">
        <x-slot:heading>{{ __('Platform Settings') }}</x-slot:heading>
        <x-slot:heading_description>{{ __('Manage your tenant platform configurations.') }}</x-slot:heading_description>

        <form wire:submit="updatePlatform" class="mt-6 space-y-6">
            <flux:input wire:model="tenantName" :label="__('Platform Display Name')" required autofocus />

            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary">{{ __('Save Configuration') }}</flux:button>


            </div>
        </form>

        <div class="mt-10 pt-10 border-t border-zinc-200">
            <flux:heading level="2" size="lg">{{ __('Members') }}</flux:heading>
            <flux:subheading>{{ __('Manage existing members and their roles.') }}</flux:subheading>

            <div class="mt-6 space-y-4">
                @foreach ($members as $member)
                    <div class="flex items-center justify-between p-4 bg-white border border-zinc-200 rounded-lg">
                        <div class="flex items-center gap-4">
                            <flux:avatar :name="$member->name" size="sm" />
                            <div>
                                <div class="font-medium text-sm text-zinc-900">{{ $member->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $member->email }}</div>
                            </div>
                        </div>
                        <flux:badge size="sm" inset="top">{{ ucfirst($member->pivot->role) }}</flux:badge>
                    </div>
                @endforeach
            </div>
        </div>

        <livewire:tenant-invite :workspace="$workspace" />
    </x-pages::tenant.settings.layout>
</section>