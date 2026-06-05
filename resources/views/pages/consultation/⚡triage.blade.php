<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Triage')] class extends Component {};
?>

<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Triage', 'icon' => 'inbox'],
            ]" />
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mt-2">
                Triage
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Consultations en attente de prelevement et d'orientation.
            </p>
        </div>
        <div
            class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="font-semibold text-slate-900 dark:text-white">Triage Total :
                {{ 0 }}</p>
        </div>
    </div>

    <livewire:triage-table />
</div>
