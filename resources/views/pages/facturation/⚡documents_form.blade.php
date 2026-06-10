<?php

use App\Models\Configs\Acte;
use App\Models\facturation\FinanceClient;
use App\Models\facturation\FinanceDocument;
use App\Models\prescription\Medicament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Creation document'), Layout('layouts::app.other.facturation')] class extends Component {
    public ?int $documentId = null;
    public string $mode = 'create';

    public string $document_type = 'devis';
    public string $beneficiary_type = 'patient';
    public ?int $dossier_patient_id = null;
    public ?int $finance_client_id = null;
    public string $number = '';
    public string $issue_date = '';
    public int $validity_days = 15;
    public ?string $notes = null;
    public string $status = 'draft';
    public string $currency = 'USD';

    public string $line_type = 'acte';
    public ?int $acte_departement_id = null;
    public ?int $acte_service_id = null;
    public ?int $selectedActeId = null;
    public ?int $selectedMedicamentId = null;
    public ?string $designation = null;
    public float $quantity = 1;
    public float $price = 0;
    public float $tva = 16;
    public float $discount = 0;

    public array $items = [];

    public bool $showQuickClientForm = false;
    public string $quickClientName = '';
    public string $quickClientPhone = '';
    public string $quickClientEmail = '';
    public string $quickClientType = 'particulier';

    public function mount(?int $id = null, ?string $type = null): void
    {
        $this->issue_date = now()->format('Y-m-d');

        if ($id) {
            $this->loadDocument($id);
            return;
        }

        $this->document_type = in_array((string) $type, ['devis', 'facture'], true) ? (string) $type : 'devis';
        $this->status = $this->defaultStatusForType($this->document_type);
        $this->refreshNumber();

        $clientId = request()->integer('client');
        if ($clientId && FinanceClient::query()->where('hopital_id', current_hopital_id())->whereKey($clientId)->exists()) {
            $this->beneficiary_type = 'client';
            $this->finance_client_id = $clientId;
        }
    }

    public function updatedDocumentType(): void
    {
        if ($this->mode === 'create') {
            $this->status = $this->defaultStatusForType($this->document_type);
            $this->refreshNumber();
        }
    }

    public function updatedBeneficiaryType(): void
    {
        if ($this->beneficiary_type === 'patient') {
            $this->finance_client_id = null;
            $this->showQuickClientForm = false;
        } else {
            $this->dossier_patient_id = null;
        }
    }

    public function updatedLineType(): void
    {
        $this->reset([
            'acte_departement_id',
            'acte_service_id',
            'selectedActeId',
            'selectedMedicamentId',
            'designation',
        ]);
        $this->quantity = 1;
        $this->price = 0;
        $this->discount = 0;
    }

    public function updatedActeDepartementId(): void
    {
        $this->acte_service_id = null;
        $this->selectedActeId = null;
    }

    public function updatedActeServiceId(): void
    {
        $this->selectedActeId = null;
    }

    public function updatedSelectedActeId($value): void
    {
        if ($this->line_type !== 'acte' || ! $value) {
            return;
        }

        $acte = Acte::query()->find($value);
        if ($acte) {
            $this->designation = $acte->name;
            $this->price = (float) ($acte->base_price ?? $acte->montant ?? 0);
        }
    }

    public function updatedSelectedMedicamentId($value): void
    {
        if ($this->line_type !== 'produit' || ! $value) {
            return;
        }

        $details = $this->resolveMedicamentDetails((int) $value);
        if ($details) {
            $this->designation = $details['designation'];
            if ($details['price'] > 0) {
                $this->price = $details['price'];
            }
        }
    }

    protected function resolveMedicamentDetails(int $medicamentId): ?array
    {
        $medicament = Medicament::query()
            ->where('is_active', true)
            ->whereHas('pharmacies', fn ($q) => $q->where('hopital_id', current_hopital_id()))
            ->with(['pharmacies' => fn ($q) => $q->where('hopital_id', current_hopital_id())])
            ->find($medicamentId);

        if (! $medicament) {
            return null;
        }

        $price = (float) ($medicament->pharmacies
            ->first(fn ($pharmacy) => (float) $pharmacy->pivot->montant > 0)?->pivot->montant ?? 0);

        $designation = $medicament->name;
        if ($medicament->reference) {
            $designation .= ' (' . $medicament->reference . ')';
        }

        return [
            'designation' => $designation,
            'price' => $price,
        ];
    }

    public function createQuickClient(): void
    {
        $validated = $this->validate([
            'quickClientName' => ['required', 'string', 'max:255'],
            'quickClientPhone' => ['nullable', 'string', 'max:50'],
            'quickClientEmail' => ['nullable', 'email', 'max:255'],
            'quickClientType' => ['required', 'in:particulier,institution'],
        ], [
            'quickClientName.required' => 'Le nom du client est obligatoire.',
        ]);

        $client = FinanceClient::query()->create([
            'hopital_id' => current_hopital_id(),
            'name' => trim($validated['quickClientName']),
            'type' => $validated['quickClientType'],
            'phone' => $validated['quickClientPhone'] ?: null,
            'email' => $validated['quickClientEmail'] ?: null,
            'is_active' => true,
        ]);

        $this->finance_client_id = $client->id;
        $this->beneficiary_type = 'client';
        $this->showQuickClientForm = false;
        $this->reset(['quickClientName', 'quickClientPhone', 'quickClientEmail']);
        $this->quickClientType = 'particulier';
    }

    public function updatedItems($value, $key): void
    {
        if (preg_match('/^(\d+)\.(quantity|price|tva|discount)$/', $key, $matches)) {
            $this->recalculateItem((int) $matches[1]);
        }
    }

    protected function recalculateItem(int $index): void
    {
        if (!isset($this->items[$index])) {
            return;
        }

        $quantity = max(1, (float) ($this->items[$index]['quantity'] ?? 1));
        $price = max(0, (float) ($this->items[$index]['price'] ?? 0));
        $tva = max(0, (float) ($this->items[$index]['tva'] ?? 0));
        $discount = min(100, max(0, (float) ($this->items[$index]['discount'] ?? 0)));

        $baseHT = $quantity * $price;
        $totalHT = $baseHT - ($baseHT * $discount / 100);
        $totalTTC = $totalHT + ($totalHT * $tva / 100);

        $this->items[$index]['quantity'] = $quantity;
        $this->items[$index]['price'] = $price;
        $this->items[$index]['tva'] = $tva;
        $this->items[$index]['discount'] = $discount;
        $this->items[$index]['total_ht'] = round($totalHT, 2);
        $this->items[$index]['total_ttc'] = round($totalTTC, 2);
    }

    #[Computed]
    public function totals(): array
    {
        $totalHT = (float) collect($this->items)->sum('total_ht');
        $totalTTC = (float) collect($this->items)->sum('total_ttc');

        return [
            'ht' => $totalHT,
            'tva' => $totalTTC - $totalHT,
            'ttc' => $totalTTC,
        ];
    }

    public function lineTypeLabel(string $type): string
    {
        return match ($type) {
            'acte' => 'Acte',
            'service' => 'Prestation',
            'produit' => 'Produit',
            'autre' => 'Autre',
            default => ucfirst($type),
        };
    }

    public function addItem(): void
    {
        $this->validate([
            'line_type' => ['required', 'in:acte,service,produit,autre'],
            'selectedActeId' => ['required_if:line_type,acte', 'nullable', 'integer', 'exists:actes,id'],
            'selectedMedicamentId' => ['required_if:line_type,produit', 'nullable', 'integer', 'exists:medicaments,id'],
            'designation' => [
                Rule::requiredIf(in_array($this->line_type, ['service', 'autre'], true)),
                'nullable',
                'string',
                'max:255',
            ],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'price' => ['required', 'numeric', 'min:0'],
            'tva' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'selectedActeId.required_if' => 'Veuillez selectionner un acte medical.',
            'selectedMedicamentId.required_if' => 'Veuillez selectionner un medicament de la pharmacie.',
            'designation.required' => 'Veuillez saisir une designation.',
        ]);

        $designation = trim((string) $this->designation);
        $acteId = null;
        $medicamentId = null;

        if ($this->line_type === 'acte') {
            $acte = Acte::query()->find($this->selectedActeId);
            if (! $acte) {
                $this->addError('selectedActeId', 'Acte introuvable.');
                return;
            }
            $acteId = (int) $acte->id;
            $designation = $acte->name;
        } elseif ($this->line_type === 'produit') {
            $details = $this->resolveMedicamentDetails((int) $this->selectedMedicamentId);
            if (! $details) {
                $this->addError('selectedMedicamentId', 'Medicament introuvable dans la pharmacie.');
                return;
            }
            $medicamentId = (int) $this->selectedMedicamentId;
            $designation = $details['designation'];
        } elseif ($designation === '') {
            $this->addError('designation', 'Veuillez saisir une designation.');
            return;
        }

        $baseHT = $this->quantity * $this->price;
        $totalHT = $baseHT - ($baseHT * $this->discount / 100);
        $totalTTC = $totalHT + ($totalHT * $this->tva / 100);

        $this->items[] = [
            'line_type' => $this->line_type,
            'acte_id' => $acteId,
            'medicament_id' => $medicamentId,
            'designation' => $designation,
            'quantity' => (float) $this->quantity,
            'price' => (float) $this->price,
            'tva' => (float) $this->tva,
            'discount' => (float) $this->discount,
            'total_ht' => round($totalHT, 2),
            'total_ttc' => round($totalTTC, 2),
        ];

        $this->reset([
            'acte_departement_id',
            'acte_service_id',
            'selectedActeId',
            'selectedMedicamentId',
            'designation',
        ]);
        $this->quantity = 1;
        $this->price = 0;
        $this->discount = 0;
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function saveDocument()
    {
        $isUpdate = $this->documentId !== null;

        $this->validate([
            'number' => ['required', 'string', 'max:255', Rule::unique('finance_documents', 'number')->ignore($this->documentId)],
            'document_type' => ['required', 'in:devis,facture'],
            'beneficiary_type' => ['required', 'in:patient,client'],
            'dossier_patient_id' => ['required_if:beneficiary_type,patient', 'nullable', 'integer', 'exists:dossier_patients,id'],
            'finance_client_id' => ['required_if:beneficiary_type,client', 'nullable', 'integer', 'exists:finance_clients,id'],
            'status' => ['required', Rule::in($this->allowedStatusesForType($this->document_type))],
            'issue_date' => ['required', 'date'],
            'validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
        ], [
            'dossier_patient_id.required_if' => 'Veuillez selectionner un patient.',
            'finance_client_id.required_if' => 'Veuillez selectionner ou creer un client.',
        ]);

        foreach (array_keys($this->items) as $index) {
            $this->recalculateItem((int) $index);
        }

        $document = DB::transaction(function () {
            $totals = $this->totals;

            $payload = [
                'hopital_id' => current_hopital_id(),
                'beneficiary_type' => $this->beneficiary_type,
                'dossier_patient_id' => $this->beneficiary_type === 'patient' ? $this->dossier_patient_id : null,
                'finance_client_id' => $this->beneficiary_type === 'client' ? $this->finance_client_id : null,
                'document_type' => $this->document_type,
                'status' => $this->status,
                'number' => $this->number,
                'issue_date' => $this->issue_date,
                'valid_until' => date('Y-m-d', strtotime($this->issue_date . " + {$this->validity_days} days")),
                'notes' => $this->notes,
                'total_ht' => $totals['ht'],
                'total_tva' => $totals['tva'],
                'total_ttc' => $totals['ttc'],
                'currency' => strtoupper($this->currency),
                'updated_by' => Auth::id(),
            ];

            if ($this->documentId) {
                $doc = FinanceDocument::query()->where('hopital_id', current_hopital_id())->findOrFail($this->documentId);
                $doc->update($payload);
                $doc->items()->delete();
            } else {
                $payload['created_by'] = Auth::id();
                $doc = FinanceDocument::query()->create($payload);
                $this->documentId = $doc->id;
                $this->mode = 'edit';
            }

            foreach ($this->items as $idx => $item) {
                $doc->items()->create([
                    'line_type' => $item['line_type'],
                    'acte_id' => $item['acte_id'] ?? null,
                    'medicament_id' => $item['medicament_id'] ?? null,
                    'designation' => $item['designation'],
                    'quantity' => $item['quantity'],
                    'price_ht' => $item['price'],
                    'tva' => $item['tva'],
                    'discount' => $item['discount'],
                    'total_ht' => $item['total_ht'],
                    'total_ttc' => $item['total_ttc'],
                    'sort_order' => $idx + 1,
                ]);
            }

            return $doc;
        });

        session()->flash('message', $isUpdate ? 'Document mis a jour avec succes.' : 'Document cree avec succes.');
        return redirect()->route('facturation.documents.edit', $document->id);
    }

    public function loadDocument(int $id): void
    {
        $document = FinanceDocument::query()
            ->with(['items', 'financeClient'])
            ->where('hopital_id', current_hopital_id())
            ->findOrFail($id);

        abort_if($document->document_type === 'avoir', 403, 'Edition indisponible pour les avoirs.');

        $this->documentId = $document->id;
        $this->mode = 'edit';
        $this->document_type = $document->document_type;
        $this->beneficiary_type = $document->beneficiary_type ?: ($document->finance_client_id ? 'client' : 'patient');
        $this->dossier_patient_id = $document->dossier_patient_id;
        $this->finance_client_id = $document->finance_client_id;
        $this->number = $document->number;
        $this->issue_date = optional($document->issue_date)->format('Y-m-d') ?: now()->format('Y-m-d');
        $this->validity_days = $document->valid_until && $document->issue_date
            ? max(1, $document->issue_date->diffInDays($document->valid_until))
            : 15;
        $this->notes = $document->notes;
        $this->status = $document->status;
        $this->currency = $document->currency ?: 'USD';

        $this->items = $document->items
            ->map(fn($item) => [
                'line_type' => $item->line_type,
                'acte_id' => $item->acte_id,
                'medicament_id' => $item->medicament_id,
                'designation' => $item->designation,
                'quantity' => (float) $item->quantity,
                'price' => (float) $item->price_ht,
                'tva' => (float) $item->tva,
                'discount' => (float) $item->discount,
                'total_ht' => (float) $item->total_ht,
                'total_ttc' => (float) $item->total_ttc,
            ])->values()->all();
    }

    protected function nextNumber(string $type): string
    {
        $prefix = match ($type) {
            'devis' => 'DEV',
            'facture' => 'FAC',
            default => 'DOC',
        };

        $seed = FinanceDocument::query()
            ->where('hopital_id', current_hopital_id())
            ->where('document_type', $type)
            ->count() + 1;

        do {
            $candidate = $prefix . '-' . str_pad((string) $seed, 5, '0', STR_PAD_LEFT);
            $exists = FinanceDocument::query()->where('number', $candidate)->exists();
            $seed++;
        } while ($exists);

        return $candidate;
    }

    public function refreshNumber(): void
    {
        $this->number = $this->nextNumber($this->document_type);
    }

    protected function defaultStatusForType(string $type): string
    {
        return $type === 'facture' ? 'provisoire' : 'draft';
    }

    protected function allowedStatusesForType(string $type): array
    {
        return $type === 'facture'
            ? ['provisoire', 'finale', 'encaisse']
            : ['draft', 'sent', 'accepted', 'rejected'];
    }

    public function statusOptions(): array
    {
        if ($this->document_type === 'facture') {
            return [
                ['label' => 'Provisoire', 'value' => 'provisoire'],
                ['label' => 'Finale', 'value' => 'finale'],
                ['label' => 'Encaissee', 'value' => 'encaisse'],
            ];
        }

        return [
            ['label' => 'Brouillon', 'value' => 'draft'],
            ['label' => 'Envoye', 'value' => 'sent'],
            ['label' => 'Accepte', 'value' => 'accepted'],
            ['label' => 'Refuse', 'value' => 'rejected'],
        ];
    }
};
?>

