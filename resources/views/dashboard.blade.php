<x-layouts::app :title="__('Dashboard')">
    <div wire:transition="content" class="flex h-full w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">Dashboard</flux:heading>
            <flux:subheading>Welcome back to Envsec!</flux:subheading>
        </div>

        <livewire:dashboard-stats />
        
        <div class="flex flex-1 gap-6 flex-col md:flex-row">
            <livewire:recent-projects />
            
            <flux:card class="w-full md:w-1/3 h-fit relative">
                <flux:heading size="lg" class="mb-4">Quick Actions</flux:heading>
                <div class="space-y-3">
                    <flux:button class="w-full justify-start" icon="plus" :href="route('projects.create')" wire:navigate>Create New Project</flux:button>
                    <flux:button class="w-full justify-start" icon="cog" :href="route('tenant.settings')" wire:navigate>Tenant Settings</flux:button>
                    <flux:button class="w-full justify-start" icon="cog" :href="route('profile.edit')" wire:navigate>User Settings</flux:button>
                    <flux:button class="w-full justify-start mt-2" variant="ghost" icon="book-open" href="https://laravel.com/docs" target="_blank">Documentation</flux:button>
                </div>
            </flux:card>
        </div>
    </div>
</x-layouts::app>
