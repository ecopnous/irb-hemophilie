<?php

use App\Models\Configs\Projet;
use App\Models\Consultation;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fiche projet'), Layout('layouts::app.other.support_tech')] class extends Component {
    public Projet $projet;

    public function mount(int $id): void
    {
        $this->projet = Projet::query()
            ->with(['assurance.categorisation'])
            ->withCount('consultations')
            ->findOrFail($id);
    }

    #[Computed]
    public function stats(): array
    {
        $base = Consultation::query()->where('projet_id', $this->projet->id);

        return [
            'consultations' => (int) $this->projet->consultations_count,
            'patients' => (int) (clone $base)->distinct('dossier_patient_id')->count('dossier_patient_id'),
            'ce_mois' => (int) (clone $base)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'aujourd_hui' => (int) (clone $base)->whereDate('created_at', today())->count(),
        ];
    }

    #[Computed]
    public function recentConsultations(): Collection
    {
        return Consultation::query()
            ->with(['dossierPatient', 'user', 'departement'])
            ->where('projet_id', $this->projet->id)
            ->latest('created_at')
            ->limit(12)
            ->get();
    }
};
?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Fiche projet</flux:heading>

    <x-header_default
        :title="$projet->name"
        :subtitle="$projet->reference ?: 'Projet ou campagne de sante'"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
            ['label' => 'Projets', 'link' => 'settings/projet', 'icon' => 'clipboard-document-check'],
            ['label' => $projet->reference ?: 'Detail', 'icon' => 'document-text'],
        ]"
    >
        <x-slot:actions>
            <x-button href="{{ route('settings.projet.index') }}" wire:navigate>Retour a la liste</x-button>
            <x-button icon="clipboard-document-check" position="left" href="{{ route('settings.projet.create') }}" wire:navigate>
                Nouveau projet
            </x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Consultations</p>
            <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->stats['consultations'] }}</p>
            <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">Total rattache au projet</p>
        </div>

        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Patients</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['patients'] }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Dossiers distincts</p>
        </div>

        <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Ce mois</p>
            <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">{{ $this->stats['ce_mois'] }}</p>
            <p class="mt-1 text-xs text-violet-700/80 dark:text-violet-300/80">Activite du mois en cours</p>
        </div>

        <div class="rounded-3xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">Aujourd'hui</p>
            <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-100">{{ $this->stats['aujourd_hui'] }}</p>
            <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">Consultations du jour</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_360px]">
        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.25em] text-slate-400">Projet / Campagne</p>
                        <h2 class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $projet->name }}</h2>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ $projet->description ?: 'Aucune description n\'a encore ete renseignee pour ce projet.' }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm dark:border-sky-500/30 dark:bg-sky-500/10">
                        <p class="text-sky-700 dark:text-sky-300">Reference</p>
                        <p class="mt-1 font-bold text-sky-900 dark:text-sky-100">{{ $projet->reference ?: '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <flux:heading size="lg">Consultations recentes</flux:heading>
                        <flux:subheading class="mt-1">Dernieres prises en charge rattachees a ce projet.</flux:subheading>
                    </div>
                    <flux:badge color="sky" inset>{{ $this->stats['consultations'] }} total</flux:badge>
                </div>

                <div class="space-y-3">
                    @forelse ($this->recentConsultations as $index => $consultation)
                        <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-black text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('consultation.show', $consultation->id) }}" wire:navigate
                                    class="font-bold text-slate-900 hover:text-sky-600 dark:text-white dark:hover:text-sky-300">
                                    {{ $consultation->dossierPatient?->full_name ?? 'Patient inconnu' }}
                                </a>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $consultation->reference }}
                                    · {{ $consultation->departement?->name ?? 'Departement non defini' }}
                                    · {{ optional($consultation->created_at)->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <p class="hidden text-xs font-semibold text-slate-500 dark:text-slate-400 sm:block">
                                {{ $consultation->user?->name ?? 'Non assigne' }}
                            </p>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Aucune consultation n'est encore rattachee a ce projet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <flux:heading size="lg">Synthese</flux:heading>
                <div class="mt-5 grid gap-3 text-sm">
                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Identifiant</p>
                        <p class="mt-1 font-bold text-slate-900 dark:text-white">#{{ $projet->id }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Cree le</p>
                        <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ optional($projet->created_at)->format('d/m/Y H:i') ?: '—' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Derniere mise a jour</p>
                        <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ optional($projet->updated_at)->format('d/m/Y H:i') ?: '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-6 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                <flux:heading size="lg">Assurance porteuse</flux:heading>

                @if ($projet->assurance)
                    <div class="mt-5 space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-emerald-700/80 dark:text-emerald-300/80">Nom</p>
                            <p class="mt-1 font-bold text-emerald-950 dark:text-emerald-100">{{ $projet->assurance->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-emerald-700/80 dark:text-emerald-300/80">Reference</p>
                            <p class="mt-1 font-semibold text-emerald-900 dark:text-emerald-100">{{ $projet->assurance->reference ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-emerald-700/80 dark:text-emerald-300/80">Type</p>
                            <p class="mt-1 font-semibold capitalize text-emerald-900 dark:text-emerald-100">{{ $projet->assurance->type }}</p>
                        </div>
                        @if ($projet->assurance->categorisation)
                            <div>
                                <p class="text-xs text-emerald-700/80 dark:text-emerald-300/80">Categorisation</p>
                                <p class="mt-1 font-semibold text-emerald-900 dark:text-emerald-100">{{ $projet->assurance->categorisation->name }}</p>
                            </div>
                        @endif
                        <a href="{{ route('settings.assurance.show', $projet->assurance->id) }}" wire:navigate
                            class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 bg-white px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                            Voir l'assurance
                        </a>
                    </div>
                @else
                    <p class="mt-4 text-sm text-amber-800 dark:text-amber-200">
                        Aucune assurance n'est liee a ce projet.
                    </p>
                @endif
            </div>
        </aside>
    </div>
</section>
