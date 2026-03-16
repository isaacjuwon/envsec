<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Transition;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Edit Project')] class extends Component {

    public Project $project;

    // Project details
    public string $name = '';
    public string $slug = '';
    public string $description = '';

    // Member management
    public string $memberSearch = '';
    public string $newMemberEmail = '';
    public string $newMemberRole = 'member';

    public ?int $editingMemberId = null;
    public string $editingRole = '';

    public function mount(int $projectId): void
    {
        $this->project = Project::with('members')->findOrFail($projectId);
        $this->name        = $this->project->name;
        $this->slug        = $this->project->slug;
        $this->description = $this->project->description ?? '';
    }

    public function updatedName(string $value): void
    {
        // Only auto-update slug if it hasn't been manually changed
        if ($this->slug === Str::slug($this->project->name)) {
            $this->slug = Str::slug($value);
        }
    }

    #[Transition]
    public function save(): void
    {
        $this->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:255', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->project->update([
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description ?: null,
        ]);

        $this->dispatch('saved');
    }

    // ── Members ──────────────────────────────────────────────────────────

    #[Computed]
    public function members()
    {
        return $this->project->members()->orderByPivot('created_at')->get();
    }

    #[Transition]
    public function addMember(): void
    {
        $this->validate([
            'newMemberEmail' => ['required', 'email'],
            'newMemberRole'  => ['required', Rule::in(['admin', 'member', 'viewer'])],
        ]);

        $user = User::where('email', $this->newMemberEmail)->first();

        if (! $user) {
            $this->addError('newMemberEmail', 'No user found with that email address.');
            return;
        }

        if ($this->project->members()->where('user_id', $user->id)->exists()) {
            $this->addError('newMemberEmail', 'This user is already a member of this project.');
            return;
        }

        $this->project->members()->attach($user->id, ['role' => $this->newMemberRole]);

        $this->reset('newMemberEmail', 'newMemberRole');
        unset($this->members);
    }

    public function openEditRole(int $userId): void
    {
        $this->editingMemberId = $userId;
        $member = $this->members->firstWhere('id', $userId);
        $this->editingRole = $member?->pivot->role ?? 'member';
    }

    #[Transition]
    public function updateRole(): void
    {
        $this->validate([
            'editingRole' => ['required', Rule::in(['admin', 'member', 'viewer'])],
        ]);

        $this->project->members()->updateExistingPivot($this->editingMemberId, [
            'role' => $this->editingRole,
        ]);

        $this->editingMemberId = null;
        unset($this->members);
    }

    #[Transition]
    public function removeMember(int $userId): void
    {
        $this->project->members()->detach($userId);
        unset($this->members);
    }

    #[Transition]
    public function delete(): void
    {
        $this->project->delete();
        $this->redirectRoute('projects.index', navigate: true);
    }
}; ?>

    <div wire:transition="content" class="flex h-full w-full flex-1 flex-col gap-8">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" icon="arrow-left" href="{{ route('projects.index') }}" wire:navigate />
                <div>
                    <flux:heading size="xl">{{ $project->name }}</flux:heading>
                    <flux:subheading>Edit project settings and manage members</flux:subheading>
                </div>
            </div>

            <flux:button
                icon="plus"
                variant="primary"
                :href="route('projects.secrets.index', $project->id)"
                wire:navigate
                inset="top bottom"
            >
                Add Secret
            </flux:button>
        </div>

        <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">

            {{-- ── Left: Project Details ─────────────────────────── --}}
            <div class="lg:col-span-2 space-y-8">

                {{-- Details section --}}
                <section>
                    <flux:heading class="mb-4">Project Details</flux:heading>
                    <form wire:submit="save" class="space-y-5">
                        <flux:input
                            wire:model.live="name"
                            label="Project Name"
                            required
                        />
                        <flux:input
                            wire:model="slug"
                            label="Slug"
                            description="Used in URLs."
                        />
                        <flux:textarea
                            wire:model="description"
                            label="Description"
                            rows="3"
                        />
                        <div class="flex items-center gap-3">
                            <flux:button type="submit" variant="primary">Save Changes</flux:button>
                            <x-action-message on="saved" class="text-sm text-green-600 dark:text-green-400">
                                Saved!
                            </x-action-message>
                        </div>
                    </form>
                </section>

                <flux:separator />

                {{-- ── Members section ──────────────────────── --}}
                <section>
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading>Members</flux:heading>
                        <flux:badge color="zinc">{{ $this->members->count() }}</flux:badge>
                    </div>

                    {{-- Add member --}}
                    <div class="mb-6 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4 space-y-3">
                        <flux:heading size="sm">Add Member</flux:heading>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <flux:input
                                    wire:model="newMemberEmail"
                                    placeholder="user@example.com"
                                    type="email"
                                />
                            </div>
                            <flux:select wire:model="newMemberRole" class="w-32">
                                <flux:select.option value="viewer">Viewer</flux:select.option>
                                <flux:select.option value="member">Member</flux:select.option>
                                <flux:select.option value="admin">Admin</flux:select.option>
                            </flux:select>
                            <flux:button wire:click="addMember" variant="primary" icon="user-plus">
                                Add
                            </flux:button>
                        </div>
                        @error('newMemberEmail')
                            <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                        @enderror
                    </div>

                    {{-- Member list --}}
                    @if ($this->members->isEmpty())
                        <div class="rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700 py-8 text-center">
                            <flux:subheading>No members yet. Add one above.</flux:subheading>
                        </div>
                    @else
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>User</flux:table.column>
                                <flux:table.column>Role</flux:table.column>
                                <flux:table.column>Joined</flux:table.column>
                                <flux:table.column></flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($this->members as $member)
                                    <flux:table.row :key="$member->id" wire:key="member-{{ $member->id }}">
                                        <flux:table.cell>
                                            <div class="flex items-center gap-3">
                                                <flux:avatar size="sm" name="{{ $member->name }}" />
                                                <div>
                                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $member->name }}</p>
                                                    <p class="text-xs text-zinc-400">{{ $member->email }}</p>
                                                </div>
                                            </div>
                                        </flux:table.cell>

                                        <flux:table.cell>
                                            @if ($editingMemberId === $member->id)
                                                <div class="flex items-center gap-2">
                                                    <flux:select wire:model="editingRole" class="w-28">
                                                        <flux:select.option value="viewer">Viewer</flux:select.option>
                                                        <flux:select.option value="member">Member</flux:select.option>
                                                        <flux:select.option value="admin">Admin</flux:select.option>
                                                    </flux:select>
                                                    <flux:button size="sm" variant="primary" wire:click="updateRole">Save</flux:button>
                                                    <flux:button size="sm" variant="ghost" wire:click="$set('editingMemberId', null)">✕</flux:button>
                                                </div>
                                            @else
                                                <flux:badge color="{{ match($member->pivot->role) {
                                                    'admin'  => 'blue',
                                                    'member' => 'zinc',
                                                    default  => 'zinc',
                                                } }}" size="sm" inset="top bottom">{{ $member->pivot->role }}</flux:badge>
                                            @endif
                                        </flux:table.cell>

                                        <flux:table.cell class="text-zinc-400">
                                            {{ $member->pivot->created_at?->diffForHumans() ?? '—' }}
                                        </flux:table.cell>

                                        <flux:table.cell>
                                            <div class="flex items-center justify-end gap-1">
                                                <flux:button
                                                    size="sm"
                                                    variant="ghost"
                                                    icon="pencil"
                                                    wire:click="openEditRole({{ $member->id }})"
                                                    inset="top bottom"
                                                />
                                                <flux:button
                                                    size="sm"
                                                    variant="ghost"
                                                    icon="trash"
                                                    wire:click="removeMember({{ $member->id }})"
                                                    inset="top bottom"
                                                />
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @endif
                </section>

            </div>

            {{-- ── Right: Quick links + Danger zone ──────────────── --}}
            <aside class="space-y-6">
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">Project Assets</flux:heading>
                    </div>
                    
                    <div class="space-y-2">
                        <flux:button
                            icon="key"
                            variant="subtle"
                            color="amber"
                            :href="route('projects.secrets.index', $project->id)"
                            wire:navigate
                            class="w-full justify-start font-medium"
                        >
                            Manage Secrets
                        </flux:button>

                        <flux:button
                            icon="plus"
                            variant="ghost"
                            :href="route('projects.secrets.index', $project->id)"
                            wire:navigate
                            class="w-full justify-start text-zinc-500"
                        >
                            Add Secret
                        </flux:button>
                    </div>
                </div>

                {{-- Danger zone --}}
                <div class="rounded-xl border border-red-200 dark:border-red-900/50 p-4 space-y-3">
                    <flux:heading size="sm" class="text-red-600 dark:text-red-400">Danger Zone</flux:heading>
                    <flux:text class="text-sm text-zinc-500">
                        Deleting this project will permanently remove all secrets and member associations.
                    </flux:text>
                    <flux:button
                        variant="danger"
                        icon="trash"
                        wire:click="delete"
                        wire:confirm="Are you sure you want to delete '{{ $project->name }}'? This cannot be undone."
                    >Delete Project</flux:button>
                </div>
            </aside>

        </div>

    </div>
