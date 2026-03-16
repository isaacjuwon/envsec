<?php

use App\Models\Project;
use App\Models\Secret;
use App\Models\SecretValue;
use App\Models\SecretValueHistory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Transition;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {

    public Project $project;
    public Secret $secret;

    // Edit secret meta
    public bool $showEditMetaModal = false;
    public string $editKey = '';
    public string $editDescription = '';

    // Manage value
    public bool $showValueModal = false;
    public string $valueEnvironment = '';
    public string $valueText = '';
    public bool $revealedValueId = false;
    public array $revealed = [];

    public function mount(int $projectId, int $secretId): void
    {
        $this->project = Project::findOrFail($projectId);
        $this->secret  = $this->project->secrets()->with('values.history.changedByUser')->findOrFail($secretId);
    }

    public function openEditMeta(): void
    {
        $this->editKey         = $this->secret->key;
        $this->editDescription = $this->secret->description ?? '';
        $this->showEditMetaModal = true;
    }

    #[Transition]
    public function updateMeta(): void
    {
        $this->validate([
            'editKey'         => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9_]+$/'],
            'editDescription' => ['nullable', 'string', 'max:500'],
        ]);

        $this->secret->update([
            'key'         => strtoupper($this->editKey),
            'description' => $this->editDescription ?: null,
        ]);

        $this->showEditMetaModal = false;
        $this->secret->refresh();
    }

    public function openValueModal(string $environment = ''): void
    {
        $this->valueEnvironment = $environment;
        $existing = $this->secret->values()->where('environment', $environment)->first();
        $this->valueText = $existing ? '' : '';
        $this->showValueModal = true;
    }

    #[Transition]
    public function saveValue(): void
    {
        $this->validate([
            'valueEnvironment' => ['required', 'string', 'max:50'],
            'valueText'        => ['required', 'string'],
        ]);

        $existing = $this->secret->values()->where('environment', $this->valueEnvironment)->first();

        if ($existing) {
            // Record history before updating
            SecretValueHistory::create([
                'secret_value_id' => $existing->id,
                'value'           => $existing->value,
                'changed_by'      => Auth::id(),
            ]);

            $existing->update(['value' => $this->valueText]);
        } else {
            $this->secret->values()->create([
                'environment' => $this->valueEnvironment,
                'value'       => $this->valueText,
            ]);
        }

        $this->showValueModal = false;
        $this->reset('valueEnvironment', 'valueText');
        $this->secret->load('values.history.changedByUser');
    }

    public function toggleReveal(int $valueId): void
    {
        if (in_array($valueId, $this->revealed)) {
            $this->revealed = array_filter($this->revealed, fn($id) => $id !== $valueId);
        } else {
            $this->revealed[] = $valueId;
        }
    }

    #[Transition]
    public function deleteValue(int $valueId): void
    {
        $this->secret->values()->where('id', $valueId)->delete();
        $this->secret->load('values.history.changedByUser');
    }
}; ?>

