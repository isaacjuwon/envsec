<?php

use Livewire\Attributes\On;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Transition;
use Livewire\Component;

new #[Layout('layouts::auth')] #[Title('Welcome to EnvSec')] class extends Component {
    public string $current = 'onboarding.display-name';
    
    public array $data = [
        'display_name' => '',
        'workspace_name' => '',
        'workspace_slug' => '',
    ];

    public array $steps = [
        'onboarding.display-name',
        'onboarding.create-workspace',
        'onboarding.success',
    ];

    #[Transition(type: 'forward')]
    public function next($stepData = [])
    {
        $this->data = array_merge($this->data, $stepData);

        $currentIndex = array_search($this->current, $this->steps);
        
        if ($currentIndex !== false && isset($this->steps[$currentIndex + 1])) {
            $this->current = $this->steps[$currentIndex + 1];
        } else {
            // Final step reached
            return redirect()->route('dashboard');
        }
    }

    #[Transition(type: 'forward')]
    public function skip()
    {
        $this->current = 'onboarding.create-workspace';
    }
};

?>

<div wire:transition="content" class="flex flex-col gap-6 w-full max-w-xl mx-auto">
    <x-auth-header title="System Setup" description="Let's get your environment ready." />

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-8 shadow-sm">
        <livewire:is 
            :component="$current" 
            :data="$data" 
            :wire:key="$current" 
        />
    </div>

    {{-- Step Indicators --}}
    <div class="flex justify-center gap-2 mt-2">
        @foreach ($steps as $step)
            <div @class([
                'h-1 rounded-full transition-all duration-300',
                'w-8 bg-primary' => $current === $step,
                'w-2 bg-zinc-200 dark:bg-zinc-800' => $current !== $step
            ])></div>
        @endforeach
    </div>
</div>
