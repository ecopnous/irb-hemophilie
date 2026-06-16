<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Title('Corps medical'), Layout('layouts::app.other.support_tech')] class extends Component {
    //
}; ?>

<section class="max-w-7xl space-y-6">
    <flux:heading class="sr-only">{{ __('Gestions des utilisateurs') }}</flux:heading>
    <x-header_default :title="__('Corps medical')" subtitle="Gestion du personnel de l'hopital actif" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Support technique', 'link' => 'settings.hopital.index', 'icon' => 'cog-6-tooth'],
        ['label' => 'Corps medical', 'icon' => 'users'],
    ]">
        <x-slot:actions>
            <x-button icon="user-plus" position="left" href="{{ route('settings.user.create') }}" wire:navigate>
                Nouvel utilisateur
            </x-button>
        </x-slot>
    </x-header_default>


    <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
            <h2 class="text-base font-black text-slate-900 dark:text-white">Liste des utilisateurs</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Recherchez un membre puis ouvrez sa fiche avec l'action <strong>Voir détail</strong>.
            </p>
        </div>
        <div class="p-4 sm:p-5">
            <livewire:user-table />
        </div>
    </section>
</section>
