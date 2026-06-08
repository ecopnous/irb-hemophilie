<?php

use App\Models\Consultation;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Consultations')] class extends Component {
   
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="space-y-2">
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Consultation', 'icon' => 'clipboard-document-check'],
            ]" />
            <div class="space-y-3">
                <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                    Registre des consultations
                </h1>
            </div>
        </div>

        <livewire:consultation-import-manager />
    </div>

    <livewire:consultation-table />
</div>
