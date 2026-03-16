<?php

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Component;

new #[Defer] class extends Component {
    #[Computed]
    public function projects()
    {
        $workspace = Workspace::current();
        
        if (!$workspace) {
            return collect([]);
        }

        return Project::where('workspace_id', $workspace->id)
            ->latest()
            ->take(5)
            ->get();
    }
}
?>

<flux:card wire:transition class="flex-1 w-full relative">
    <flux:heading size="lg" class="mb-4">Recent Projects</flux:heading>
    
    @if($this->projects->isEmpty())
        <div class="py-8 text-center text-zinc-500">
            No projects found.
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->projects as $project)
                <div class="flex items-center justify-between border-b border-zinc-200 py-3 last:border-0 dark:border-zinc-700">
                    <div>
                        <div class="font-medium">{{ $project->name }}</div>
                        <div class="text-sm text-zinc-500">{{ $project->slug }}</div>
                    </div>
                    <flux:button size="sm" variant="ghost" icon="chevron-right" :href="route('projects.secrets.index', $project)" wire:navigate />
                </div>
            @endforeach
        </div>
    @endif
</flux:card>