<div class="space-y-5 p-6">
    @if (session()->has('message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    <section class="overflow-hidden rounded-4xl border border-sky-100 bg-linear-to-br from-white via-sky-50/70 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="space-y-3 px-6 py-6 md:px-8">
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Facturation', 'link' => route('facturation.index'), 'icon' => 'banknotes'],
                ['label' => 'Factures & devis', 'link' => route('facturation.documents'), 'icon' => 'document'],
                ['label' => $documentId ? 'Edition' : 'Creation', 'icon' => 'pencil-square'],
            ]" />
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Formulaire unique</p>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white">
                        {{ $documentId ? 'Modifier le document' : 'Nouveau document' }}
                    </h1>
                </div>
                <a href="{{ route('facturation.documents') }}" wire:navigate>
                    <flux:button variant="ghost" icon="arrow-left">Retour a la liste</flux:button>
                </a>
            </div>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[1.1fr,1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="grid grid-cols-1 gap-3">
                <div class="grid gap-3 md:grid-cols-2">
                    <x-select.styled wire:model.live="document_type" label="Type *" :options="[
                        ['label' => 'Devis', 'value' => 'devis'],
                        ['label' => 'Facture', 'value' => 'facture'],
                    ]" select="label:label|value:value" />
                    <x-input wire:model="number" label="Numero *" />
                </div>

                <x-select.styled wire:model.live="beneficiary_type" label="Beneficiaire *" :options="[
                    ['label' => 'Patient', 'value' => 'patient'],
                    ['label' => 'Client', 'value' => 'client'],
                ]" select="label:label|value:value" />

                @if ($beneficiary_type === 'patient')
                    <x-select.styled label="Patient *" wire:model="dossier_patient_id" :request="route('api.patient')"
                        select="label:name|value:id" />
                    @error('dossier_patient_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @else
                    <div class="space-y-3">
                        <x-select.styled label="Client *" wire:model="finance_client_id" :request="route('api.clients')"
                            select="label:name|value:id" />
                        @error('finance_client_id')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        @if ($showQuickClientForm)
                            <div class="rounded-2xl border border-sky-200 bg-sky-50/80 p-4 dark:border-sky-500/20 dark:bg-sky-500/10">
                                <p class="text-sm font-bold text-slate-900 dark:text-white">Nouveau client rapide</p>
                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <x-input wire:model="quickClientName" label="Nom / Raison sociale *" />
                                    <x-select.styled wire:model="quickClientType" label="Type" :options="[
                                        ['label' => 'Particulier', 'value' => 'particulier'],
                                        ['label' => 'Institution', 'value' => 'institution'],
                                    ]" select="label:label|value:value" />
                                    <x-input wire:model="quickClientPhone" label="Telephone" />
                                    <x-input wire:model="quickClientEmail" label="Email" />
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <flux:button wire:click="createQuickClient" variant="primary" size="sm" icon="check">
                                        Enregistrer le client
                                    </flux:button>
                                    <flux:button wire:click="$set('showQuickClientForm', false)" variant="ghost" size="sm">
                                        Annuler
                                    </flux:button>
                                </div>
                            </div>
                        @else
                            <div class="flex flex-wrap gap-2">
                                <flux:button wire:click="$set('showQuickClientForm', true)" variant="ghost" size="sm" icon="plus">
                                    Creer un client rapide
                                </flux:button>
                                <a href="{{ route('facturation.clients') }}" wire:navigate>
                                    <flux:button variant="ghost" size="sm" icon="user-group">Gerer les clients</flux:button>
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="grid gap-3 md:grid-cols-3">
                    <x-input type="date" wire:model="issue_date" label="Date *" />
                    <x-number wire:model="validity_days" label="Validite (jours) *" min="1" max="365" />
                    <x-input wire:model="currency" label="Devise *" />
                </div>

                <x-select.styled wire:model="status" label="Statut *" :options="$this->statusOptions()" select="label:label|value:value" />
                <x-textarea wire:model="notes" label="Conditions / notes" rows="3" />
            </div>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/70">
                <p class="text-sm font-bold text-slate-900 dark:text-white">Ajouter une ligne</p>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <x-select.styled wire:model.live="line_type" label="Type ligne" :options="[
                        ['label' => 'Acte medical', 'value' => 'acte'],
                        ['label' => 'Prestation', 'value' => 'service'],
                        ['label' => 'Produit (pharmacie)', 'value' => 'produit'],
                        ['label' => 'Autre', 'value' => 'autre'],
                    ]" select="label:label|value:value" />

                    @if ($line_type === 'acte')
                        <x-select.styled wire:model.live="acte_departement_id" label="Departement (filtre)"
                            :request="route('api.departements')" select="label:name|value:id" placeholder="Tous" />
                        <x-select.styled wire:model.live="acte_service_id" label="Service hospitalier (filtre)"
                            :request="['url' => route('api.services'), 'params' => ['departement' => $acte_departement_id]]"
                            select="label:name|value:id" placeholder="Tous" />
                        <x-select.styled wire:model.live="selectedActeId" label="Acte *"
                            :request="['url' => route('api.actes'), 'params' => ['departement' => $acte_departement_id, 'service' => $acte_service_id]]"
                            select="label:name|value:id" />
                    @elseif ($line_type === 'service')
                        <x-input wire:model="designation" label="Prestation / service *"
                            placeholder="Ex: Installation, maintenance, conseil, main d oeuvre..." />
                    @elseif ($line_type === 'produit')
                        <x-select.styled wire:model.live="selectedMedicamentId" label="Medicament (pharmacie) *"
                            :request="route('api.medicaments')" select="label:name|value:id" />
                    @else
                        <x-input wire:model="designation" label="Designation *" placeholder="Libelle libre..." />
                    @endif

                    <x-number wire:model="quantity" label="Quantite" min="0.01" step="0.01" />
                    <x-number wire:model="price" label="Prix HT" min="0" step="0.01" />
                    <x-number wire:model="tva" label="TVA %" min="0" step="0.01" />
                    <x-number wire:model="discount" label="Remise %" min="0" max="100" step="0.01" />
                </div>
                <flux:button class="mt-3 w-full" wire:click="addItem" variant="primary" color="indigo" icon="plus">Ajouter la ligne</flux:button>
            </div>
        </div>

        <div class="space-y-4">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-950/70">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900/70">
                        <tr class="text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400">
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Designation</th>
                            <th class="px-3 py-2 text-center">Qte</th>
                            <th class="px-3 py-2 text-right">HT</th>
                            <th class="px-3 py-2 text-right">TTC</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse ($items as $index => $item)
                            <tr wire:key="item-{{ $index }}">
                                <td class="px-3 py-2">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $this->lineTypeLabel($item['line_type']) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" wire:model.live.debounce.300ms="items.{{ $index }}.designation"
                                        class="w-full rounded-lg border border-slate-300 px-2 py-1 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input type="number" step="0.01" min="1" wire:model.live.debounce.300ms="items.{{ $index }}.quantity"
                                        class="w-20 rounded-lg border border-slate-300 px-2 py-1 text-center text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                </td>
                                <td class="px-3 py-2 text-right">{{ number_format((float) $item['total_ht'], 2, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-right font-bold">{{ number_format((float) $item['total_ttc'], 2, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-right">
                                    <button wire:click="removeItem({{ $index }})" class="text-rose-600 hover:text-rose-700">Suppr.</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-slate-500 dark:text-slate-400">Aucune ligne ajoutee.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rounded-2xl bg-slate-900 p-4 text-slate-100">
                <div class="flex items-center justify-between text-sm"><span>Total HT</span><span>{{ number_format($this->totals['ht'], 2, ',', ' ') }} $</span></div>
                <div class="mt-2 flex items-center justify-between text-sm"><span>TVA</span><span>{{ number_format($this->totals['tva'], 2, ',', ' ') }} $</span></div>
                <div class="mt-3 flex items-center justify-between text-lg font-black"><span>Total TTC</span><span class="text-emerald-300">{{ number_format($this->totals['ttc'], 2, ',', ' ') }} $</span></div>
                <flux:button class="mt-4 w-full" wire:click="saveDocument" variant="primary" color="emerald" icon="check">
                    {{ $documentId ? 'Enregistrer modifications' : 'Valider document' }}
                </flux:button>
            </div>
        </div>
    </div>
</div>
