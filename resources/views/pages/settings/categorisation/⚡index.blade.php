<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Title('Categorisation'), Layout('layouts::app.other.support_tech')] class extends Component {
    //
};
?>

<section class="w-full">
    <flux:heading class="sr-only">{{ __('Gestion de categories') }}</flux:heading>
    <x-header_default :title="__('Categorisation')" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
        ['label' => 'categorisation', 'icon' => 'squares-plus'],
    ]">
        <x-slot:actions>
            <x-button icon="squares-plus" position="left" href="{{ route('settings.categorisation.create') }}"
                wire:navigate>Nouvelle Categorie</x-button>
        </x-slot>
    </x-header_default>

    <livewire:categorisation-table />
</section>
