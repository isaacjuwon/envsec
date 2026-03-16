<?php

use App\Models\Project;
use App\Enums\Environment;
use Livewire\Component;

new class extends Component {
    public Project $project;
    public string $environment = 'development';

    public function export()
    {
        $this->validate([
            'environment' => ['required', \Illuminate\Validation\Rule::enum(Environment::class)],
        ]);

        $envEnum = Environment::from($this->environment);
        $secrets = $this->project->secrets()->with(['values' => function($query) use ($envEnum) {
            $query->where('environment', $envEnum);
        }])->get();

        $content = "# Secrets export for {$this->project->name} ({$envEnum->label()})\n";
        $content .= "# Generated at " . now()->toDateTimeString() . "\n\n";

        foreach ($secrets as $secret) {
            $val = $secret->values->first();
            if ($val) {
                // Basic escaping for values with spaces
                $value = $val->value;
                if (str_contains($value, ' ') || str_contains($value, '#')) {
                    $value = '"' . str_replace('"', '\"', $value) . '"';
                }
                $content .= "{$secret->key}={$value}\n";
            }
        }

        $filename = "{$this->project->slug}-{$this->environment}.env";

        $this->modal('export-modal')->close();

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename);
    }
}; ?>

<div>
    <flux:modal name="export-modal" class="min-w-[28rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Export Secrets</flux:heading>
            </div>

            <flux:callout variant="info" icon="information-circle">
                This will generate a <code>.env</code> file containing all secrets configured for the selected environment.
            </flux:callout>

            <flux:select wire:model="environment" label="Environment to Export">
                @foreach (Environment::cases() as $env)
                    <flux:select.option :value="$env->value">{{ $env->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:button variant="ghost" x-on:click="$flux.modal('export-modal').close()">Cancel</flux:button>
                <flux:button variant="primary" class="ml-2" wire:click="export" inset="top bottom">Download</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
