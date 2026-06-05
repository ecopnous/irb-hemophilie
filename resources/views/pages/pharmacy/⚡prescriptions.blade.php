<?php

use App\Models\Consultation;
use App\Models\prescription\Medicament;
use App\Models\prescription\Pharmacie;
use App\Models\prescription\Prescription;
use App\Services\PharmacyStockService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Gestion des prescriptions'), Layout('layouts::app.other.pharmacy')] class extends Component {
    public ?int $consultation_id = null;
    public ?int $medicament_id = null;
    public ?int $qty = null;
    public ?int $pharmacie_id = null;
    public ?int $serve_prescription_id = null;

    public function mount(): void
    {
        $this->pharmacie_id = Pharmacie::query()->where('hopital_id', current_hopital_id())->value('id');
        if (request()->filled('consultation')) {
            $this->consultation_id = (int) request()->integer('consultation');
        }
    }

    #[Computed]
    public function consultations()
    {
        return Consultation::query()
            ->where('hopital_id', current_hopital_id())
            ->latest('created_at')
            ->limit(300)
            ->get();
    }

    #[Computed]
    public function medicaments()
    {
        return Medicament::query()->orderBy('name')->limit(500)->get();
    }

    #[Computed]
    public function pharmacies()
    {
        return Pharmacie::query()->where('hopital_id', current_hopital_id())->orderBy('nom')->get();
    }

    public function createPrescription(): void
    {
        $validated = $this->validate([
            'consultation_id' => ['required', 'integer', 'exists:consultations,id'],
            'medicament_id' => ['required', 'integer', 'exists:medicaments,id'],
            'qty' => ['required', 'integer', 'gt:0'],
        ]);

        DB::transaction(function () use ($validated) {
            $consultation = Consultation::query()->with('dossierPatient')->findOrFail($validated['consultation_id']);
            $prescription = $consultation->prescription ?: Prescription::query()->create([
                'consultation_id' => $consultation->id,
                'hopital_id' => $consultation->hopital_id,
                'dossier_patient_id' => $consultation->dossier_patient_id,
                'status' => 'draft',
            ]);

            $existing = $prescription->medicaments()->where('medicament_id', $validated['medicament_id'])->first();
            if ($existing) {
                $prescription->medicaments()->updateExistingPivot($validated['medicament_id'], [
                    'nbr' => (int) $existing->pivot->nbr + (int) $validated['qty'],
                ]);
            } else {
                $prescription->medicaments()->attach($validated['medicament_id'], [
                    'qte_jour' => 1,
                    'nbr' => (int) $validated['qty'],
                    'qte_servie' => 0,
                ]);
            }
        });

        $this->reset(['medicament_id', 'qty']);
    }

    public function serveSelected(PharmacyStockService $service): void
    {
        $validated = $this->validate([
            'serve_prescription_id' => ['required', 'integer', 'exists:prescriptions,id'],
            'pharmacie_id' => ['required', 'integer', 'exists:pharmacies,id'],
        ]);

        $prescription = Prescription::query()->with('medicaments')->findOrFail($validated['serve_prescription_id']);
        $service->servePrescription($prescription, $validated['pharmacie_id'], auth()->id());
        $this->serve_prescription_id = null;
    }
};
?>

<div class="space-y-5 p-6">
    <h1 class="text-2xl font-black text-slate-900 dark:text-white">Gestion des Prescriptions</h1>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-3 font-bold text-slate-900 dark:text-white">Nouvelle ligne de prescription (liee a consultation)</h2>
        <div class="grid gap-3 md:grid-cols-4">
            <x-select.styled label="Consultation" wire:model="consultation_id" :options="$this->consultations->map(fn($c) => ['label' => ($c->reference . ' - ' . ($c->dossierPatient?->full_name ?? 'Patient')), 'value' => $c->id])->values()->all()"
                select="label:label|value:value" />
            <x-select.styled label="Medicament" wire:model="medicament_id" :options="$this->medicaments->map(fn($m) => ['label' => $m->name . ' (' . $m->reference . ')', 'value' => $m->id])->values()->all()"
                select="label:label|value:value" />
            <x-number label="Quantite" wire:model="qty" />
            <flux:button class="mt-6" wire:click="createPrescription" variant="primary">Ajouter</flux:button>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-3 font-bold text-slate-900 dark:text-white">Servir une prescription</h2>
        <div class="grid gap-3 md:grid-cols-3">
            <x-input label="Prescription ID" wire:model="serve_prescription_id" />
            <x-select.styled label="Pharmacie" wire:model="pharmacie_id" :options="$this->pharmacies->map(fn($p) => ['label' => $p->nom, 'value' => $p->id])->values()->all()"
                select="label:label|value:value" />
            <flux:button class="mt-6" wire:click="serveSelected" variant="primary" color="emerald">Servir</flux:button>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <livewire:pharmacy-prescription-table />
    </div>
</div>
