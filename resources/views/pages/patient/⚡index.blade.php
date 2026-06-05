<?php
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
};
?>

<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Dossiers patients', 'icon' => 'folder'],
            ]" />
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mt-2">
                Dossiers Médicaux
            </h1>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" icon="folder-open" color="indigo">Nouveau dossier</flux:button>
        </div>
    </div>

    <livewire:patient-table />
</div>
