<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Carnet des vaccins')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Gestions des vaccins') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Carnet des vaccins')" :subheading="__('Gestions des vaccins')">
        <x-slot:actions>
            <x-button icon="briefcase" position="left" href="{{ route('settings.paquet.create') }}" wire:navigate>
                Nouveau vaccin
            </x-button>
        </x-slot>
        {{-- <livewire:settings.hopitaux.table_hopitaux /> --}}
    </x-pages::settings.layout>
</section>
