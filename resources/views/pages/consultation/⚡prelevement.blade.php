<?php

use App\Models\Consultation;
use Flux\Flux;
use Livewire\Component;

new class extends Component {
    public Consultation $consultation;

    public $poids;
    public $temperature;
    public $taille;
    public $systolite;
    public $perimetre_cranien;
    public $perimetre_brachial;
    public $frequence_cardiaque;
    public $frequence_respiratoire;
    public $diastolique;
    public $saturation_oxygene;
    public $glycemie;
    public $mois;

    public function mount($id): void
    {
        $this->consultation = Consultation::query()
            ->with(['user.departement', 'dossierPatient', 'departement'])
            ->findOrFail($id);

        $this->poids = $this->consultation->poids;
        $this->temperature = $this->consultation->temperature;
        $this->taille = $this->consultation->taille;
        $this->systolite = $this->consultation->systolite;
        $this->perimetre_cranien = $this->consultation->perimetre_cranien;
        $this->perimetre_brachial = $this->consultation->perimetre_brachial;
        $this->frequence_cardiaque = $this->consultation->frequence_cardiaque;
        $this->frequence_respiratoire = $this->consultation->frequence_respiratoire;
        $this->diastolique = $this->consultation->diastolique;
        $this->saturation_oxygene = $this->consultation->saturation_oxygene;
        $this->glycemie = $this->consultation->glycemie;
        $this->mois = $this->consultation->mois;
    }

    public function assignerMedecin(mixed $userId): void
    {
        if (is_array($userId)) {
            $userId = $userId['id'] ?? ($userId['value'] ?? 0);
        }

        $userId = (int) $userId;
        if ($userId < 1) {
            Flux::toast(variant: 'danger', heading: 'Assignation impossible', text: 'Sélection invalide. Réessayez depuis la liste des médecins.');

            return;
        }

        $this->consultation->update(['user_id' => $userId]);
        $this->consultation->refresh()->load(['user.departement', 'dossierPatient', 'departement']);

        Flux::toast(
            variant: 'success',
            heading: 'Médecin assigné',
            text: 'Dr ' . ($this->consultation->user?->name ?? 'sélectionné') . ' est maintenant responsable de cette consultation.'
        );
    }

    public function sauvegarder(): void
    {
        if (! $this->consultation->user_id) {
            Flux::toast(variant: 'danger', heading: 'Médecin requis', text: "Assignez un médecin avant d'enregistrer le prélèvement.");

            return;
        }

        $validated = $this->validate(
            [
                'poids' => 'required|numeric|min:1',
                'temperature' => 'required|numeric|min:35|max:45',
                'taille' => 'nullable|numeric|min:1',
                'systolite' => 'nullable|numeric|min:50|max:250',
                'diastolique' => 'nullable|numeric|min:30|max:150',
                'perimetre_cranien' => 'nullable|numeric|min:1',
                'perimetre_brachial' => 'nullable|numeric|min:0',
                'frequence_cardiaque' => 'nullable|numeric|min:30|max:200',
                'frequence_respiratoire' => 'nullable|numeric|min:8|max:60',
                'saturation_oxygene' => 'nullable|numeric|min:30|max:100',
                'glycemie' => 'nullable|numeric|min:8|max:60',
                'mois' => 'nullable|string|max:50',
            ],
            [
                'poids.required' => 'Le poids est obligatoire.',
                'temperature.required' => 'La température est obligatoire.',
            ],
        );

        $payload = [
            'poids' => (int) round($validated['poids']),
            'temperature' => (int) round($validated['temperature']),
            'taille' => isset($validated['taille']) ? (int) round($validated['taille']) : null,
            'systolite' => isset($validated['systolite']) ? (int) round($validated['systolite']) : null,
            'diastolique' => isset($validated['diastolique']) ? (int) round($validated['diastolique']) : null,
            'perimetre_cranien' => isset($validated['perimetre_cranien']) ? (int) round($validated['perimetre_cranien']) : null,
            'perimetre_brachial' => isset($validated['perimetre_brachial']) ? (int) round($validated['perimetre_brachial']) : null,
            'frequence_cardiaque' => isset($validated['frequence_cardiaque']) ? (int) round($validated['frequence_cardiaque']) : null,
            'frequence_respiratoire' => isset($validated['frequence_respiratoire']) ? (int) round($validated['frequence_respiratoire']) : null,
            'saturation_oxygene' => isset($validated['saturation_oxygene']) ? (int) round($validated['saturation_oxygene']) : null,
            'glycemie' => isset($validated['glycemie']) ? (int) round($validated['glycemie']) : null,
            'mois' => $validated['mois'] ?? null,
        ];

        $this->consultation->update($payload);
        $this->consultation->refresh();

        Flux::toast(variant: 'success', heading: 'Prélèvement enregistré', text: 'Les signes vitaux ont été sauvegardés.');

        $this->redirect(route('consultation.show', $this->consultation->id), navigate: true);
    }

    public function continuerSansPrelevement(): void
    {
        if (! $this->consultation->user_id) {
            Flux::toast(variant: 'danger', heading: 'Médecin requis', text: 'Assignez un médecin avant de continuer, même sans prélèvement.');

            return;
        }

        $this->redirect(route('consultation.show', $this->consultation->id), navigate: true);
    }
};
?>

