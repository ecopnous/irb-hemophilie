<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Title('Projets et campagnes'), Layout('layouts::app.other.support_tech')] class extends Component {
    //
}; ?>

<section class="w-full">
    <flux:heading class="sr-only">{{ __('Gestions des projets et campagnes') }}</flux:heading>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Support technique', 'icon' => 'cog-6-tooth'],
            ]" />
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mt-2">
                Projets et campagnes
            </h1>
        </div>
        <x-button icon="clipboard-document-check" position="left" href="{{ route('settings.projet.create') }}"
            wire:navigate>
            Nouveau projet
        </x-button>
    </div>

    {{-- <x-pages::settings.layout :heading="__('Projets et campagnes')" :subheading="__('Gestions des projets et campagnes')">
        <x-slot:actions>
            
        </x-slot>
    </x-pages::settings.layout> --}}
    <livewire:projet-table />
</section>