<x-slot:title>{{ $secret->key }} &ndash; {{ $project->name }}</x-slot:title>
    <div wire:transition="content" class="flex h-full w-full flex-1 flex-col gap-6">

        {{-- Breadcrumb --}}
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('projects.secrets.index', $project->id)" wire:navigate>
                {{ $project->name }}
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $secret->key }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <flux:icon icon="key" class="size-5 text-zinc-400" />
                    <flux:heading size="xl">
                        <code class="font-mono">{{ $secret->key }}</code>
                    </flux:heading>
                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openEditMeta" />
                </div>
                @if ($secret->description)
                    <flux:subheading class="mt-1">{{ $secret->description }}</flux:subheading>
                @endif
            </div>
            <flux:button variant="primary" icon="plus" wire:click="openValueModal('')">
                Set Value
            </flux:button>
        </div>

        @php
            $missingEnvs = collect(\App\Enums\Environment::cases())
                ->reject(fn ($env) => $this->secret->values->contains('environment', $env));
        @endphp

        @if ($missingEnvs->isNotEmpty())
            <flux:callout variant="warning" icon="exclamation-triangle">
                This secret is missing values for: <strong>{{ $missingEnvs->map->label()->join(', ') }}</strong>.
            </flux:callout>
        @endif

        {{-- Environment Values --}}
        <div class="grid gap-4">
            @forelse ($this->secret->values as $secretValue)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4" wire:key="val-{{ $secretValue->id }}">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <flux:badge :color="$secretValue->environment->color()">{{ $secretValue->environment->label() }}</flux:badge>
                            <code class="font-mono text-sm text-zinc-700 dark:text-zinc-300">
                                @if (in_array($secretValue->id, $revealed))
                                    {{ $secretValue->value }}
                                @else
                                    {{ str_repeat('•', min(strlen($secretValue->value), 24)) }}
                                @endif
                            </code>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:button
                                size="sm"
                                variant="ghost"
                                :icon="in_array($secretValue->id, $revealed) ? 'eye-slash' : 'eye'"
                                wire:click="toggleReveal({{ $secretValue->id }})"
                            />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="pencil"
                                wire:click="openValueModal('{{ $secretValue->environment }}')"
                            />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                wire:click="deleteValue({{ $secretValue->id }})"
                            />
                            <div x-data="{ copied: false }" class="flex items-center">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="document-duplicate"
                                    x-on:click="window.copyToClipboard('{{ $secretValue->value }}').then(ok => { if(ok) { copied = true; setTimeout(() => copied = false, 2000) } })"
                                    x-bind:class="copied ? 'text-green-500' : 'text-zinc-400'"
                                    title="Copy value"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- History --}}
                    @if ($secretValue->history->isNotEmpty())
                        <details class="mt-3">
                            <summary class="cursor-pointer text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                {{ $secretValue->history->count() }} change(s)
                            </summary>
                            <div class="mt-2 space-y-1 pl-2 border-l border-zinc-200 dark:border-zinc-700">
                                @foreach ($secretValue->history->sortByDesc('created_at') as $entry)
                                    <div class="flex items-center justify-between text-xs text-zinc-500">
                                        <span>Changed by <strong>{{ $entry->changedByUser?->name ?? 'Unknown' }}</strong></span>
                                        <span>{{ $entry->created_at->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700 py-10 text-center">
                    <flux:subheading>No values set yet. Click "Set Value" to add one.</flux:subheading>
                </div>
            @endforelse
        </div>

        {{-- Edit Meta Modal --}}
        <flux:modal wire:model.self="showEditMetaModal" class="min-w-[26rem]">
            <form wire:submit="updateMeta" class="space-y-6">
                <div>
                    <flux:heading size="lg">Edit Secret</flux:heading>
                    <flux:text class="mt-2 text-sm text-zinc-500">Update the secret key and description.</flux:text>
                </div>

                <div class="space-y-4">
                    <flux:input
                        wire:model="editKey"
                        label="Key"
                        description="Uppercase letters, numbers and underscores only."
                    />
                    <flux:textarea
                        wire:model="editDescription"
                        label="Description"
                        rows="2"
                    />
                </div>

                <div class="flex">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" class="ml-2">Save Changes</flux:button>
                </div>
            </form>
        </flux:modal>

        {{-- Set Value Modal --}}
        <flux:modal wire:model.self="showValueModal" class="min-w-[26rem]">
            <form wire:submit="saveValue" class="space-y-6">
                <div>
                    <flux:heading size="lg">Set Value</flux:heading>
                </div>

                <flux:callout variant="info" icon="information-circle">
                    Secrets are stored securely in our database. Consider using unique values for each environment to prevent accidental data leaks or cross-contamination.
                </flux:callout>

                <div class="space-y-4">
                    <flux:select
                        wire:model="valueEnvironment"
                        label="Environment"
                        placeholder="Select environment…"
                    >
                        <flux:select.option value="development">Development</flux:select.option>
                        <flux:select.option value="staging">Staging</flux:select.option>
                        <flux:select.option value="production">Production</flux:select.option>
                    </flux:select>
                    <flux:textarea
                        wire:model="valueText"
                        label="Value"
                        placeholder="Enter secret value…"
                        rows="3"
                        description="Values are stored as-is. Consider encrypting sensitive values before storing."
                    />
                </div>

                <div class="flex">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" class="ml-2">Save Value</flux:button>
                </div>
            </form>
        </flux:modal>

    </div>
