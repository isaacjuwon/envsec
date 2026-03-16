<?php

use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {

    public bool $open = false;

    #[Computed]
    public function workspaces()
    {
        return Auth::user()->workspaces()->get();
    }

    #[Computed]
    public function current(): ?Workspace
    {
        return Workspace::current() 
            ?? (request()->route('tenant') ? Workspace::where('slug', request()->route('tenant'))->first() : null);
    }

    public function switchTo(int $workspaceId): void
    {
        $workspace = Auth::user()->workspaces()->find($workspaceId);
        
        if ($workspace) {
            $workspace->use();
            $this->redirect(route('dashboard', ['tenant' => $workspace->slug]));
        }

        $this->open = false;
        $this->dispatch('workspace-switched');
    }
}
?>




<flux:dropdown align="end" position="bottom">

    {{-- Trigger: current workspace name --}}
    <flux:button variant="ghost" size="sm" icon-trailing="chevrons-up-down" class="max-w-[180px]">
        <span class="truncate text-sm font-medium">
            {{ $this->current?->name ?? __('Select Workspace') }}
        </span>
    </flux:button>

    {{-- Dropdown menu --}}
    <flux:menu class="min-w-[220px]">
        <flux:menu.radio.group>
            @foreach ($this->workspaces as $workspace)
                <flux:menu.radio
                    :value="$workspace->id"
                    :checked="$this->current?->id === $workspace->id"
                    wire:click="switchTo({{ $workspace->id }})"
                >
                    {{ $workspace->name }}
                </flux:menu.radio>
            @endforeach

            @if ($this->workspaces->isEmpty())
                <div class="px-3 py-2 text-sm text-zinc-400">No workspaces found.</div>
            @endif
        </flux:menu.radio.group>

        <flux:menu.separator />

        <flux:menu.item
            icon="plus"
            href="{{ route('onboarding') }}"
            wire:navigate
        >Create Workspace</flux:menu.item>

        @if ($this->current)
            <flux:menu.item
                icon="cog"
                href="{{ route('tenant.platform', ['tenant' => $this->current->slug]) }}"
                wire:navigate
            >{{ __('Tenant Settings') }}</flux:menu.item>
        @endif
    </flux:menu>

</flux:dropdown>
