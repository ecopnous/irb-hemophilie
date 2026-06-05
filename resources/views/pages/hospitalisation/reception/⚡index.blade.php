<?php

use App\Models\Consultation;
use App\Models\hospitalisation\Chambre;
use App\Models\hospitalisation\Hospitalisation;
use App\Models\hospitalisation\HospService;
use App\Models\hospitalisation\Lit;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Title('Reception hospitalisation'), Layout('layouts::app.other.hospital')] class extends Component {
    use Interactions;

    public array $admissionForm = [
        'consultation_id' => null,
        'hosp_service_id' => null,
        'chambre_id' => null,
        'lit_id' => null,
        'date_entree' => '',
        'motif' => '',
    ];

    public ?int $pendingDischargeId = null;
    public string $dischargeNote = '';

    public function mount(): void
    {
        abort_unless(current_hopital_id(), 403, 'Aucun hopital courant en session.');

        $this->admissionForm['date_entree'] = now()->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function pendingConsultations()
    {
        return Consultation::query()
            ->with(['dossierPatient', 'departement', 'service', 'user'])
            ->whereHopitalId(current_hopital_id())
            ->where('issue', 'hospitalisation')
            ->whereNull('hospitalisation_id')
            ->latest()
            ->get();
    }

    #[Computed]
    public function services()
    {
        return HospService::query()
            ->with('departement')
            ->whereHopitalId(current_hopital_id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function chambres()
    {
        return Chambre::query()
            ->where('hosp_service_id', $this->admissionForm['hosp_service_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function lits()
    {
        return Lit::query()
            ->where('chambre_id', $this->admissionForm['chambre_id'])
            ->where('is_active', true)
            ->where('statut', 'disponible')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function activeHospitalisations()
    {
        return Hospitalisation::query()
            ->with(['dossierPatient', 'consultation', 'service', 'chambre', 'lit'])
            ->whereHopitalId(current_hopital_id())
            ->where('statut', 'active')
            ->whereNull('date_sortie')
            ->latest('date_entree')
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $bedBase = Lit::query()
            ->whereHas('chambre.service', fn($query) => $query->where('hopital_id', current_hopital_id()));

        $totalBeds = (clone $bedBase)->count();
        $occupiedBeds = (clone $bedBase)->where('statut', 'occupe')->count();
        $availableBeds = (clone $bedBase)->where('statut', 'disponible')->count();

        return [
            'pending' => $this->pendingConsultations->count(),
            'active' => $this->activeHospitalisations->count(),
            'available_beds' => $availableBeds,
            'occupied_beds' => $occupiedBeds,
            'occupancy_rate' => $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0,
        ];
    }

    public function updatedAdmissionFormHospServiceId(): void
    {
        $this->admissionForm['chambre_id'] = null;
        $this->admissionForm['lit_id'] = null;
    }

    public function updatedAdmissionFormChambreId(): void
    {
        $this->admissionForm['lit_id'] = null;
    }

    public function selectConsultation(int $consultationId): void
    {
        $consultation = $this->pendingConsultations->firstWhere('id', $consultationId);

        if (!$consultation) {
            return;
        }

        $this->admissionForm['consultation_id'] = $consultationId;
        $this->admissionForm['motif'] = (string) ($consultation->cause_issue ?? '');
    }

    public function saveAdmission(): void
    {
        $validated = $this->validate([
            'admissionForm.consultation_id' => ['required', 'integer'],
            'admissionForm.hosp_service_id' => ['required', 'integer', 'exists:hosp_services,id'],
            'admissionForm.chambre_id' => ['required', 'integer', 'exists:chambres,id'],
            'admissionForm.lit_id' => ['required', 'integer', 'exists:lits,id'],
            'admissionForm.date_entree' => ['required', 'date'],
            'admissionForm.motif' => ['nullable', 'string', 'max:2000'],
        ]);

        $consultation = Consultation::query()
            ->with('dossierPatient')
            ->whereHopitalId(current_hopital_id())
            ->findOrFail($validated['admissionForm']['consultation_id']);

        if ($consultation->hospitalisation_id) {
            $this->toast()->error('Cette consultation a deja ete traitee en hospitalisation.')->send();

            return;
        }

        $service = HospService::query()
            ->whereHopitalId(current_hopital_id())
            ->findOrFail($validated['admissionForm']['hosp_service_id']);

        $chambre = Chambre::query()
            ->where('hosp_service_id', $service->id)
            ->findOrFail($validated['admissionForm']['chambre_id']);

        $lit = Lit::query()
            ->where('chambre_id', $chambre->id)
            ->findOrFail($validated['admissionForm']['lit_id']);

        if (!$lit->isDisponible()) {
            $this->toast()->error('Le lit selectionne n est plus disponible.')->send();

            return;
        }

        $hospitalisation = Hospitalisation::query()->create([
            'consultation_id' => $consultation->id,
            'dossier_patient_id' => $consultation->dossier_patient_id,
            'departement_id' => $consultation->departement_id,
            'hosp_service_id' => $service->id,
            'chambre_id' => $chambre->id,
            'lit_id' => $lit->id,
            'hopital_id' => current_hopital_id(),
            'montant' => $chambre->montant,
            'total_amount' => $chambre->montant,
            'paid_amount' => 0,
            'due_amount' => $chambre->montant,
            'currency' => 'USD',
            'unite' => $chambre->unite,
            'date_entree' => Carbon::parse($validated['admissionForm']['date_entree']),
            'motif' => $validated['admissionForm']['motif'] ?: null,
            'statut' => 'active',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $consultation->update([
            'hospitalisation_id' => $hospitalisation->id,
        ]);

        $this->admissionForm = [
            'consultation_id' => null,
            'hosp_service_id' => null,
            'chambre_id' => null,
            'lit_id' => null,
            'date_entree' => now()->format('Y-m-d\TH:i'),
            'motif' => '',
        ];

        $this->toast()->success('Admission hospitaliere enregistree avec succes.')->send();
    }

    public function prepareDischarge(int $hospitalisationId): void
    {
        $hospitalisation = $this->activeHospitalisations->firstWhere('id', $hospitalisationId);

        if (!$hospitalisation) {
            return;
        }

        $this->pendingDischargeId = $hospitalisationId;
        $this->dischargeNote = '';
    }

    public function discharge(): void
    {
        $validated = $this->validate([
            'pendingDischargeId' => ['required', 'integer'],
            'dischargeNote' => ['nullable', 'string', 'max:2000'],
        ]);

        $hospitalisation = Hospitalisation::query()
            ->whereHopitalId(current_hopital_id())
            ->findOrFail($validated['pendingDischargeId']);

        $hospitalisation->update([
            'date_sortie' => now(),
            'statut' => 'terminee',
            'note_sortie' => $validated['dischargeNote'] ?: null,
        ]);

        $this->pendingDischargeId = null;
        $this->dischargeNote = '';

        $this->toast()->success('Sortie hospitaliere enregistree et lit libere.')->send();
    }
};
?>

<section class="w-full space-y-6 px-4 py-5 sm:px-6 lg:px-8">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">En attente</p>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['pending'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Hospitalises</p>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['active'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Lits disponibles</p>
            <p class="mt-3 text-3xl font-black text-emerald-600">{{ $this->stats['available_beds'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Lits occupes</p>
            <p class="mt-3 text-3xl font-black text-rose-600">{{ $this->stats['occupied_beds'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Occupation</p>
            <p class="mt-3 text-3xl font-black text-sky-600">{{ $this->stats['occupancy_rate'] }}%</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
        <div class="space-y-6">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Admission</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Nouvelle hospitalisation</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Affectez un patient a un service, une chambre et un lit disponible.
                        </p>
                    </div>
                    <a href="{{ route('hospital.configuration') }}" wire:navigate
                        class="inline-flex items-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:text-slate-200">
                        Configurer la capacite
                    </a>
                </div>

                <div class="mt-6 space-y-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-select.native wire:model.live="admissionForm.consultation_id" label="Consultation a hospitaliser *"
                            :options="$this->pendingConsultations->map(fn($consultation) => ['label' => $consultation->dossierPatient?->full_name . ' - ' . $consultation->reference, 'value' => $consultation->id])->values()->all()" />
                        <x-input wire:model="admissionForm.date_entree" type="datetime-local" label="Date d entree *" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <x-select.native wire:model.live="admissionForm.hosp_service_id" label="Service *"
                            :options="$this->services->map(fn($service) => ['label' => $service->name, 'value' => $service->id])->values()->all()" />
                        <x-select.native wire:model.live="admissionForm.chambre_id" label="Chambre *"
                            :options="$this->chambres->map(fn($chambre) => ['label' => $chambre->name . ' - ' . ucfirst($chambre->type), 'value' => $chambre->id])->values()->all()" />
                        <x-select.native wire:model="admissionForm.lit_id" label="Lit *"
                            :options="$this->lits->map(fn($lit) => ['label' => $lit->name, 'value' => $lit->id])->values()->all()" />
                    </div>

                    <x-textarea wire:model="admissionForm.motif" label="Motif / indication clinique" rows="4" maxlength="2000" count />

                    <div class="flex justify-end">
                        <flux:button variant="primary" color="sky" wire:click="saveAdmission">
                            Enregistrer l admission
                        </flux:button>
                    </div>
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">File d attente</p>
                        <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Consultations orientees vers l hospitalisation</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        {{ $this->pendingConsultations->count() }}
                    </span>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($this->pendingConsultations as $consultation)
                        <button type="button" wire:click="selectConsultation({{ $consultation->id }})"
                            class="w-full rounded-3xl border px-5 py-4 text-left transition {{ (int) $admissionForm['consultation_id'] === (int) $consultation->id ? 'border-sky-300 bg-sky-50/80 dark:border-sky-800 dark:bg-sky-950/30' : 'border-slate-200 bg-slate-50/70 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900/60' }}">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-base font-bold text-slate-900 dark:text-white">{{ $consultation->dossierPatient?->full_name ?: 'Patient inconnu' }}</p>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        {{ $consultation->reference }} · {{ $consultation->departement?->name ?: 'Sans departement' }}
                                        @if ($consultation->service?->name)
                                            · {{ $consultation->service->name }}
                                        @endif
                                    </p>
                                </div>
                                <div class="text-sm text-slate-500 dark:text-slate-400">
                                    {{ optional($consultation->created_at)->format('d/m/Y H:i') }}
                                </div>
                            </div>
                            @if (filled($consultation->cause_issue))
                                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $consultation->cause_issue }}</p>
                            @endif
                        </button>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Aucune consultation en attente d admission.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Patients presents</p>
                <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Hospitalisations actives</h2>

                <div class="mt-5 space-y-3">
                    @forelse ($this->activeHospitalisations as $hospitalisation)
                        <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                            <div class="flex flex-col gap-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white">{{ $hospitalisation->dossierPatient?->full_name ?: 'Patient inconnu' }}</p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            {{ $hospitalisation->service?->name ?: 'Service non defini' }} ·
                                            {{ $hospitalisation->chambre?->name ?: 'Chambre non definie' }} ·
                                            {{ $hospitalisation->lit?->name ?: 'Lit non defini' }}
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        En cours
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3 text-sm text-slate-500 dark:text-slate-400">
                                    <span>Entree: {{ optional($hospitalisation->date_entree)->format('d/m/Y H:i') }}</span>
                                    <button type="button" wire:click="prepareDischarge({{ $hospitalisation->id }})"
                                        class="font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-300">
                                        Enregistrer la sortie
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Aucun patient actuellement hospitalise.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Sortie</p>
                <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Liberer un lit</h2>

                @if ($pendingDischargeId)
                    <div class="mt-5 space-y-4">
                        <x-textarea wire:model="dischargeNote" label="Observation de sortie" rows="5" maxlength="2000" count />
                        <div class="flex justify-end gap-3">
                            <flux:button variant="ghost" wire:click="$set('pendingDischargeId', null)">Annuler</flux:button>
                            <flux:button variant="primary" color="rose" wire:click="discharge">Confirmer la sortie</flux:button>
                        </div>
                    </div>
                @else
                    <p class="mt-5 text-sm text-slate-500 dark:text-slate-400">
                        Selectionnez un patient hospitalise pour enregistrer sa sortie et remettre automatiquement le lit en disponibilite.
                    </p>
                @endif
            </div>
        </div>
    </div>
</section>
