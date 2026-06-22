<?php

use App\Models\Configs\Assurance;
use App\Models\Configs\Projet;
use App\Models\Consultation;
use App\Models\DossierPatient;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fiche assurance'), Layout('layouts::app.other.support_tech')] class extends Component {
    public Assurance $assurance;

    public function mount(int $id): void
    {
        $this->assurance = Assurance::query()
            ->with(['categorisation'])
            ->withCount(['projets', 'patients', 'consultations'])
            ->findOrFail($id);
    }

    #[Computed]
    public function stats(): array
    {
        $consultationBase = Consultation::query()->forAssurance($this->assurance->id);

        return [
            'projets' => (int) $this->assurance->projets_count,
            'patients' => (int) $this->assurance->patients_count,
            'consultations' => (int) (clone $consultationBase)->count(),
            'ce_mois' => (int) (clone $consultationBase)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    #[Computed]
    public function recentProjets(): Collection
    {
        return Projet::query()
            ->where('assurance_id', $this->assurance->id)
            ->latest('created_at')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function recentPatients(): Collection
    {
        return DossierPatient::query()
            ->where('assurance_id', $this->assurance->id)
            ->latest('created_at')
            ->limit(8)
            ->get();
    }
};
?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Fiche assurance</flux:heading>

    <x-header_default
        :title="$assurance->name"
        :subtitle="$assurance->reference"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
            ['label' => 'Assurances', 'link' => 'settings/assurance', 'icon' => 'shield-check'],
            ['label' => $assurance->reference, 'icon' => 'document-text'],
        ]"
    >
        <x-slot:actions>
            <x-button href="{{ route('settings.assurance.index') }}" wire:navigate>Retour a la liste</x-button>
            <x-button icon="shield-check" position="left" href="{{ route('settings.assurance.create') }}" wire:navigate>
                Nouvelle assurance
            </x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-blue-200 bg-blue-50/80 p-5 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700 dark:text-blue-300">Projets</p>
            <p class="mt-3 text-3xl font-black text-blue-900 dark:text-blue-100">{{ $this->stats['projets'] }}</p>
            <p class="mt-1 text-xs text-blue-700/80 dark:text-blue-300/80">Campagnes rattachees</p>
        </div>

        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Patients</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['patients'] }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Dossiers couverts</p>
        </div>

        <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Consultations</p>
            <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">{{ $this->stats['consultations'] }}</p>
            <p class="mt-1 text-xs text-violet-700/80 dark:text-violet-300/80">Prises en charge liees</p>
        </div>

        <div class="rounded-3xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">Ce mois</p>
            <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-100">{{ $this->stats['ce_mois'] }}</p>
            <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">Consultations du mois</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_360px]">
        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="flex flex-col gap-5 md:flex-row md:items-start">
                    <div class="shrink-0">
                        @if ($assurance->logo)
                            <img src="{{ $assurance->logoUrl() }}" alt="Logo {{ $assurance->name }}"
                                class="h-28 w-28 rounded-2xl object-cover ring-1 ring-slate-200 dark:ring-slate-700" />
                        @else
                            <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-sky-100 text-3xl font-black text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                                {{ strtoupper(mb_substr($assurance->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-black uppercase tracking-[0.25em] text-slate-400">Partenaire payeur</p>
                        <h2 class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $assurance->name }}</h2>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ $assurance->description ?: 'Aucune description n\'a encore ete renseignee pour cette assurance.' }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <flux:badge color="blue" inset>{{ ucfirst($assurance->type) }}</flux:badge>
                            @if ($assurance->email)
                                <flux:badge color="zinc" inset>{{ $assurance->email }}</flux:badge>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <flux:heading size="lg">Projets rattaches</flux:heading>
                        <flux:subheading class="mt-1">Campagnes financees par cette assurance.</flux:subheading>
                    </div>
                    <flux:badge color="sky" inset>{{ $this->stats['projets'] }}</flux:badge>
                </div>

                <div class="space-y-3">
                    @forelse ($this->recentProjets as $index => $projet)
                        <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-black text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('settings.projet.show', $projet->id) }}" wire:navigate
                                    class="font-bold text-slate-900 hover:text-sky-600 dark:text-white dark:hover:text-sky-300">
                                    {{ $projet->name }}
                                </a>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $projet->reference ?: '—' }}
                                    · {{ optional($projet->created_at)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Aucun projet n'est encore rattache a cette assurance.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <flux:heading size="lg">Patients recents</flux:heading>
                        <flux:subheading class="mt-1">Dossiers utilisant cette assurance.</flux:subheading>
                    </div>
                    <flux:badge color="emerald" inset>{{ $this->stats['patients'] }}</flux:badge>
                </div>

                <div class="space-y-3">
                    @forelse ($this->recentPatients as $index => $patient)
                        <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-black text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('patient.show', $patient->id) }}" wire:navigate
                                    class="font-bold text-slate-900 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-300">
                                    {{ $patient->full_name }}
                                </a>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    NIN: {{ $patient->nin ?: '—' }}
                                    · {{ optional($patient->created_at)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Aucun patient n'utilise encore cette assurance.
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
                        <p class="text-xs text-slate-500 dark:text-slate-400">Reference</p>
                        <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ $assurance->reference }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Cree le</p>
                        <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ optional($assurance->created_at)->format('d/m/Y H:i') ?: '—' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Derniere mise a jour</p>
                        <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ optional($assurance->updated_at)->format('d/m/Y H:i') ?: '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-6 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                <flux:heading size="lg">Categorisation</flux:heading>

                @if ($assurance->categorisation)
                    <div class="mt-5 space-y-4">
                        <div>
                            <p class="text-xs text-emerald-700/80 dark:text-emerald-300/80">Nom</p>
                            <p class="mt-1 font-bold text-emerald-950 dark:text-emerald-100">{{ $assurance->categorisation->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-emerald-700/80 dark:text-emerald-300/80">Prise en charge</p>
                            <p class="mt-1 text-2xl font-black text-emerald-900 dark:text-emerald-100">
                                {{ $assurance->categorisation->pourcentage }}%
                            </p>
                        </div>
                        @if ($assurance->categorisation->description)
                            <p class="text-sm leading-6 text-emerald-900/80 dark:text-emerald-100/80">
                                {{ $assurance->categorisation->description }}
                            </p>
                        @endif
                        <a href="{{ route('settings.categorisation.show', $assurance->categorisation->id) }}" wire:navigate
                            class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 bg-white px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                            Voir la categorisation
                        </a>
                    </div>
                @else
                    <p class="mt-4 text-sm text-amber-800 dark:text-amber-200">
                        Aucune categorisation n'est liee a cette assurance.
                    </p>
                @endif
            </div>
        </aside>
    </div>
</section>
