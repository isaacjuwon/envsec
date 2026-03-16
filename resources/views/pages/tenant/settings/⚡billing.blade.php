<?php

use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Transition;
use Livewire\Component;

new class extends Component {
    public Workspace $workspace;
    public string $plan = 'pro'; // default for demo

    public function mount()
    {
        $this->workspace = Auth::user()->ownedWorkspaces()->first();

        if (!$this->workspace) {
            $this->redirectRoute('dashboard');
            return;
        }
    }

    #[Transition]
    public function updatePlan(string $newPlan): void
    {
        $this->plan = $newPlan;
        $this->dispatch('plan-updated');
    }
};

?>

<section>
    <x-pages::tenant.settings.layout wire:transition="content">
        <x-slot:heading>{{ __('Tenant Billing') }}</x-slot:heading>
        <x-slot:heading_description>{{ __('Manage your subscription plans and payment methods.') }}</x-slot:heading_description>

        <div class="mt-8 space-y-10">
            <!-- Current Plan -->
            <div class="p-6 bg-zinc-50 border border-zinc-200 rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <flux:heading size="sm">{{ __('Current Plan') }}</flux:heading>
                        <flux:subheading>{{ __('You are currently on the') }} <span
                                class="font-bold text-zinc-900">{{ ucfirst($plan) }}</span> {{ __('plan') }}
                        </flux:subheading>
                    </div>
                    <flux:badge color="blue" size="sm" inset="top">{{ __('Active') }}</flux:badge>
                </div>

                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-bold text-zinc-900">$29</span>
                    <span class="text-sm text-zinc-500">/{{ __('month') }}</span>
                </div>

                <div class="mt-6 flex gap-3">
                    <flux:button variant="primary" size="sm">{{ __('Manage Subscription') }}</flux:button>
                    <flux:button variant="ghost" size="sm">{{ __('View Invoices') }}</flux:button>
                </div>
            </div>

            <!-- Available Plans -->
            <div class="space-y-4">
                <flux:heading size="sm">{{ __('Change Plan') }}</flux:heading>

                <div class="grid grid-cols-1 gap-4">
                    <!-- Starter -->
                    <div
                        class="p-4 border {{ $plan === 'starter' ? 'border-zinc-900 bg-white shadow-sm' : 'border-zinc-200' }} rounded-lg flex items-center justify-between transition-all">
                        <div class="flex items-center gap-4">
                            <div class="p-2 bg-zinc-100 rounded text-zinc-600">
                                <flux:icon icon="rocket" class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="font-medium text-sm text-zinc-900">{{ __('Starter') }}</div>
                                <div class="text-xs text-zinc-500">{{ __('Perfect for small teams and individuals.') }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <span class="font-bold text-zinc-900 text-sm">$0</span>
                                <div class="text-[10px] text-zinc-400 uppercase tracking-wider font-semibold">
                                    {{ __('Free Forever') }}</div>
                            </div>
                            <flux:button wire:click="updatePlan('starter')"
                                variant="{{ $plan === 'starter' ? 'filled' : 'ghost' }}" size="sm"
                                :disabled="$plan === 'starter'">
                                {{ $plan === 'starter' ? __('Selected') : __('Switch') }}
                            </flux:button>
                        </div>
                    </div>

                    <!-- Pro -->
                    <div
                        class="p-4 border {{ $plan === 'pro' ? 'border-zinc-900 bg-white shadow-sm' : 'border-zinc-200' }} rounded-lg flex items-center justify-between transition-all">
                        <div class="flex items-center gap-4">
                            <div class="p-2 bg-blue-50 rounded text-blue-600">
                                <flux:icon icon="bolt" class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="font-medium text-sm text-zinc-900">{{ __('Professional') }}</div>
                                <div class="text-xs text-zinc-500">{{ __('Advanced features for growing businesses.') }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <span class="font-bold text-zinc-900 text-sm">$29</span>
                                <div class="text-[10px] text-zinc-400 uppercase tracking-wider font-semibold">
                                    {{ __('Per Month') }}</div>
                            </div>
                            <flux:button wire:click="updatePlan('pro')"
                                variant="{{ $plan === 'pro' ? 'filled' : 'ghost' }}" size="sm"
                                :disabled="$plan === 'pro'">
                                {{ $plan === 'pro' ? __('Selected') : __('Switch') }}
                            </flux:button>
                        </div>
                    </div>

                    <!-- Enterprise -->
                    <div
                        class="p-4 border {{ $plan === 'enterprise' ? 'border-zinc-900 bg-white shadow-sm' : 'border-zinc-200' }} rounded-lg flex items-center justify-between transition-all">
                        <div class="flex items-center gap-4">
                            <div class="p-2 bg-purple-50 rounded text-purple-600">
                                <flux:icon icon="building-office-2" class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="font-medium text-sm text-zinc-900">{{ __('Enterprise') }}</div>
                                <div class="text-xs text-zinc-500">
                                    {{ __('Scale without limits with dedicated support.') }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right font-medium text-sm text-zinc-900">
                                {{ __('Custom') }}
                            </div>
                            <flux:button variant="ghost" size="sm">{{ __('Contact Sales') }}</flux:button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="space-y-4 pt-6 border-t border-zinc-200">
                <flux:heading size="sm">{{ __('Payment Methods') }}</flux:heading>

                <div class="flex items-center justify-between p-4 bg-white border border-zinc-200 rounded-lg">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-zinc-100 rounded text-zinc-600">
                            <flux:icon icon="credit-card" class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="font-medium text-sm text-zinc-900">Visa ending in 4242</div>
                            <div class="text-xs text-zinc-500">Expires 12/2026</div>
                        </div>
                    </div>
                    <flux:button variant="ghost" size="sm">{{ __('Edit') }}</flux:button>
                </div>
            </div>
        </div>
    </x-pages::tenant.settings.layout>
</section>