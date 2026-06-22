<?php

use App\Models\Configs\Assurance;
use App\Models\Configs\Projet;
use App\Models\Consultation;
use App\Services\AssuranceInvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fiche facturation assurance'), Layout('layouts::app.other.facturation')] class extends Component {
    public Assurance $assurance;

    public string $periodMonth;

    public function mount(int $id): void
    {
        $this->assurance = Assurance::query()
            ->with(['categorisation'])
            ->withCount(['projets', 'patients'])
            ->findOrFail($id);

        $this->periodMonth = now()->format('Y-m');
    }

    #[Computed]
    public function period(): array
    {
        $start = Carbon::createFromFormat('Y-m', $this->periodMonth)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [
            'start' => $start,
            'end' => $end,
            'label' => $start->translatedFormat('F Y'),
        ];
    }

    #[Computed]
    public function invoice(): array
    {
        return app(AssuranceInvoiceService::class)->build(
            $this->assurance,
            current_hopital_id(),
            $this->period['start'],
            $this->period['end'],
        );
    }

    #[Computed]
    public function projets(): Collection
    {
        return Projet::query()
            ->where('assurance_id', $this->assurance->id)
            ->withCount([
                'consultations' => fn ($query) => $query
                    ->whereHopitalId(current_hopital_id())
                    ->whereBetween('created_at', [$this->period['start'], $this->period['end']])
                    ->whereHas('actes'),
            ])
            ->latest('created_at')
            ->get();
    }

    #[Computed]
    public function recentConsultations(): Collection
    {
        return Consultation::query()
            ->with(['dossierPatient', 'projet.assurance', 'departement', 'actes'])
            ->forAssurance($this->assurance->id)
            ->whereHopitalId(current_hopital_id())
            ->whereBetween('created_at', [$this->period['start'], $this->period['end']])
            ->whereHas('actes')
            ->latest('created_at')
            ->limit(10)
            ->get();
    }

    protected function consultationAmount(Consultation $consultation): float
    {
        return (float) $consultation->actes->sum(fn ($acte) => (float) ($acte->pivot->montant ?? $acte->montant ?? 0));
    }
};
?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Fiche facturation assurance</flux:heading>

    <x-header_default
        :title="$assurance->name"
        :subtitle="'Facturation assurance | ' . $assurance->reference"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Facturation', 'link' => 'facturation', 'icon' => 'document-text'],
            ['label' => 'Assurances', 'link' => 'facturation/assurance', 'icon' => 'shield-check'],
            ['label' => $assurance->reference, 'icon' => 'document-text'],
        ]"
    >
        <x-slot:actions>
            <x-button href="{{ route('facturation.assurance.index') }}" wire:navigate>Retour</x-button>
            <x-button icon="document-text" position="left"
                href="{{ route('facturation.assurance.invoice', ['id' => $assurance->id, 'month' => $periodMonth]) }}"
                wire:navigate>
                Voir facture complete
            </x-button>
        </x-slot>
    </x-header_default>

    <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <label class="text-sm font-semibold text-slate-600 dark:text-slate-300">Periode</label>
        <input type="month" wire:model.live="periodMonth"
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
        <flux:badge color="zinc" inset>{{ $this->period['label'] }}</flux:badge>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-blue-200 bg-blue-50/80 p-5 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700 dark:text-blue-300">Patients</p>
            <p class="mt-3 text-3xl font-black text-blue-900 dark:text-blue-100">{{ $this->invoice['meta']['patients_count'] }}</p>
            <p class="mt-1 text-xs text-blue-700/80 dark:text-blue-300/80">Sur la periode</p>
        </div>
        <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Consultations</p>
            <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">{{ $this->invoice['meta']['consultations_count'] }}</p>
            <p class="mt-1 text-xs text-violet-700/80 dark:text-violet-300/80">Avec actes facturables</p>
        </div>
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Total general</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ number_format($this->invoice['totals']['general'], 2, ',', ' ') }} $</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Montant des prestations</p>
        </div>
        <div class="rounded-3xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">A payer assurance</p>
            <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-100">{{ number_format($this->invoice['totals']['a_payer'], 2, ',', ' ') }} $</p>
            <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">Montant a facturer</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_360px]">
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
                            {{ $assurance->description ?: 'Aucune description renseignee pour cette assurance.' }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <flux:badge color="blue" inset>{{ ucfirst($assurance->type) }}</flux:badge>
                            @if ($assurance->categorisation)
                                <flux:badge color="emerald" inset>{{ $assurance->categorisation->name }} ({{ $assurance->categorisation->pourcentage }}%)</flux:badge>
                            @endif
                            <flux:badge :color="$assurance->forfait_actif ? 'emerald' : 'zinc'" inset>
                                Forfait {{ $assurance->forfait_actif ? 'actif' : 'inactif' }}
                            </flux:badge>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Consultations recentes</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Dernieres prestations sur la periode selectionnee</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-black uppercase tracking-[0.16em] text-slate-400 dark:border-slate-800">
                                <th class="px-3 py-3">Reference</th>
                                <th class="px-3 py-3">Patient</th>
                                <th class="px-3 py-3">Projet</th>
                                <th class="px-3 py-3 text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->recentConsultations as $consultation)
                                @php
                                    $patient = $consultation->dossierPatient;
                                    $patientName = trim(implode(' ', array_filter([
                                        strtoupper((string) $patient?->nom),
                                        strtoupper((string) $patient?->postnom),
                                        ucfirst((string) $patient?->prenom),
                                    ])));
                                @endphp
                                <tr class="border-b border-slate-100 dark:border-slate-800">
                                    <td class="px-3 py-3 font-mono text-xs">{{ $consultation->reference }}</td>
                                    <td class="px-3 py-3">{{ $patientName ?: '—' }}</td>
                                    <td class="px-3 py-3">{{ $consultation->projet?->name ?? 'Sans projet' }}</td>
                                    <td class="px-3 py-3 text-right font-semibold">{{ number_format($this->consultationAmount($consultation), 2, ',', ' ') }} $</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-8 text-center text-slate-500 dark:text-slate-400">Aucune consultation sur cette periode.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <h3 class="text-lg font-black text-slate-900 dark:text-white">Parametres forfait</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Forfait disponible</dt>
                        <dd class="font-semibold">{{ $assurance->forfait_actif ? 'OUI' : 'NON' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Prix par patient</dt>
                        <dd class="font-semibold">{{ number_format((float) ($assurance->prix_patient ?? 0), 2, ',', ' ') }} $</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Estimation forfait</dt>
                        <dd class="font-semibold">{{ number_format($this->invoice['meta']['estimation_forfait'], 2, ',', ' ') }} $</dd>
                    </div>
                </dl>
                <div class="mt-5">
                    <x-button class="w-full justify-center"
                        href="{{ route('facturation.assurance.invoice', ['id' => $assurance->id, 'month' => $periodMonth]) }}"
                        wire:navigate>
                        Modifier forfait / Voir facture
                    </x-button>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <h3 class="text-lg font-black text-slate-900 dark:text-white">Projets rattaches</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($this->projets as $projet)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                            <p class="font-bold text-slate-900 dark:text-white">{{ $projet->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $projet->reference }}</p>
                            <p class="mt-2 text-sm font-semibold text-sky-700 dark:text-sky-300">{{ $projet->consultations_count }} consultation(s)</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">Aucun projet rattache.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
