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
            ->with(['user', 'dossierPatient', 'departement'])
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
        $this->consultation->refresh()->load(['user', 'dossierPatient', 'departement']);

        Flux::toast(variant: 'success', heading: 'Médecin assigné', text: 'Le médecin a été associé à cette consultation.');
    }

    public function sauvegarder(): void
    {
        if (!$this->consultation->user_id) {
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
        if (!$this->consultation->user_id) {
            Flux::toast(variant: 'danger', heading: 'Médecin requis', text: 'Assignez un médecin avant de continuer, même sans prélèvement.');

            return;
        }

        $this->redirect(route('consultation.show', $this->consultation->id), navigate: true);
    }
};
?>

<div>
    <div class="max-w-7xl mx-auto mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Consultation', 'icon' => 'clipboard-document-check'],
                ['label' => 'Prelevement'],
            ]" />
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mt-2">
                {{ ucwords($consultation->departement->name) }}
            </h1>
            <p class="text-sm font-mono text-gray-500 dark:text-slate-400 mt-1">ID:
                {{ $consultation->dossierPatient->nin }}
                {{ ucfirst($consultation->dossierPatient->prenom) }} {{ ucfirst($consultation->dossierPatient->nom) }}
                {{ $consultation->dossierPatient->ins ? 'N°' . $consultation->dossierPatient->ins : '' }}</p>
            <p class="text-sm font-mono text-gray-500 dark:text-slate-400 mt-1">
                {{ $consultation->dossierPatient->genre }}
                ({{ $consultation->dossierPatient->age }})</p>
        </div>
        <div>
            <x-command-palette id="search" :request="['url' => route('api.usersConnected'), 'params' => ['hopital_id' => current_hopital_id()]]" select="label:name|value:id"
                x-on:select="$wire.assignerMedecin($event.detail.id ?? $event.detail.value)" />
            <flux:button variant="primary" icon="link" color="indigo" x-on:click="$tsui.open.commandPalette('search')">
                Assigner un médecin à la consultation
            </flux:button>
        </div>
    </div>
    <div class="max-w-7xl mx-auto">
        <x-card header="Médecin assigné">
            @if ($consultation->user_id && $consultation->user)
                <p class="text-center text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $consultation->user->name }}
                </p>
            @else
                <p class="text-center text-amber-700 dark:text-amber-400">Aucun médecin assigné — obligatoire pour
                    continuer.</p>
            @endif
        </x-card>
        <div class="mt-6">
            <x-card header="Prélevement des Signes Vitaux">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <x-number label="Poids (Kg) *" wire:model="poids" min="1" />
                    <x-number label="Temperature (°C) *" wire:model="temperature" />
                    <x-number label="Taille" wire:model="taille" min="1" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <x-number label="Systolique (mmHg)" wire:model="systolite" />
                    <x-number label="Diastolique (mmHg)" wire:model="diastolique" />
                    <x-number label="Périmètre cranien (cm)" min="1" wire:model="perimetre_cranien" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <x-number label="Fréquence Cardiaque (bpm)" wire:model="frequence_cardiaque" />
                    <x-number label="Fréquence Respiratoire (resp/min)" wire:model="frequence_respiratoire" />
                    <x-number label="Périmètre brachial (cm)" min="0" wire:model="perimetre_brachial" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <x-number label="Saturation en Oygène (%)" wire:model="saturation_oxygene" />
                    <x-number label="Glycémie" wire:model="glycemie" />
                    <x-input label="Période (M0, M1, M2, ...)" wire:model="mois" />
                </div>
                <div class="flex flex-wrap justify-end gap-4">
                    <flux:button type="button" wire:click="continuerSansPrelevement"
                        :disabled="! $consultation->user_id" variant="subtle">
                        Continuer sans prélevement
                    </flux:button>
                    <flux:button type="button" icon="save" variant="primary" color="indigo" wire:click="sauvegarder"
                        wire:loading.attr="disabled" :disabled="! $consultation->user_id">
                        Sauvergarder les informations
                    </flux:button>
                </div>
            </x-card>
        </div>
    </div>
</div>
