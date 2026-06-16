<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Apparence'), Layout('layouts::app')] class extends Component {
    //
}; ?>

<div class="mx-auto max-w-7xl space-y-6">
    <x-breadcrumbs :items="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Parametres', 'link' => 'settings.index', 'icon' => 'cog-6-tooth'],
        ['label' => 'Apparence', 'icon' => 'swatch'],
    ]" />

    <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-5 dark:border-slate-800 dark:bg-slate-900/80">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white">Apparence</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Choisissez le theme d'affichage de l'interface.
                    </p>
                </div>
                <flux:button href="{{ route('settings.index') }}" variant="ghost" size="sm" icon="arrow-left" wire:navigate>
                    Retour
                </flux:button>
            </div>
        </div>

        <div class="space-y-6 p-6 sm:p-8">
            <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="w-full max-w-lg">
                <flux:radio value="light" icon="sun">Clair</flux:radio>
                <flux:radio value="dark" icon="moon">Sombre</flux:radio>
                <flux:radio value="system" icon="computer-desktop">Systeme</flux:radio>
            </flux:radio.group>

            <p class="text-sm text-slate-500 dark:text-slate-400">
                Le mode systeme suit automatiquement les preferences de votre appareil.
            </p>
        </div>
    </section>
</div>
