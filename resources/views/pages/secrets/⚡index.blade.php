<?php

use App\Models\Project;
use App\Models\Secret;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Transition;
use Livewire\Attributes\Url;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\QueueLog;

new #[Layout('layouts.app')] #[Title('Secrets')] class extends Component {
    use WithFileUploads;

    public Project $project;

    #[Url(history: true)]
    public string $search = '';

    // Layout
    #[Session]
    public string $view = 'list'; // 'list' | 'grid'

    // Create
    public string $key = '';
    public string $description = '';
    public array $values = [
        'production' => '',
        'staging' => '',
        'development' => '',
    ];

    // Delete
    public bool $showDeleteModal = false;
    public ?int $deletingSecretId = null;

    #[Defer]
    public function mount(int $projectId): void
    {
        $this->project = Project::findOrFail($projectId);
    }

    #[Transition]
    public function createSecret(): void
    {
        $this->validate([
            'key' => ['required', 'string', 'max:255', 'unique:secrets,key', 'regex:/^[A-Z0-9_]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'values.*' => ['nullable', 'string'],
        ]);

        $secret = $this->project->secrets()->create([
            'key'         => strtoupper($this->key),
            'description' => $this->description ?: null,
        ]);

        foreach ($this->values as $env => $val) {
            if (filled($val)) {
                $secret->values()->create([
                    'environment' => $env,
                    'value' => $val,
                ]);
            }
        }

        $this->modal('create-secret-modal')->close();
        $this->reset('key', 'description', 'values');
    }

    public function confirmDelete(int $secretId): void
    {
        $this->deletingSecretId = $secretId;
        $this->showDeleteModal  = true;
    }

    #[Transition]
    public function delete(): void
    {
        $secret = Secret::find($this->deletingSecretId);

        if ($secret) {
            $secret->delete();
        }

        $this->showDeleteModal = false;
        $this->deletingSecretId = null;
        $this->dispatch('success', message: 'Secret deleted successfully.');
    }
}; ?>

    <div wire:transition="content" class="flex h-full w-full flex-1 flex-col">

        <div class="flex items-center gap-2 mb-6">
            <flux:heading size="xl" class="flex-1 truncate">{{ $project->name }}</flux:heading>

            <flux:button.group>
              <flux:button
                    size="sm"
                    variant="ghost"
                    icon="arrow-up-tray"
                    x-on:click="$flux.modal('import-modal').show()"
                >Import</flux:button>
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="arrow-down-tray"
                    x-on:click="$flux.modal('export-modal').show()"
                >Export</flux:button>
            </flux:button.group>

            <flux:button.group>
                <flux:button
                    size="sm"
                    :variant="$view === 'list' ? 'filled' : 'ghost'"
                    icon="bars-3"
                    x-on:click="$wire.set('view', 'list')"
                    title="List view"
                />
                <flux:button
                    size="sm"
                    :variant="$view === 'grid' ? 'filled' : 'ghost'"
                    icon="squares-2x2"
                    x-on:click="$wire.set('view', 'grid')"
                    title="Grid view"
                />
            </flux:button.group>

            <flux:button size="sm" variant="primary" x-on:click="$flux.modal('create-secret-modal').show()" icon="plus" inset="top bottom">
                <span class="hidden sm:inline">Add Secret</span>
            </flux:button>
        </div>

        @php
            $secrets = $project->secrets()
                ->withCount('values')
                ->when($this->search, fn ($q) => $q->where('key', 'like', "%{$this->search}%"))
                ->latest()->get();
        @endphp

        {{-- Empty state --}}
        @if ($secrets->isEmpty())
            <div class="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700 py-20 text-center">
                <flux:icon icon="key" class="size-10 text-zinc-400" />
                <div>
                    <flux:heading>No secrets yet</flux:heading>
                    <flux:subheading>Add your first secret to get started.</flux:subheading>
                </div>
                <flux:button variant="primary" x-on:click="$flux.modal('create-secret-modal').show()" icon="plus" inset="top bottom">Add Secret</flux:button>
            </div>

        {{-- ── LIST VIEW ──────────────────────────────────────────── --}}
        @elseif ($view === 'list')
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Key</flux:table.column>
                    <flux:table.column>Description</flux:table.column>
                    <flux:table.column>Environments</flux:table.column>
                    <flux:table.column>Updated</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($secrets as $secret)
                        <flux:table.row :key="$secret->id" wire:key="row-{{ $secret->id }}">
                            {{-- Key --}}
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <a
                                        href="{{ route('projects.secrets.show', [$project->id, $secret->id]) }}"
                                        wire:navigate
                                        class="font-mono text-sm font-semibold text-zinc-800 dark:text-zinc-100 hover:underline"
                                    >{{ $secret->key }}</a>

                                    <div x-data="{ copied: false }" class="flex items-center">
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            icon="document-duplicate"
                                            x-on:click="window.copyToClipboard('{{ $secret->key }}').then(ok => { if(ok) { copied = true; setTimeout(() => copied = false, 2000) } })"
                                            x-bind:class="copied ? 'text-green-500' : 'text-zinc-400'"
                                            title="Copy key"
                                        />
                                    </div>
                                </div>
                            </flux:table.cell>

                            {{-- Description --}}
                            <flux:table.cell class="max-w-xs truncate text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $secret->description ?? '—' }}
                            </flux:table.cell>

                            {{-- Env badges --}}
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($secret->values as $val)
                                        <flux:badge size="sm" :color="$val->environment->color()" inset="top bottom">{{ $val->environment->label() }}</flux:badge>
                                    @endforeach
                                    @if ($secret->values->isEmpty())
                                        <span class="text-xs text-zinc-400">No values</span>
                                    @endif
                                </div>
                            </flux:table.cell>

                            {{-- Updated --}}
                            <flux:table.cell class="whitespace-nowrap text-sm text-zinc-400">
                                {{ $secret->updated_at->diffForHumans() }}
                            </flux:table.cell>

                            {{-- Actions --}}
                            <flux:table.cell>
                                <div class="flex items-center justify-end gap-1">
                                    @php
                                        $valueToCopy = $secret->values->firstWhere('environment', 'production')?->value
                                            ?? $secret->values->first()?->value;
                                    @endphp
                                    @if ($valueToCopy)
                                        <div x-data="{ copied: false }" class="flex items-center">
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                icon="document-duplicate"
                                                x-on:click="window.copyToClipboard('{{ $valueToCopy }}').then(ok => { if(ok) { copied = true; setTimeout(() => copied = false, 2000) } })"
                                                x-bind:class="copied ? 'text-green-500' : 'text-zinc-400'"
                                                title="Copy value (production or first)"
                                            />
                                        </div>
                                    @endif
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        :href="route('projects.secrets.show', [$project->id, $secret->id])"
                                        wire:navigate
                                        inset="top bottom"
                                    >Manage</flux:button>
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        wire:click="confirmDelete({{ $secret->id }})"
                                        inset="top bottom"
                                    />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

        {{-- ── GRID VIEW ──────────────────────────────────────────── --}}
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($secrets as $secret)
                    <div
                        wire:key="card-{{ $secret->id }}"
                        class="group relative flex flex-col gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5 hover:border-zinc-300 dark:hover:border-zinc-600 transition"
                    >
                        {{-- Top row --}}
                        <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2 truncate">
                                    <a
                                        href="{{ route('projects.secrets.show', [$project->id, $secret->id]) }}"
                                        wire:navigate
                                        class="font-mono text-sm font-semibold text-zinc-800 dark:text-zinc-100 hover:underline truncate"
                                    >{{ $secret->key }}</a>

                                    <div x-data="{ copied: false }" class="flex items-center shrink-0">
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            icon="document-duplicate"
                                            x-on:click="window.copyToClipboard('{{ $secret->key }}').then(ok => { if(ok) { copied = true; setTimeout(() => copied = false, 2000) } })"
                                            x-bind:class="copied ? 'text-green-500' : 'text-zinc-400'"
                                            title="Copy key"
                                        />
                                    </div>
                                </div>

                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                class="shrink-0 opacity-0 group-hover:opacity-100 transition"
                                wire:click="confirmDelete({{ $secret->id }})"
                            />
                        </div>

                        {{-- Description --}}
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2 min-h-[2.5rem]">
                            {{ $secret->description ?? 'No description.' }}
                        </p>

                        {{-- Environment badges --}}
                        <div class="flex flex-wrap gap-1">
                            @foreach ($secret->values as $val)
                                <flux:badge size="sm" :color="$val->environment->color()">{{ $val->environment->label() }}</flux:badge>
                            @endforeach
                            @if ($secret->values->isEmpty())
                                <flux:badge size="sm" color="zinc">No values</flux:badge>
                            @endif
                        </div>

                        {{-- Footer --}}
                        <div class="mt-auto flex items-center justify-between pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            <span class="text-xs text-zinc-400">{{ $secret->updated_at->diffForHumans() }}</span>
                            <div class="flex items-center gap-1">
                                @php
                                    $valueToCopy = $secret->values->firstWhere('environment', 'production')?->value
                                        ?? $secret->values->first()?->value;
                                @endphp
                                @if ($valueToCopy)
                                    <div x-data="{ copied: false }" class="flex items-center">
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="document-duplicate"
                                            x-on:click="window.copyToClipboard('{{ $valueToCopy }}').then(ok => { if(ok) { copied = true; setTimeout(() => copied = false, 2000) } })"
                                            x-bind:class="copied ? 'text-green-500' : 'text-zinc-400'"
                                            title="Copy value (production or first)"
                                        />
                                    </div>
                                @endif
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    :href="route('projects.secrets.show', [$project->id, $secret->id])"
                                    wire:navigate
                                >Manage →</flux:button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Create Secret Modal --}}
        <flux:modal name="create-secret-modal" class="min-w-[28rem]">
            <form wire:submit="createSecret" class="space-y-6">
                <div>
                    <flux:heading size="lg">Add Secret</flux:heading>
                    <flux:text class="mt-2">Secrets are shared across environments. Set values per environment after creation.</flux:text>
                </div>

                <div class="space-y-4">
                    <flux:input
                        wire:model="key"
                        label="Key"
                        placeholder="DATABASE_URL"
                        description="Uppercase letters, numbers and underscores only."
                        autofocus
                    />
                    <flux:textarea
                        wire:model="description"
                        label="Description"
                        placeholder="Optional description…"
                        rows="2"
                    />

                    <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 space-y-4">
                        <flux:heading size="sm">Values</flux:heading>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <flux:textarea
                                wire:model="values.production"
                                label="Production"
                                placeholder="Enter production value…"
                                rows="2"
                            />
                            <flux:textarea
                                wire:model="values.staging"
                                label="Staging"
                                placeholder="Enter staging value…"
                                rows="2"
                            />
                            <flux:textarea
                                wire:model="values.development"
                                label="Development"
                                placeholder="Enter development value…"
                                rows="2"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex">
                    <flux:spacer />
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-secret-modal').close()">Cancel</flux:button>
                    <flux:button type="submit" variant="primary" class="ml-2" inset="top bottom">Create</flux:button>
                </div>
            </form>
        </flux:modal>

        {{-- Delete Confirm Modal --}}
        <flux:modal wire:model.self="showDeleteModal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Secret?</flux:heading>
                    <flux:text class="mt-2 text-sm text-zinc-500">
                        This will permanently delete the secret and all its environment values. This action cannot be undone.
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="delete">Delete Secret</flux:button>
                </div>
            </div>
        </flux:modal>

        <livewire:import-secrets :$project />
        <livewire:export-secrets :$project />
    </div>