@php
    $patient = $consultation->dossierPatient;
    $medecinAssigne = filled($consultation->user_id) && $consultation->user;
@endphp

<div class="mx-auto max-w-6xl space-y-6 pb-28">
    {{-- En-tête patient --}}
    <section
        class="overflow-hidden rounded-[2rem] border border-cyan-100 bg-gradient-to-br from-white via-cyan-50/60 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="flex flex-col gap-6 px-6 py-6 md:px-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-3">
                <x-breadcrumbs :items="[
                    ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                    ['label' => 'Triage', 'link' => 'consultation.triage', 'icon' => 'inbox'],
                    ['label' => 'Prélèvement', 'icon' => 'heart'],
                ]" />
                <div class="space-y-1">
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-cyan-700 dark:text-cyan-300">
                        Orientation & constantes
                    </p>
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                        {{ ucwords($consultation->departement?->name ?? 'Consultation') }}
                    </h1>
                    <p class="font-mono text-sm text-slate-500 dark:text-slate-400">
                        {{ $consultation->reference }}
                    </p>
                </div>
            </div>

            <div
                class="flex items-center gap-4 rounded-2xl border border-white/80 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-900/80">
                <div
                    class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-cyan-500 text-lg font-black text-white shadow-md">
                    {{ strtoupper(substr($patient->prenom ?? 'P', 0, 1) . substr($patient->nom ?? 'X', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-lg font-black uppercase tracking-tight text-slate-900 dark:text-white">
                        {{ $patient->full_name }}
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ $patient->genre }} · {{ $patient->age }}
                    </p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        NIN {{ $patient->nin }}{{ $patient->ins ? ' · INS ' . $patient->ins : '' }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Étapes --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <div @class([
            'flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-semibold transition',
            'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200' => $medecinAssigne,
            'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100' => ! $medecinAssigne,
        ])>
            <span @class([
                'flex size-8 items-center justify-center rounded-full text-xs font-black',
                'bg-emerald-500 text-white' => $medecinAssigne,
                'bg-amber-400 text-amber-950' => ! $medecinAssigne,
            ])>1</span>
            <div>
                <p class="font-bold">Médecin traitant</p>
                <p class="text-xs font-normal opacity-80">
                    {{ $medecinAssigne ? 'Assigné' : 'Obligatoire avant de continuer' }}
                </p>
            </div>
        </div>
        <div
            class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200">
            <span
                class="flex size-8 items-center justify-center rounded-full bg-slate-200 text-xs font-black text-slate-700 dark:bg-slate-700 dark:text-slate-100">2</span>
            <div>
                <p class="font-bold">Signes vitaux</p>
                <p class="text-xs font-normal text-slate-500 dark:text-slate-400">Poids et température obligatoires</p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)]">
        {{-- Assignation médecin --}}
        <section
            class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                        <flux:icon.user-circle class="size-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Étape 1</p>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Médecin traitant</h2>
                    </div>
                </div>
            </div>

            <div class="space-y-5 p-5">
                @if ($medecinAssigne)
                    <div
                        class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 dark:border-emerald-500/25 dark:from-emerald-500/10 dark:to-slate-900/40">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-sm font-black text-white">
                                {{ $consultation->user->initials() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">
                                    Médecin assigné
                                </p>
                                <p class="mt-1 text-xl font-black text-slate-900 dark:text-white">
                                    {{ $consultation->user->name }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    {{ $consultation->user->departement?->name ?? 'Département non renseigné' }}
                                </p>
                            </div>
                            <flux:icon.check-circle class="size-6 shrink-0 text-emerald-500" />
                        </div>
                    </div>
                @else
                    <div
                        class="rounded-2xl border border-dashed border-amber-300 bg-amber-50/50 p-5 text-center dark:border-amber-500/40 dark:bg-amber-500/5">
                        <flux:icon.exclamation-triangle class="mx-auto size-8 text-amber-500" />
                        <p class="mt-3 text-sm font-semibold text-amber-900 dark:text-amber-100">
                            Aucun médecin assigné
                        </p>
                        <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-200/80">
                            Recherchez un médecin du département pour poursuivre.
                        </p>
                    </div>
                @endif

                <x-command-palette id="medecins-search" :request="[
                    'url' => route('api.usersConnected'),
                    'method' => 'get',
                    'params' => [
                        'search' => '',
                        'hopital_id' => current_hopital_id(),
                        'departement_id' => $consultation->departement_id,
                    ],
                ]" select="label:name|value:id|description:description|image:image"
                    x-on:select="$wire.assignerMedecin($event.detail.id ?? $event.detail.value)"
                    placeholder="Nom, matricule ou CNOM (min. 2 caractères)..." />

                <flux:button class="w-full justify-center" variant="primary" icon="magnifying-glass" color="sky"
                    x-on:click="$tsui.open.commandPalette('medecins-search')">
                    {{ $medecinAssigne ? 'Changer de médecin' : 'Rechercher un médecin' }}
                </flux:button>

                <p class="text-center text-xs text-slate-400 dark:text-slate-500">
                    Filtre : médecins de
                    <span class="font-semibold text-slate-600 dark:text-slate-300">{{ current_hopital_nom() }}</span>
                    @if ($consultation->departement)
                        · {{ ucwords($consultation->departement->name) }}
                    @endif
                </p>
            </div>
        </section>

        {{-- Signes vitaux --}}
        <section
            class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                        <flux:icon.heart class="size-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Étape 2</p>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Signes vitaux</h2>
                    </div>
                </div>
            </div>

            <div class="space-y-6 p-5">
                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Mesures principales</p>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-number label="Poids (kg) *" wire:model="poids" min="1" />
                        <x-number label="Température (°C) *" wire:model="temperature" />
                        <x-number label="Taille (cm)" wire:model="taille" min="1" />
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Pression artérielle</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-number label="Systolique (mmHg)" wire:model="systolite" />
                        <x-number label="Diastolique (mmHg)" wire:model="diastolique" />
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Cardio-respiratoire &
                        périmètres</p>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <x-number label="Fréq. cardiaque (bpm)" wire:model="frequence_cardiaque" />
                        <x-number label="Fréq. respiratoire" wire:model="frequence_respiratoire" />
                        <x-number label="Périmètre crânien (cm)" min="1" wire:model="perimetre_cranien" />
                        <x-number label="Périmètre brachial (cm)" min="0" wire:model="perimetre_brachial" />
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Compléments</p>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-number label="Saturation O₂ (%)" wire:model="saturation_oxygene" />
                        <x-number label="Glycémie" wire:model="glycemie" />
                        <x-input label="Période (M0, M1, M2…)" wire:model="mois" />
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Barre d'actions --}}
    <div
        class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 px-4 py-4 shadow-[0_-8px_30px_rgba(15,23,42,0.08)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                @if ($medecinAssigne)
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">Prêt</span> — vous pouvez
                    enregistrer ou passer sans prélèvement.
                @else
                    <span class="font-semibold text-amber-600 dark:text-amber-400">En attente</span> — assignez un
                    médecin pour débloquer les actions.
                @endif
            </p>
            <div class="flex flex-wrap justify-end gap-3">
                <flux:button type="button" wire:click="continuerSansPrelevement" :disabled="! $consultation->user_id"
                    variant="subtle" wire:loading.attr="disabled">
                    Continuer sans prélèvement
                </flux:button>
                <flux:button type="button" icon="check" variant="primary" color="indigo" wire:click="sauvegarder"
                    wire:loading.attr="disabled" :disabled="! $consultation->user_id">
                    <span wire:loading.remove wire:target="sauvegarder">Enregistrer les constantes</span>
                    <span wire:loading wire:target="sauvegarder">Enregistrement…</span>
                </flux:button>
            </div>
        </div>
    </div>
</div>
