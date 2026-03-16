@props([
    'title',
    'addLabel'   => 'Add',
    'addHref'    => null,
    'addClick'   => null,
    'showAdd'    => true,
    'showSearch' => true,
    'showToggle' => false,   // show grid/list toggle
    'view'       => 'list',  // current view: 'list' | 'grid'
    'onToggle'   => null,    // wire:click action name for toggle
    'search'     => '',      // wire:model target for search
])

<div class="flex flex-col gap-3 mb-6">

    {{-- ── Main toolbar row ────────────────────────────────────── --}}
    <div class="flex items-center gap-2">

        {{-- Page title --}}
        <flux:heading size="xl" class="flex-1 truncate">{{ $title }}</flux:heading>

        {{-- ── Right-side actions ──────────────────────────────── --}}

        {{-- Search bar (hidden on mobile, visible md+) --}}
        @if ($showSearch)
            <div class="hidden md:flex">
                <flux:input
                    wire:model.live.debounce.300ms="{{ $search }}"
                    icon="magnifying-glass"
                    placeholder="Search…"
                    class="w-56"
                    size="sm"
                />
            </div>
        @endif

        {{-- Grid / List toggle --}}
        @if ($showToggle && $onToggle)
            <flux:button.group>
                <flux:button
                    size="sm"
                    :variant="$view === 'list' ? 'filled' : 'ghost'"
                    icon="bars-3"
                    wire:click="{{ $onToggle }}('list')"
                    title="List view"
                />
                <flux:button
                    size="sm"
                    :variant="$view === 'grid' ? 'filled' : 'ghost'"
                    icon="squares-2x2"
                    wire:click="{{ $onToggle }}('grid')"
                    title="Grid view"
                />
            </flux:button.group>
        @endif

        {{-- Search icon (mobile only) --}}
        @if ($showSearch)
            <div class="md:hidden">
                <flux:tooltip content="Search" position="bottom">
                    <flux:button
                        size="sm"
                        variant="ghost"
                        icon="magnifying-glass"
                        x-data="{ open: false }"
                        @click="open = !open; $nextTick(() => $el.closest('[data-toolbar]').querySelector('[data-search-bar]')?.focus())"
                    />
                </flux:tooltip>
            </div>
        @endif

        {{-- Add button --}}
        @if ($showAdd)
            @if ($addHref)
                <flux:button
                    size="sm"
                    variant="primary"
                    icon="plus"
                    :href="$addHref"
                    wire:navigate
                >
                    {{-- Text hidden on mobile --}}
                    <span class="hidden sm:inline">{{ $addLabel }}</span>
                </flux:button>
            @elseif ($addClick)
                <flux:button
                    size="sm"
                    variant="primary"
                    icon="plus"
                    wire:click="{{ $addClick }}"
                >
                    <span class="hidden sm:inline">{{ $addLabel }}</span>
                </flux:button>
            @endif
        @endif

    </div>

    {{-- ── Mobile search bar (expandable) ─────────────────────── --}}
    @if ($showSearch)
        <div
            class="md:hidden"
            x-data="{ open: false }"
            x-show="open"
            x-transition
        >
            <flux:input
                data-search-bar
                wire:model.live.debounce.300ms="{{ $search }}"
                icon="magnifying-glass"
                placeholder="Search…"
                class="w-full"
                size="sm"
            />
        </div>
    @endif

</div>
