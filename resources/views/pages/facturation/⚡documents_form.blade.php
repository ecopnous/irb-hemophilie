<?php

use App\Models\Configs\Acte;
use App\Models\facturation\FinanceDocument;
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
    public ?int $dossier_patient_id = null;
    public string $number = '';
    public string $issue_date = '';
    public int $validity_days = 15;
    public ?string $notes = null;
    public string $status = 'draft';
    public string $currency = 'USD';

    public string $line_type = 'acte';
    public ?int $selectedActeId = null;
    public ?string $designation = null;
    public float $quantity = 1;
    public float $price = 0;
    public float $tva = 16;
    public float $discount = 0;

    public array $items = [];

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
    }

    public function updatedDocumentType(): void
    {
        if ($this->mode === 'create') {
            $this->status = $this->defaultStatusForType($this->document_type);
            $this->refreshNumber();
        }
    }

    public function updatedSelectedActeId($value): void
    {
        if ($this->line_type !== 'acte' || !$value) {
            return;
        }

        $acte = Acte::query()->find($value);
        if ($acte) {
            $this->designation = $acte->name;
            $this->price = (float) ($acte->base_price ?? $acte->montant ?? 0);
        }
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

    #[Computed]
    public function actes()
    {
        return Acte::query()->orderBy('name')->limit(300)->get();
    }

    public function addItem(): void
    {
        $this->validate([
            'line_type' => ['required', 'in:acte,service'],
            'selectedActeId' => [$this->line_type === 'acte' ? 'required' : 'nullable', 'integer', 'exists:actes,id'],
            'designation' => [$this->line_type === 'service' ? 'required' : 'nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'tva' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $designation = $this->designation;
        if ($this->line_type === 'acte' && $this->selectedActeId) {
            $acte = Acte::query()->find($this->selectedActeId);
            if (!$acte) {
                $this->addError('selectedActeId', 'Acte introuvable.');
                return;
            }
            $designation = $acte->name;
        }

        $baseHT = $this->quantity * $this->price;
        $totalHT = $baseHT - ($baseHT * $this->discount / 100);
        $totalTTC = $totalHT + ($totalHT * $this->tva / 100);

        $this->items[] = [
            'line_type' => $this->line_type,
            'acte_id' => $this->line_type === 'acte' ? $this->selectedActeId : null,
            'designation' => $designation,
            'quantity' => (float) $this->quantity,
            'price' => (float) $this->price,
            'tva' => (float) $this->tva,
            'discount' => (float) $this->discount,
            'total_ht' => round($totalHT, 2),
            'total_ttc' => round($totalTTC, 2),
        ];

        $this->reset(['selectedActeId', 'designation']);
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
            'status' => ['required', Rule::in($this->allowedStatusesForType($this->document_type))],
            'dossier_patient_id' => ['nullable', 'integer', 'exists:dossier_patients,id'],
            'issue_date' => ['required', 'date'],
            'validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
        ]);

        foreach (array_keys($this->items) as $index) {
            $this->recalculateItem((int) $index);
        }

        $document = DB::transaction(function () {
            $totals = $this->totals;

            $payload = [
                'hopital_id' => current_hopital_id(),
                'dossier_patient_id' => $this->dossier_patient_id ?: null,
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
                    'acte_id' => $item['acte_id'],
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
            ->with('items')
            ->where('hopital_id', current_hopital_id())
            ->findOrFail($id);

        abort_if($document->document_type === 'avoir', 403, 'Edition indisponible pour les avoirs.');

        $this->documentId = $document->id;
        $this->mode = 'edit';
        $this->document_type = $document->document_type;
        $this->dossier_patient_id = $document->dossier_patient_id;
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

                <x-select.styled label="Patient (optionnel)" wire:model="dossier_patient_id" :request="route('api.patient')"
                    select="label:name|value:id" />

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
                        ['label' => 'Acte', 'value' => 'acte'],
                        ['label' => 'Service', 'value' => 'service'],
                    ]" select="label:label|value:value" />
                    @if ($line_type === 'acte')
                        <x-select.styled wire:model="selectedActeId" label="Acte"
                            :options="$this->actes->map(fn($a) => ['label' => $a->name, 'value' => (string) $a->id])->all()"
                            select="label:label|value:value" />
                    @else
                        <x-input wire:model="designation" label="Designation service" />
                    @endif
                    <x-number wire:model="quantity" label="Quantite" min="1" step="0.01" />
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
                                <td colspan="5" class="px-3 py-6 text-center text-slate-500 dark:text-slate-400">Aucune ligne ajoutee.</td>
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
