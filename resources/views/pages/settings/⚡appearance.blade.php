<?php

use Livewire\Attributes\Transition;
use Livewire\Component;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-pages::settings.layout wire:transition="content" :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        
     <flux:callout variant="info" icon="sparkles" class="mb-6">
            {{ __('Choose a theme that matches your preference. Your choice will be saved locally to this browser.') }}
        </flux:callout>
    <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
        </flux:radio.group>
    </x-pages::settings.layout>
</section>
