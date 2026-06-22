<?php

use App\Models\Configs\Assurance;
use App\Models\Consultation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Factures assurance'), Layout('layouts::app.other.facturation')] class extends Component {
    #[Computed]
    public function stats(): array
    {
        $hopitalId = current_hopital_id();
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $assurances = Assurance::query()
            ->where(function ($query) {
                $query->where('is_delete', false)->orWhereNull('is_delete');
            })
            ->count();

        $consultations = Consultation::query()
            ->whereHopitalId($hopitalId)
            ->where(function ($query) {
                $query->whereNotNull('assurance_id')
                    ->orWhereHas('projet', fn ($projetQuery) => $projetQuery->whereNotNull('assurance_id'));
            })
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('actes')
            ->count();

        $montant = 0.0;
        Consultation::query()
            ->whereHopitalId($hopitalId)
            ->where(function ($query) {
                $query->whereNotNull('assurance_id')
                    ->orWhereHas('projet', fn ($projetQuery) => $projetQuery->whereNotNull('assurance_id'));
            })
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('actes')
            ->with('actes')
            ->get()
            ->each(function ($consultation) use (&$montant) {
                foreach ($consultation->actes as $acte) {
                    $montant += (float) ($acte->pivot->montant ?? $acte->montant ?? 0);
                }
            });

        $actives = Assurance::query()
            ->where(function ($query) {
                $query->where('is_delete', false)->orWhereNull('is_delete');
            })
            ->where(function ($query) use ($hopitalId, $start, $end) {
                $query->whereHas('consultations', function ($consultationQuery) use ($hopitalId, $start, $end) {
                    $consultationQuery->whereHopitalId($hopitalId)
                        ->whereBetween('consultations.created_at', [$start, $end])
                        ->whereHas('actes');
                })->orWhereHas('projetConsultations', function ($consultationQuery) use ($hopitalId, $start, $end) {
                    $consultationQuery->whereHopitalId($hopitalId)
                        ->whereBetween('consultations.created_at', [$start, $end])
                        ->whereHas('actes');
                });
            })
            ->count();

        return [
            'assurances' => $assurances,
            'actives' => $actives,
            'consultations' => $consultations,
            'montant' => round($montant, 2),
        ];
    }
};
?>

<section class="w-full space-y-6 p-4 md:p-6">
    <flux:heading class="sr-only">Factures assurance</flux:heading>

    <x-header_default
        title="Factures assurance"
        subtitle="Facturation mensuelle par partenaire payeur"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Facturation', 'link' => 'facturation', 'icon' => 'document-text'],
            ['label' => 'Assurances', 'icon' => 'shield-check'],
        ]"
    >
        <x-slot:actions>
            <x-button href="{{ route('facturation.index') }}" wire:navigate>Factures clinique</x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Assurances</p>
            <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->stats['assurances'] }}</p>
            <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">Partenaires enregistres</p>
        </div>

        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Actives ce mois</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['actives'] }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Avec prestations facturees</p>
        </div>

        <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Consultations</p>
            <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">{{ $this->stats['consultations'] }}</p>
            <p class="mt-1 text-xs text-violet-700/80 dark:text-violet-300/80">Prises en charge du mois</p>
        </div>

        <div class="rounded-3xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">Montant du mois</p>
            <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-100">{{ number_format($this->stats['montant'], 2, ',', ' ') }} $</p>
            <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">Volume facturable assurance</p>
        </div>
    </div>

    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-black text-slate-900 dark:text-white">Liste des assurances</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Consultez la fiche detaillee ou ouvrez la facture mensuelle complete de chaque partenaire.
            </p>
        </div>
        <flux:badge color="blue" inset>{{ now()->translatedFormat('F Y') }}</flux:badge>
    </div>

    <livewire:assurance-facturation-table />
</section>
