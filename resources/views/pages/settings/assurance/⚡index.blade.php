<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Title('Assurances'), Layout('layouts::app.other.support_tech')] class extends Component {
    //
}; ?>

<section class="w-full">
    <flux:heading class="sr-only">Gestions des assurances</flux:heading>
    <x-header_default :title="__('Assurances')" subtitle="Gestion des assurances et partenaires payeurs" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
        ['label' => 'Assurance', 'icon' => 'shield-check'],
    ]">
        <x-slot:actions>
            <x-button icon="shield-check" position="left" href="{{ route('settings.assurance.create') }}" wire:navigate>
                Nouvelle Assurance
            </x-button>
        </x-slot>
    </x-header_default>

    <livewire:assurance-table />
</section>
