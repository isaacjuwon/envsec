<?php

use App\Models\Invitation;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public string $email = '';
    public string $role = 'member';
    public Workspace $workspace;

    public function mount(Workspace $workspace)
    {
        $this->workspace = $workspace;
    }

    public function invite()
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'in:admin,member'],
        ]);

        Invitation::updateOrCreate(
            ['workspace_id' => $this->workspace->id, 'email' => $this->email],
            [
                'role' => $this->role,
                'token' => Str::random(40),
                'expires_at' => now()->addDays(7),
            ]
        );

        $this->email = '';
        $this->dispatch('member-invited');
    }
};

?>

<div class="mt-10 pt-10 border-t border-zinc-200">
    <flux:heading level="2" size="lg">{{ __('Invite New Member') }}</flux:heading>
    <flux:subheading>{{ __('Send an invitation link to a colleague.') }}</flux:subheading>

    <form wire:submit="invite" class="mt-6 space-y-6 max-w-lg">
        <flux:input 
            wire:model="email" 
            label="{{ __('Email Address') }}" 
            type="email" 
            placeholder="colleague@example.com"
            required 
        />

        <flux:radio.group wire:model="role" label="{{ __('Role') }}" variant="cards" class="flex gap-4">
            <flux:radio value="member" label="{{ __('Member') }}" description="{{ __('Can view and manage secrets.') }}" />
            <flux:radio value="admin" label="{{ __('Admin') }}" description="{{ __('Can manage members and settings.') }}" />
        </flux:radio.group>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Send Invitation') }}</flux:button>

            <x-action-message on="member-invited">
                {{ __('Invitation sent successfully.') }}
            </x-action-message>
        </div>
    </form>
</div>
