<?php

use App\Models\Project;
use App\Enums\Environment;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Project $project;
    public string $method = 'text'; // 'text' or 'upload'
    public string $content = '';
    public $file;
    public string $environment = 'development';

    public function import(): void
    {
        $this->validate([
            'environment' => ['required', \Illuminate\Validation\Rule::enum(Environment::class)],
            'method' => 'required|in:text,upload',
            'content' => 'required_if:method,text',
            'file' => 'required_if:method,upload|file|max:1024',
        ]);

        $rawContent = $this->method === 'upload' 
            ? file_get_contents($this->file->getRealPath())
            : $this->content;

        $lines = explode("\n", $rawContent);
        $importedCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");

                if (empty($key)) continue;

                $secret = $this->project->secrets()->updateOrCreate(
                    ['key' => strtoupper($key)],
                    ['description' => 'Imported via .env']
                );

                $secret->values()->updateOrCreate(
                    ['environment' => $this->environment],
                    ['value' => $value]
                );

                $importedCount++;
            }
        }

        $this->dispatch('success', message: "Successfully imported {$importedCount} secrets for {$this->environment}.");
        $this->modal('import-modal')->close();
        $this->reset(['content', 'file']);
        $this->dispatch('secrets-updated');
    }
}; ?>

<div>
    <flux:modal name="import-modal" class="min-w-[32rem]">
        <form wire:submit="import" class="space-y-6">
            <div>
                <flux:heading size="lg">Import Secrets</flux:heading>
            </div>

            <flux:callout variant="info" icon="information-circle">
                Import secrets using the <code>KEY=VALUE</code> format. If a key already exists, its value for the selected environment will be <strong>overwritten</strong>.
            </flux:callout>

            <flux:select wire:model="environment" label="Target Environment">
                @foreach (\App\Enums\Environment::cases() as $env)
                    <flux:select.option :value="$env->value">{{ $env->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:radio.group wire:model.live="method" variant="segmented" class="w-full">
                <flux:radio value="text" icon="document-text">Paste Text</flux:radio>
                <flux:radio value="upload" icon="arrow-up-tray">Upload File</flux:radio>
            </flux:radio.group>

            @if ($method === 'text')
                <flux:textarea 
                    wire:model="content" 
                    label="Paste .env content" 
                    placeholder="KEY=VALUE" 
                    rows="8"
                    class="font-mono text-xs"
                />
            @else
                <div class="space-y-3">
                    <flux:input 
                        type="file"
                        wire:model="file" 
                        label="Choose .env file" 
                        accept=".env,text/plain"
                    />
                    <flux:text size="sm" class="text-zinc-500 italic">Supports .env and plain text files.</flux:text>
                </div>
            @endif

            <div class="flex">
                <flux:spacer />
                <flux:button variant="ghost" x-on:click="$flux.modal('import-modal').close()">Cancel</flux:button>
                <flux:button type="submit" variant="primary" class="ml-2" inset="top bottom">Import</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
