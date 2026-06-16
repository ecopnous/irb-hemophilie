<?php

use App\Models\Consultation;
use App\Models\prescription\Pharmacie;
use App\Models\prescription\Prescription;
use App\Services\PharmacyStockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Gestion des prescriptions'), Layout('layouts::app.other.pharmacy')] class extends Component {
    public ?int $pharmacie_id = null;
    public ?int $consultation_id = null;
    public ?int $medicament_id = null;
    public ?int $qty = null;
    public ?int $serve_prescription_id = null;

    public function mount(): void
    {
        $this->pharmacie_id = Pharmacie::query()
            ->where('hopital_id', current_hopital_id())
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('nom')
            ->value('id');

        if (request()->filled('consultation')) {
            $this->consultation_id = (int) request()->integer('consultation');
        }
    }

    public function updatedPharmacieId($value): void
    {
        $this->pharmacie_id = $this->toInt($value);
        $this->medicament_id = null;
        $this->qty = null;
        unset($this->pharmacyMedicaments, $this->currentStock, $this->serveLines);
    }

    public function updatedConsultationId($value): void
    {
        $this->consultation_id = $this->toInt($value);
    }

    public function updatedMedicamentId($value): void
    {
        $this->medicament_id = $this->toInt($value);
        $this->qty = null;
        unset($this->currentStock);
    }

    public function updatedServePrescriptionId($value): void
    {
        $this->serve_prescription_id = $this->toInt($value);
        unset($this->selectedPrescription, $this->serveLines);
    }

    private function toInt(mixed $value): ?int
    {
        return $value !== null && $value !== '' ? (int) $value : null;
    }

    #[Computed]
    public function consultations(): Collection
    {
        return Consultation::query()
            ->with('dossierPatient')
            ->where('hopital_id', current_hopital_id())
            ->latest('created_at')
            ->limit(200)
            ->get();
    }

    #[Computed]
    public function servablePrescriptions(): Collection
    {
        return Prescription::query()
            ->with(['dossierPatient', 'medicaments'])
            ->where('hopital_id', current_hopital_id())
            ->whereIn('status', ['draft', 'partial'])
            ->latest('created_at')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function pharmacies(): Collection
    {
        return Pharmacie::query()
            ->where('hopital_id', current_hopital_id())
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('nom')
            ->get();
    }

    #[Computed]
    public function pharmacyMedicaments(): Collection
    {
        if (! $this->pharmacie_id) {
            return collect();
        }

        $pharmacie = Pharmacie::query()
            ->where('hopital_id', current_hopital_id())
            ->with(['medicaments' => fn ($q) => $q->orderBy('medicaments.name')])
            ->find($this->pharmacie_id);

        return $pharmacie?->medicaments ?? collect();
    }

    #[Computed]
    public function currentStock(): int
    {
        if (! $this->pharmacie_id || ! $this->medicament_id) {
            return 0;
        }

        $medicament = $this->pharmacyMedicaments->firstWhere('id', $this->medicament_id);

        return (int) ($medicament?->pivot->quantiter ?? 0);
    }

    #[Computed]
    public function selectedPrescription(): ?Prescription
    {
        if (! $this->serve_prescription_id) {
            return null;
        }

        return Prescription::query()
            ->with(['medicaments', 'dossierPatient', 'consultation'])
            ->where('hopital_id', current_hopital_id())
            ->whereIn('status', ['draft', 'partial'])
            ->find($this->serve_prescription_id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function serveLines(): array
    {
        $prescription = $this->selectedPrescription;

        if (! $prescription || ! $this->pharmacie_id) {
            return [];
        }

        $pharmacie = Pharmacie::query()
            ->where('hopital_id', current_hopital_id())
            ->with('medicaments')
            ->find($this->pharmacie_id);

        if (! $pharmacie) {
            return [];
        }

        $stockByMedicament = $pharmacie->medicaments->mapWithKeys(
            fn ($medicament) => [$medicament->id => (int) $medicament->pivot->quantiter]
        );

        return $prescription->medicaments->map(function ($medicament) use ($stockByMedicament) {
            $prescribed = (int) $medicament->pivot->nbr;
            $served = (int) $medicament->pivot->qte_servie;
            $remaining = max(0, $prescribed - $served);
            $stock = (int) ($stockByMedicament[$medicament->id] ?? 0);

            return [
                'name' => $medicament->name,
                'reference' => $medicament->reference ?: '-',
                'prescribed' => $prescribed,
                'served' => $served,
                'remaining' => $remaining,
                'stock' => $stock,
                'ok' => $remaining <= 0 || $stock >= $remaining,
            ];
        })->all();
    }

    #[Computed]
    public function canServe(): bool
    {
        if (! $this->serve_prescription_id || ! $this->pharmacie_id) {
            return false;
        }

        $lines = collect($this->serveLines)->where('remaining', '>', 0);

        return $lines->isNotEmpty() && $lines->every(fn (array $line) => $line['ok']);
    }

    // public function createPrescription(): void
    // {
    //     $stock = $this->currentStock;

    //     $validated = $this->validate([
    //         'pharmacie_id' => [
    //             'required',
    //             'integer',
    //             Rule::exists('pharmacies', 'id')->where(fn ($q) => $q->where('hopital_id', current_hopital_id())),
    //         ],
    //         'consultation_id' => [
    //             'required',
    //             'integer',
    //             Rule::exists('consultations', 'id')->where(fn ($q) => $q->where('hopital_id', current_hopital_id())),
    //         ],
    //         'medicament_id' => ['required', 'integer', 'exists:medicaments,id'],
    //         'qty' => ['required', 'integer', 'gt:0'],
    //     ]);

    //     $linked = DB::table('medicament_pharmacie')
    //         ->where('pharmacie_id', $validated['pharmacie_id'])
    //         ->where('medicament_id', $validated['medicament_id'])
    //         ->exists();

    //     if (! $linked) {
    //         throw ValidationException::withMessages([
    //             'medicament_id' => 'Ce medicament n\'est pas disponible dans la pharmacie selectionnee.',
    //         ]);
    //     }

    //     if ($stock <= 0) {
    //         throw ValidationException::withMessages([
    //             'medicament_id' => 'Stock indisponible pour ce medicament.',
    //         ]);
    //     }

    //     if ($validated['qty'] > $stock) {
    //         throw ValidationException::withMessages([
    //             'qty' => 'La quantite ne peut pas depasser le stock actuel (' . $stock . ').',
    //         ]);
    //     }

    //     DB::transaction(function () use ($validated) {
    //         $consultation = Consultation::query()->with('dossierPatient')->findOrFail($validated['consultation_id']);
    //         $prescription = $consultation->prescription ?: Prescription::query()->create([
    //             'consultation_id' => $consultation->id,
    //             'hopital_id' => $consultation->hopital_id,
    //             'dossier_patient_id' => $consultation->dossier_patient_id,
    //             'status' => 'draft',
    //         ]);

    //         $existing = $prescription->medicaments()->where('medicament_id', $validated['medicament_id'])->first();

    //         if ($existing) {
    //             $newTotal = (int) $existing->pivot->nbr + (int) $validated['qty'];

    //             if ($newTotal > $stock) {
    //                 throw ValidationException::withMessages([
    //                     'qty' => 'La quantite totale prescrite (' . $newTotal . ') depasserait le stock actuel (' . $stock . ').',
    //                 ]);
    //             }

    //             $prescription->medicaments()->updateExistingPivot($validated['medicament_id'], [
    //                 'nbr' => $newTotal,
    //             ]);
    //         } else {
    //             $prescription->medicaments()->attach($validated['medicament_id'], [
    //                 'qte_jour' => 1,
    //                 'nbr' => (int) $validated['qty'],
    //                 'qte_servie' => 0,
    //             ]);
    //         }
    //     });

    //     $this->reset(['medicament_id', 'qty']);
    //     unset($this->pharmacyMedicaments, $this->currentStock, $this->selectedPrescription, $this->serveLines);

    //     $this->dispatch('pg:eventRefresh-pharmacyPrescriptionTable');
    //     session()->flash('success', 'Ligne de prescription ajoutee avec succes.');
    // }

    public function serveSelected(PharmacyStockService $service): void
    {
        if (! $this->canServe) {
            throw ValidationException::withMessages([
                'serve_prescription_id' => 'Impossible de servir : stock insuffisant ou aucune quantite restante.',
            ]);
        }

        $validated = $this->validate([
            'serve_prescription_id' => [
                'required',
                'integer',
                Rule::exists('prescriptions', 'id')->where(fn ($q) => $q->where('hopital_id', current_hopital_id())),
            ],
            'pharmacie_id' => [
                'required',
                'integer',
                Rule::exists('pharmacies', 'id')->where(fn ($q) => $q->where('hopital_id', current_hopital_id())),
            ],
        ]);

        $prescription = Prescription::query()
            ->with('medicaments')
            ->where('hopital_id', current_hopital_id())
            ->whereIn('status', ['draft', 'partial'])
            ->findOrFail($validated['serve_prescription_id']);

        foreach ($this->serveLines as $line) {
            if ($line['remaining'] <= 0) {
                continue;
            }

            if ($line['stock'] < $line['remaining']) {
                throw ValidationException::withMessages([
                    'serve_prescription_id' => 'Stock insuffisant pour '
                        . $line['name']
                        . ' : disponible ' . $line['stock']
                        . ', requis ' . $line['remaining'] . '.',
                ]);
            }
        }

        $service->servePrescription(
            $prescription,
            $validated['pharmacie_id'],
            (int) auth()->id()
        );

        $this->serve_prescription_id = null;
        unset($this->selectedPrescription, $this->serveLines, $this->pharmacyMedicaments, $this->currentStock);

        $this->dispatch('pg:eventRefresh-pharmacyPrescriptionTable');
        session()->flash('success', 'Prescription servie avec succes.');
    }
};
?>

<div class="mx-auto space-y-5 max-w-7xl">
    <div>
        <x-breadcrumbs :items="[
            ['label' => 'Pharmacie', 'link' => 'pharmacie.dashboard', 'icon' => 'building-storefront'],
            ['label' => 'Prescriptions', 'icon' => 'document-text'],
        ]" />
        <h1 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Gestion des prescriptions</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Ajoutez des lignes liees a une consultation et dispensez en verifiant le stock disponible.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($this->pharmacies->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            Aucune pharmacie active.
            <a href="{{ route('pharmacie.pharmacies') }}" wire:navigate class="font-bold underline">Configurer une pharmacie</a>
        </div>
    @else
        {{-- <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <h2 class="mb-1 font-bold text-slate-900 dark:text-white">Nouvelle ligne de prescription</h2>
            <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Liee a une consultation active de l'hopital.</p>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                <x-select.styled
                    label="Pharmacie"
                    wire:model.live="pharmacie_id"
                    :options="$this->pharmacies->map(fn ($p) => ['label' => $p->nom, 'value' => $p->id])->values()->all()"
                    select="label:label|value:value"
                />

                <x-select.styled
                    label="Consultation"
                    wire:model.live="consultation_id"
                    :options="$this->consultations->map(fn ($c) => [
                        'label' => $c->reference . ' — ' . ($c->dossierPatient?->full_name ?? 'Patient'),
                        'value' => $c->id,
                    ])->values()->all()"
                    select="label:label|value:value"
                    searchable
                />

                <x-select.styled
                    label="Medicament"
                    wire:model.live="medicament_id"
                    :options="$this->pharmacyMedicaments->map(fn ($m) => [
                        'label' => $m->name . ' (' . ($m->reference ?: '-') . ') — Stock: ' . (int) $m->pivot->quantiter,
                        'value' => $m->id,
                        'disabled' => (int) $m->pivot->quantiter <= 0,
                    ])->values()->all()"
                    :disabled="!$pharmacie_id"
                    wire:key="presc-medicament-{{ $pharmacie_id }}"
                    select="label:label|value:value"
                    searchable
                />

                <div>
                    <x-number
                        label="Quantite"
                        wire:model="qty"
                        min="1"
                        :max="$this->currentStock > 0 ? $this->currentStock : null"
                    />
                    @if ($medicament_id)
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Stock disponible :
                            <span class="font-bold {{ $this->currentStock > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $this->currentStock }}
                            </span>
                        </p>
                    @endif
                    @error('qty')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-end">
                    <flux:button class="w-full" wire:click="createPrescription" variant="primary">
                        Ajouter la ligne
                    </flux:button>
                </div>
            </div>

            @error('pharmacie_id')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
            @error('consultation_id')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
            @error('medicament_id')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div> --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <h2 class="mb-1 font-bold text-slate-900 dark:text-white">Dispenser une prescription</h2>
            <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">
                Selectionnez la prescription et verifiez le stock avant la sortie.
            </p>

            <div class="grid gap-3 md:grid-cols-3">
                <x-select.styled
                    label="Pharmacie de sortie"
                    wire:model.live="pharmacie_id"
                    :options="$this->pharmacies->map(fn ($p) => ['label' => $p->nom, 'value' => $p->id])->values()->all()"
                    select="label:label|value:value"
                />

                <x-select.styled
                    label="Prescription"
                    wire:model.live="serve_prescription_id"
                    :options="$this->servablePrescriptions->map(function ($p) {
                        $remaining = $p->medicaments->sum(
                            fn ($m) => max(0, (int) $m->pivot->nbr - (int) $m->pivot->qte_servie)
                        );

                        return [
                            'label' => ($p->reference ?: 'PRES-' . $p->id)
                                . ' — ' . ($p->dossierPatient?->full_name ?? 'Patient'),
                            'value' => $p->id,
                            'description' => $remaining . ' a servir · ' . optional($p->created_at)->format('d/m/Y'),
                        ];
                    })->values()->all()"
                    select="label:label|value:value|description:description"
                    searchable
                />

                <div class="flex items-end">
                    <flux:button
                        class="w-full"
                        wire:click="serveSelected"
                        variant="primary"
                        color="emerald"
                    >
                        Servir la prescription
                    </flux:button>
                </div>
            </div>

            @error('serve_prescription_id')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror

            @if ($serve_prescription_id && $pharmacie_id)
                <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500 dark:bg-slate-900 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Medicament</th>
                                <th class="px-4 py-3 text-right">Prescrit</th>
                                <th class="px-4 py-3 text-right">Deja servi</th>
                                <th class="px-4 py-3 text-right">A servir</th>
                                <th class="px-4 py-3 text-right">Stock</th>
                                <th class="px-4 py-3 text-center">Etat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($this->serveLines as $line)
                                <tr class="{{ $line['remaining'] > 0 && ! $line['ok'] ? 'bg-red-50/80 dark:bg-red-500/5' : '' }}">
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                        {{ $line['name'] }}
                                        <span class="block text-xs text-slate-500">{{ $line['reference'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ $line['prescribed'] }}</td>
                                    <td class="px-4 py-3 text-right">{{ $line['served'] }}</td>
                                    <td class="px-4 py-3 text-right font-bold">{{ $line['remaining'] }}</td>
                                    <td class="px-4 py-3 text-right">{{ $line['stock'] }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($line['remaining'] <= 0)
                                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600">Complet</span>
                                        @elseif ($line['ok'])
                                            <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700">OK</span>
                                        @else
                                            <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-700">Stock insuffisant</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">Aucune ligne sur cette prescription.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($serve_prescription_id && ! $this->canServe)
                    <p class="mt-3 text-xs font-semibold text-amber-700 dark:text-amber-300">
                        La dispensation est bloquee tant que le stock ne couvre pas toutes les quantites a servir.
                    </p>
                @endif
            @endif
        </div>
    @endif

    <h3 class="mb-3 text-base font-black text-slate-900 dark:text-white">Registre des prescriptions</h3>
    <livewire:pharmacy-prescription-table />
</div>
