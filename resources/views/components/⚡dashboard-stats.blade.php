<?php

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Component;

new #[Defer] class extends Component {
    #[Computed]
    public function stats()
    {
        $workspace = Workspace::current();
        
        $projectCount = $workspace ? Project::where('workspace_id', $workspace->id)->count() : 0;
        
        return [
            ['title' => 'Total Projects', 'value' => $projectCount, 'icon' => 'folder'],
            ['title' => 'Active Secrets', 'value' => '--', 'icon' => 'key'],
            ['title' => 'API Requests', 'value' => '0', 'icon' => 'chart-bar'],
        ];
    }
}
?>

<div wire:transition class="grid auto-rows-min gap-4 md:grid-cols-3">
    @foreach($this->stats as $stat)
        <flux:card>
            <div class="flex items-center gap-4">
                <div class="rounded-lg bg-zinc-100 p-3 dark:bg-zinc-800">
                    <flux:icon :icon="$stat['icon']" class="size-6 text-zinc-500 dark:text-zinc-400" />
                </div>
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500">{{ $stat['title'] }}</flux:text>
                    <flux:heading size="xl">{{ $stat['value'] }}</flux:heading>
                </div>
            </div>
        </flux:card>
    @endforeach
</div>
