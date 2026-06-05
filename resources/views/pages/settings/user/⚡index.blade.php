<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Title('Corps medical'), Layout('layouts::app.other.support_tech')] class extends Component {
    //
}; ?>

<section class="w-full">
    <flux:heading class="sr-only">{{ __('Gestions des utilisateurs') }}</flux:heading>
    <x-header_default :title="__('Corps medicals')" subtitle="Gestion des utilisateurs de l'hopital" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
        ['label' => 'Hopitaux', 'icon' => 'building-office'],
    ]">
        <x-slot:actions>
            <x-button icon="user-plus" position="left" href="{{ route('settings.user.create') }}" wire:navigate>
                Nouvel utilisateur
            </x-button>
        </x-slot>
    </x-header_default>

    <livewire:user-table />
</section>
