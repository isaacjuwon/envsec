<?php

use Livewire\Component;

new class extends Component {
    public array $data = [];
    public string $display_name = '';

    public function mount(array $data)
    {
        $this->data = $data;
        $this->display_name = $data['display_name'] ?? auth()->user()->name;
    }

    public function submit()
    {
        $this->validate([
            'display_name' => 'required|string|min:2|max:255',
        ]);

        $this->dispatch('next', stepData: [
            'display_name' => $this->display_name,
        ]);
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">What should we call you?</flux:heading>
        <flux:subheading>This name will be visible to your team members.</flux:subheading>
    </div>

    <form wire:submit="submit" class="space-y-6">
        <flux:input label="Full Name" wire:model="display_name" placeholder="John Doe" autofocus />

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Next Step</flux:button>
        </div>
    </form>
</div>
