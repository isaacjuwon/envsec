<?php

use Livewire\Component;

new class extends Component {
    public array $data = [];

    public function mount(array $data)
    {
        $this->data = $data;
    }

    public function finish()
    {
        return redirect()->route('dashboard');
    }
}; ?>

<div class="flex flex-col items-center text-center space-y-6">
    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-full">
        <flux:icon icon="check" class="size-12 text-green-500" />
    </div>

    <div>
        <flux:heading size="lg">You're all set!</flux:heading>
        <flux:subheading>Your workspace <strong>{{ $data['workspace_name'] }}</strong> is ready.</flux:subheading>
    </div>

    <flux:text class="max-w-xs">
        You can now start adding projects and managing your environment secrets securely.
    </flux:text>

    <div class="w-full pt-4">
        <flux:button wire:click="finish" variant="primary" class="w-full">Go to Dashboard</flux:button>
    </div>
</div>
