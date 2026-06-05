<?php

use App\Models\Consultation;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Consultations')] class extends Component {
    #[Computed]
    public function stats(): array
    {
        $base = Consultation::query()->whereHopitalId(current_hopital_id());

        return [
            'programmees' => (clone $base)->programmed()->count(),
            'actives' => (clone $base)
                ->notProgrammed()
                ->where(function ($query) {
                    $query->whereNotNull('user_id')->orWhere('type', 'depistage');
                })
                ->count(),
            'aujourd_hui' => (clone $base)->notProgrammed()->whereDate('created_at', Carbon::today())->count(),
        ];
    }
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

        <div class="grid gap-3 sm:grid-cols-3">
            <div
                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Actives</p>
                <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ $this->stats['actives'] }}</p>
            </div>

            <div
                class="rounded-2xl border border-blue-200 bg-blue-50/80 px-4 py-3 text-sm shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700 dark:text-blue-300">
                    Programmees
                </p>
                <p class="mt-2 text-2xl font-black text-blue-900 dark:text-blue-100">
                    {{ $this->stats['programmees'] }}
                </p>
            </div>

            <div
                class="rounded-2xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 text-sm shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">
                    Aujourd'hui
                </p>
                <p class="mt-2 text-2xl font-black text-emerald-900 dark:text-emerald-100">
                    {{ $this->stats['aujourd_hui'] }}
                </p>
            </div>
        </div>
    </div>

    <livewire:consultation-table />
</div>
