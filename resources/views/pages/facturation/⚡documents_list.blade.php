<?php

use App\Models\facturation\FinanceDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Factures et devis'), Layout('layouts::app.other.facturation')] class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $year = null;
    public string $statusFilter = '';
    public string $typeFilter = '';

    public function mount(): void
    {
        $this->year = now()->year;
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedTypeFilter(): void { $this->resetPage(); }
    public function updatedYear(): void { $this->resetPage(); }

    public function statusMeta(string $status): array
    {
        return match ($status) {
            'draft', 'provisoire' => ['label' => 'Provisoire', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'],
            'sent', 'finale' => ['label' => 'Finale / Envoye', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300'],
            'accepted', 'encaisse' => ['label' => 'Accepte / Encaisse', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'],
            'rejected' => ['label' => 'Refuse', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'],
            'avoir' => ['label' => 'Avoir', 'class' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300'],
            default => ['label' => ucfirst($status), 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'],
        };
    }

    public function documentTypeLabel(string $type): string
    {
        return match ($type) {
            'devis' => 'Devis',
            'facture' => 'Facture',
            'avoir' => 'Avoir',
            default => ucfirst($type),
        };
    }

    protected function nextNumber(string $type): string
    {
        $prefix = match ($type) {
            'devis' => 'DEV',
            'facture' => 'FAC',
            'avoir' => 'AVO',
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

    public function convertToInvoice(int $id): void
    {
        $source = FinanceDocument::query()->with('items')
            ->where('hopital_id', current_hopital_id())->where('document_type', 'devis')->findOrFail($id);

        if (FinanceDocument::query()->where('source_document_id', $source->id)->where('document_type', 'facture')->exists()) {
            session()->flash('message', 'Ce devis est deja converti en facture.');
            return;
        }

        DB::transaction(function () use ($source) {
            $invoice = FinanceDocument::query()->create([
                'hopital_id' => $source->hopital_id,
                'beneficiary_type' => $source->beneficiary_type,
                'dossier_patient_id' => $source->dossier_patient_id,
                'finance_client_id' => $source->finance_client_id,
                'source_document_id' => $source->id,
                'document_type' => 'facture',
                'status' => 'provisoire',
                'number' => $this->nextNumber('facture'),
                'issue_date' => now()->toDateString(),
                'valid_until' => now()->addDays(15)->toDateString(),
                'notes' => $source->notes,
                'total_ht' => $source->total_ht,
                'total_tva' => $source->total_tva,
                'total_ttc' => $source->total_ttc,
                'currency' => $source->currency,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($source->items as $idx => $item) {
                $invoice->items()->create([
                    'line_type' => $item->line_type,
                    'acte_id' => $item->acte_id,
                    'medicament_id' => $item->medicament_id,
                    'designation' => $item->designation,
                    'quantity' => $item->quantity,
                    'price_ht' => $item->price_ht,
                    'tva' => $item->tva,
                    'discount' => $item->discount,
                    'total_ht' => $item->total_ht,
                    'total_ttc' => $item->total_ttc,
                    'sort_order' => $idx + 1,
                ]);
            }

            $source->update(['status' => 'accepted', 'updated_by' => Auth::id()]);
        });

        session()->flash('message', 'Devis converti en facture.');
    }

    public function convertToAvoir(int $id): void
    {
        $source = FinanceDocument::query()->with('items')
            ->where('hopital_id', current_hopital_id())->where('document_type', 'facture')->findOrFail($id);

        if (FinanceDocument::query()->where('source_document_id', $source->id)->where('document_type', 'avoir')->exists()) {
            session()->flash('message', 'Cette facture a deja un avoir.');
            return;
        }

        DB::transaction(function () use ($source) {
            $avoir = FinanceDocument::query()->create([
                'hopital_id' => $source->hopital_id,
                'beneficiary_type' => $source->beneficiary_type,
                'dossier_patient_id' => $source->dossier_patient_id,
                'finance_client_id' => $source->finance_client_id,
                'source_document_id' => $source->id,
                'document_type' => 'avoir',
                'status' => 'avoir',
                'number' => $this->nextNumber('avoir'),
                'issue_date' => now()->toDateString(),
                'notes' => 'Avoir genere depuis la facture ' . $source->number,
                'total_ht' => $source->total_ht,
                'total_tva' => $source->total_tva,
                'total_ttc' => $source->total_ttc,
                'currency' => $source->currency,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($source->items as $idx => $item) {
                $avoir->items()->create([
                    'line_type' => $item->line_type,
                    'acte_id' => $item->acte_id,
                    'medicament_id' => $item->medicament_id,
                    'designation' => $item->designation,
                    'quantity' => $item->quantity,
                    'price_ht' => $item->price_ht,
                    'tva' => $item->tva,
                    'discount' => $item->discount,
                    'total_ht' => $item->total_ht,
                    'total_ttc' => $item->total_ttc,
                    'sort_order' => $idx + 1,
                ]);
            }

            $source->update(['status' => 'encaisse', 'updated_by' => Auth::id()]);
        });

        session()->flash('message', 'Facture transformee en avoir.');
    }

    #[Computed]
    public function rows()
    {
        return FinanceDocument::query()->with(['dossierPatient', 'financeClient', 'sourceDocument'])
            ->where('hopital_id', current_hopital_id())
            ->when($this->year, fn($q) => $q->whereYear('issue_date', $this->year))
            ->when($this->typeFilter !== '', fn($q) => $q->where('document_type', $this->typeFilter))
            ->when($this->statusFilter !== '', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('number', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhereHas('dossierPatient', fn($sq) => $sq
                            ->where('nom', 'like', $term)
                            ->orWhere('postnom', 'like', $term)
                            ->orWhere('prenom', 'like', $term)
                            ->orWhere('nin', 'like', $term))
                        ->orWhereHas('financeClient', fn($sq) => $sq
                            ->where('name', 'like', $term)
                            ->orWhere('phone', 'like', $term)
                            ->orWhere('email', 'like', $term));
                });
            })
            ->latest('issue_date')
            ->paginate(15);
    }

    #[Computed]
    public function stats(): array
    {
        $docs = FinanceDocument::query()->where('hopital_id', current_hopital_id())
            ->when($this->year, fn($q) => $q->whereYear('issue_date', $this->year))->get();

        return [
            'all' => $docs->count(),
            'devis' => $docs->where('document_type', 'devis')->count(),
            'factures' => $docs->where('document_type', 'facture')->count(),
            'avoirs' => $docs->where('document_type', 'avoir')->count(),
            'amount' => (float) $docs->sum('total_ttc'),
        ];
    }
};
?>

<div class="space-y-5 p-6">
    @if (session()->has('message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('message') }}</div>
    @endif

    <section class="overflow-hidden rounded-4xl border border-blue-100 bg-gradient-to-br from-white via-blue-50/60 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="space-y-6 px-6 py-6 md:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Facturation', 'link' => route('facturation.index'), 'icon' => 'banknotes'],
                        ['label' => 'Factures & devis', 'icon' => 'document'],
                    ]" />
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-blue-600 dark:text-blue-300">Pilotage commercial</p>
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Factures et devis</h1>
                </div>
                <div class="text-left lg:text-right">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Montant cumule TTC</p>
                    <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ number_format($this->stats['amount'], 2, ',', ' ') }} $</p>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-2xl border border-blue-100 bg-white/85 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80"><p class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Total docs</p><p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $this->stats['all'] }}</p></div>
                <div class="rounded-2xl border border-blue-100 bg-white/85 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80"><p class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Devis</p><p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $this->stats['devis'] }}</p></div>
                <div class="rounded-2xl border border-blue-100 bg-white/85 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80"><p class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Factures</p><p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $this->stats['factures'] }}</p></div>
                <div class="rounded-2xl border border-blue-100 bg-white/85 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80"><p class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Avoirs</p><p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $this->stats['avoirs'] }}</p></div>
                <div class="flex items-end justify-end gap-2">
                    <a href="{{ route('facturation.documents.create', ['type' => 'devis']) }}" wire:navigate><flux:button variant="primary" color="indigo" icon="plus">Nouveau devis</flux:button></a>
                    <a href="{{ route('facturation.documents.create', ['type' => 'facture']) }}" wire:navigate><flux:button variant="primary" color="emerald" icon="plus">Nouvelle facture</flux:button></a>
                </div>
            </div>
        </div>
    </section>

    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="grid gap-3 md:grid-cols-4">
            <x-input wire:model.live.debounce.300ms="search" label="Recherche" placeholder="Numero, patient, client..." />
            <x-number wire:model.live="year" min="2020" max="2100" label="Annee" />
            <x-select.styled wire:model.live="typeFilter" label="Type" :options="[
                ['label' => 'Tous', 'value' => ''],
                ['label' => 'Devis', 'value' => 'devis'],
                ['label' => 'Factures', 'value' => 'facture'],
                ['label' => 'Avoirs', 'value' => 'avoir'],
            ]" select="label:label|value:value" />
            <x-input wire:model.live.debounce.300ms="statusFilter" label="Statut" placeholder="draft, finale..." />
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($this->rows as $row)
            @php($meta = $this->statusMeta($row->status))
            <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-lg font-black text-slate-900 dark:text-white">{{ $row->number }}</p>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $this->documentTypeLabel($row->document_type) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $row->beneficiaryLabel() }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Date: {{ optional($row->issue_date)->format('d/m/Y') }} · Validite: {{ optional($row->valid_until)->format('d/m/Y') ?: '-' }}</p>
                    </div>
                    <div class="text-left lg:text-right">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Montant TTC</p>
                        <p class="text-2xl font-black text-indigo-700 dark:text-indigo-300">{{ number_format((float) $row->total_ttc, 2, ',', ' ') }} $</p>
                        <div class="mt-2 flex flex-wrap gap-2 lg:justify-end">
                            @if ($row->document_type !== 'avoir')
                                <a href="{{ route('facturation.documents.edit', $row->id) }}" wire:navigate><flux:button size="sm" variant="ghost" icon="pencil-square">Modifier</flux:button></a>
                            @endif
                            @if ($row->document_type === 'devis')
                                <flux:button size="sm" variant="primary" color="indigo" wire:click="convertToInvoice({{ $row->id }})" icon="document-duplicate">Convertir facture</flux:button>
                            @endif
                            @if ($row->document_type === 'facture')
                                <flux:button size="sm" variant="danger" wire:click="convertToAvoir({{ $row->id }})" icon="arrow-uturn-left">Generer avoir</flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center dark:border-slate-700 dark:bg-slate-900/40">
                <p class="text-base font-bold text-slate-700 dark:text-slate-200">Aucun document trouve</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Creez un devis ou une facture pour demarrer.</p>
            </div>
        @endforelse
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-950/70">{{ $this->rows->links() }}</div>
</div>
