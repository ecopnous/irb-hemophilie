<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Title('Hopitaux'), Layout('layouts::app.other.support_tech')] class extends Component {
    //
}; ?>

<section class="w-full">
    <flux:heading class="sr-only">{{ __('Gestions des hopitaux') }}</flux:heading>

    <x-header_default :title="__('Hopitaux')" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
        ['label' => 'Hopitaux', 'icon' => 'building-office'],
    ]">
        <x-slot:actions>
            <x-button icon="building-office" position="left" href="{{ route('settings.hopital.create') }}" wire:navigate>
                Nouvel hopital
            </x-button>
        </x-slot>
    </x-header_default>

    <livewire:hopital-table />

    {{-- <x-pages::settings.layout :heading="__('Hopitaux')" :subheading="__('Gestions des hopitaux')">
        <x-slot:actions>
                <x-button icon="building-office" position="left" href="{{ route('settings.hopital.create') }}"
                    wire:navigate>Nouvelle
                    Hopital</x-button>
        </x-slot>
        <livewire:settings.hopitaux.table_hopitaux />
    </x-pages::settings.layout> --}}
</section>
