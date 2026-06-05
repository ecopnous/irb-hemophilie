<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Groupes d hopitaux')] class extends Component {};
?>

<section class="w-full space-y-6">
    <x-header_default :title="__('Groupes d hopitaux')" :subtitle="__(
        'Pilotez les reseaux d etablissements, leurs objectifs et les hopitaux rattaches.',
    )" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Groupes d hopitaux', 'icon' => 'building-office-2'],
    ]">
        <x-slot:actions>
            <x-button icon="plus" position="left" href="{{ route('groupe_hopitaux.create') }}" wire:navigate>
                Nouveau groupe
            </x-button>
        </x-slot>
    </x-header_default>

    <div class="px-4 pb-10 sm:px-6 lg:px-8">
        <div
            class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Repertoire</p>
                    <h2 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Tous les groupes</h2>
                </div>
                <flux:badge color="sky" inset>PowerGrid</flux:badge>
            </div>

            <livewire:groupe-hopital-table />
        </div>
    </div>
</section>
