<?php

use App\Enums\PatientArchiveCategory;
use App\Models\DossierPatient;
use App\Models\PatientArchiveDocument;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Archivages patient'), Layout('layouts::app.other.profil_medical')] class extends Component {
    public DossierPatient $patient;

    public string $search = '';
    public string $filterCategory = '';
    public string $sortBy = 'recent';

    public bool $showUploadModal = false;
    public bool $showDetailModal = false;
    public ?int $selectedDocumentId = null;
    public bool $confirmingDelete = false;

    public string $uploadTitle = '';
    public string $uploadDescription = '';
    public string $uploadCategory = 'consultation_externe';
    public string $uploadSource = '';
    public ?string $uploadDocumentDate = null;
    public bool $uploadConfidential = false;

    public ?string $stagedFilePath = null;

    public ?string $stagedOriginalName = null;

    public ?string $stagedMimeType = null;

    public int $stagedFileSize = 0;

    public function mount(int $id): void
    {
        abort_unless(current_hopital_id(), 403, 'Selectionnez un hopital.');

        $this->patient = DossierPatient::query()
            ->whereHopitalId(current_hopital_id())
            ->findOrFail($id);
    }

    public function updatedSearch(): void
    {
        //
    }

    public function openUploadModal(): void
    {
        $this->resetUploadForm();
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->resetUploadForm();
    }

    public function updatedShowUploadModal(bool $open): void
    {
        if (! $open) {
            $this->resetUploadForm();
        }
    }

    public function openDetail(int $documentId): void
    {
        $this->selectedDocumentId = $documentId;
        $this->confirmingDelete = false;
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedDocumentId = null;
        $this->confirmingDelete = false;
    }

    public function uploadDocument(): void
    {
        if (! filled($this->stagedFilePath) || ! Storage::disk('local')->exists($this->stagedFilePath)) {
            $this->addError('uploadFile', 'Veuillez selectionner un fichier valide.');

            return;
        }

        $validated = $this->validate([
            'uploadTitle' => ['required', 'string', 'max:200'],
            'uploadDescription' => ['nullable', 'string', 'max:2000'],
            'uploadCategory' => ['required', 'in:' . implode(',', array_column(PatientArchiveCategory::options(), 'value'))],
            'uploadSource' => ['nullable', 'string', 'max:200'],
            'uploadDocumentDate' => ['nullable', 'date', 'before_or_equal:today'],
            'uploadConfidential' => ['boolean'],
        ], [
            'uploadTitle.required' => 'Le titre du document est obligatoire.',
        ]);

        $expectedPrefix = 'patient-archives-staging/' . $this->patient->id . '/';
        abort_unless(str_starts_with($this->stagedFilePath, $expectedPrefix), 403);

        $finalPath = 'patient-archives/' . $this->patient->id . '/' . basename($this->stagedFilePath);
        Storage::disk('local')->move($this->stagedFilePath, $finalPath);
        $this->stagedFilePath = null;

        PatientArchiveDocument::query()->create([
            'dossier_patient_id' => $this->patient->id,
            'user_id' => auth()->id(),
            'hopital_id' => current_hopital_id(),
            'title' => $validated['uploadTitle'],
            'description' => $validated['uploadDescription'] ?: null,
            'category' => $validated['uploadCategory'],
            'source_establishment' => $validated['uploadSource'] ?: null,
            'document_date' => $validated['uploadDocumentDate'] ?: null,
            'original_filename' => $this->stagedOriginalName,
            'path' => $finalPath,
            'mime_type' => $this->stagedMimeType ?: 'application/octet-stream',
            'size' => $this->stagedFileSize,
            'is_confidential' => $validated['uploadConfidential'] ?? false,
        ]);

        $this->showUploadModal = false;
        $this->resetUploadForm();
        unset($this->documents, $this->stats, $this->selectedDocument);

        Flux::toast(
            variant: 'success',
            heading: 'Document archive',
            text: 'Le document a ete ajoute au dossier du patient.',
        );
    }

    public function registerStagedUpload(string $path, string $originalName, string $mimeType, int $size): void
    {
        $expectedPrefix = 'patient-archives-staging/' . $this->patient->id . '/';

        if (! str_starts_with($path, $expectedPrefix) || ! Storage::disk('local')->exists($path)) {
            $this->addError('uploadFile', 'Fichier invalide ou expire. Veuillez reessayer.');

            return;
        }

        $this->clearStagedFile();
        $this->resetValidation();

        $this->stagedFilePath = $path;
        $this->stagedOriginalName = $originalName;
        $this->stagedMimeType = $mimeType;
        $this->stagedFileSize = $size;

        if (! filled($this->uploadTitle)) {
            $this->uploadTitle = pathinfo($originalName, PATHINFO_FILENAME);
        }
    }

    public function removeStagedFile(): void
    {
        $this->clearStagedFile();
        $this->resetValidation();
    }

    public function deleteDocument(): void
    {
        $document = $this->selectedDocument;

        if ($document === null) {
            return;
        }

        $document->deleteFile();
        $document->delete();

        $this->closeDetail();
        unset($this->documents, $this->stats);

        Flux::toast(
            variant: 'success',
            heading: 'Document supprime',
            text: 'Le document a ete retire des archivages.',
        );
    }

    #[Computed]
    public function documents(): Collection
    {
        return PatientArchiveDocument::query()
            ->with('user:id,name')
            ->where('dossier_patient_id', $this->patient->id)
            ->whereHopitalId(current_hopital_id())
            ->when(filled($this->filterCategory), fn ($q) => $q->where('category', $this->filterCategory))
            ->when(filled($this->search), function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('source_establishment', 'like', $term)
                        ->orWhere('original_filename', 'like', $term);
                });
            })
            ->when($this->sortBy === 'recent', fn ($q) => $q->orderByDesc('created_at'))
            ->when($this->sortBy === 'oldest', fn ($q) => $q->orderBy('created_at'))
            ->when($this->sortBy === 'document_date', fn ($q) => $q->orderByDesc('document_date')->orderByDesc('created_at'))
            ->when($this->sortBy === 'title', fn ($q) => $q->orderBy('title'))
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $all = PatientArchiveDocument::query()
            ->where('dossier_patient_id', $this->patient->id)
            ->whereHopitalId(current_hopital_id())
            ->get();

        $sources = $all->pluck('source_establishment')->filter()->unique()->count();
        $thisYear = $all->filter(fn ($doc) => $doc->created_at?->isCurrentYear())->count();
        $totalSize = $all->sum('size');

        return [
            'total' => $all->count(),
            'this_year' => $thisYear,
            'sources' => $sources,
            'total_size' => $this->humanBytes($totalSize),
            'by_category' => $all->groupBy(fn ($doc) => $doc->category->value)->map->count(),
        ];
    }

    #[Computed]
    public function selectedDocument(): ?PatientArchiveDocument
    {
        if ($this->selectedDocumentId === null) {
            return null;
        }

        return PatientArchiveDocument::query()
            ->with('user:id,name')
            ->where('dossier_patient_id', $this->patient->id)
            ->whereHopitalId(current_hopital_id())
            ->find($this->selectedDocumentId);
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return PatientArchiveCategory::options();
    }

    public function toneClasses(string $tone): string
    {
        return match ($tone) {
            'indigo' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300',
            'cyan' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300',
            'sky' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
            'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
            'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
            'rose' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
            'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
        };
    }

    public function iconBgClasses(string $tone): string
    {
        return match ($tone) {
            'indigo' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300',
            'cyan' => 'bg-cyan-50 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-300',
            'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300',
            'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
            'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
            'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
            'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
            'slate' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
            default => 'bg-zinc-50 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
        };
    }

    protected function resetUploadForm(): void
    {
        $this->clearStagedFile();
        $this->uploadTitle = '';
        $this->uploadDescription = '';
        $this->uploadCategory = PatientArchiveCategory::ConsultationExterne->value;
        $this->uploadSource = '';
        $this->uploadDocumentDate = null;
        $this->uploadConfidential = false;
        $this->resetValidation();
    }

    protected function clearStagedFile(): void
    {
        if (filled($this->stagedFilePath) && Storage::disk('local')->exists($this->stagedFilePath)) {
            Storage::disk('local')->delete($this->stagedFilePath);
        }

        $this->stagedFilePath = null;
        $this->stagedOriginalName = null;
        $this->stagedMimeType = null;
        $this->stagedFileSize = 0;
    }

    public function humanBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 1) . ' Go';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' Mo';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' Ko';
        }

        return $bytes . ' o';
    }
};
?>

<div class="px-4 pb-12 lg:px-8">
    <x-patient.patient-profil-header
        :nav="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Dossiers patients', 'link' => 'patient.index', 'icon' => 'folder'],
            ['label' => $patient->nin, 'link' => route('patient.show', $patient->id), 'icon' => 'identification'],
        ]"
        :patient="$patient"
        :current_patient="$patient->id"
    >
        <x-slot name="title">Archivages externes</x-slot>
        <x-slot name="subtitle">
            Documents hors plateforme · {{ ucfirst($patient->prenom) }} {{ ucfirst($patient->nom) }}
        </x-slot>
    </x-patient.patient-profil-header>

    {{-- Bandeau informatif --}}
    <div class="mb-6 overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-white to-slate-50 p-5 shadow-sm dark:border-indigo-500/20 dark:from-indigo-950/40 dark:via-slate-900 dark:to-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30">
                    <flux:icon.archive-box-arrow-down class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">Coffre-fort documentaire du patient</p>
                    <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        Centralisez ici les pièces issues d'autres hôpitaux, cliniques ou systèmes d'information :
                        anciennes consultations, comptes rendus, imageries, bilans biologiques, ordonnances et certificats.
                    </p>
                </div>
            </div>
            <flux:button variant="primary" icon="arrow-up-tray" wire:click="openUploadModal" class="shrink-0">
                Ajouter un document
            </flux:button>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Documents archivés</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['total'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $this->stats['total_size'] }} au total</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Ajoutés cette année</p>
            <p class="mt-2 text-3xl font-black text-indigo-600 dark:text-indigo-400">{{ $this->stats['this_year'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Nouveaux dépôts en {{ now()->year }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Établissements sources</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['sources'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Hôpitaux / cliniques référencés</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Catégories</p>
            <div class="mt-3 flex flex-wrap gap-1.5">
                @forelse ($this->stats['by_category'] as $cat => $count)
                    @php($category = \App\Enums\PatientArchiveCategory::from($cat))
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $this->toneClasses($category->tone()) }}">
                        {{ $count }} {{ Str::limit($category->label(), 12) }}
                    </span>
                @empty
                    <span class="text-xs text-slate-400">Aucune catégorie</span>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[260px,1fr]">
        {{-- Filtres latéraux --}}
        <aside class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="mb-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Recherche</p>
                <div class="relative">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Titre, source, fichier..."
                        class="w-full rounded-xl border border-slate-200 py-2.5 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950" />
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="mb-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Catégorie</p>
                <div class="space-y-1">
                    <button type="button" wire:click="$set('filterCategory', '')"
                        class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition {{ $filterCategory === '' ? 'bg-indigo-50 font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                        <span>Toutes</span>
                        <span class="text-xs opacity-60">{{ $this->stats['total'] }}</span>
                    </button>
                    @foreach ($this->categoryOptions as $option)
                        @php($count = $this->stats['by_category'][$option['value']] ?? 0)
                        <button type="button" wire:click="$set('filterCategory', '{{ $option['value'] }}')"
                            class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm transition {{ $filterCategory === $option['value'] ? 'bg-indigo-50 font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <flux:icon :icon="$option['icon']" class="h-4 w-4 shrink-0 opacity-60" />
                            <span class="min-w-0 flex-1 truncate">{{ $option['label'] }}</span>
                            @if ($count > 0)
                                <span class="text-xs opacity-60">{{ $count }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="mb-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Tri</p>
                <select wire:model.live="sortBy"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <option value="recent">Plus récents</option>
                    <option value="oldest">Plus anciens</option>
                    <option value="document_date">Date du document</option>
                    <option value="title">Titre (A → Z)</option>
                </select>
            </div>
        </aside>

        {{-- Grille de documents --}}
        <section>
            @if ($this->documents->isEmpty())
                <div class="flex min-h-[420px] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 dark:bg-slate-800">
                        <flux:icon.folder-open class="h-10 w-10 text-slate-300 dark:text-slate-600" />
                    </div>
                    <h3 class="mt-6 text-lg font-bold text-slate-900 dark:text-white">
                        @if (filled($search) || filled($filterCategory))
                            Aucun document ne correspond à vos filtres
                        @else
                            Aucun document archivé pour l'instant
                        @endif
                    </h3>
                    <p class="mt-2 max-w-md text-sm text-slate-500">
                        @if (filled($search) || filled($filterCategory))
                            Modifiez la recherche ou sélectionnez une autre catégorie.
                        @else
                            Importez les dossiers externes du patient : PDF de consultations, radios scannées,
                            résultats de laboratoire d'un autre établissement, etc.
                        @endif
                    </p>
                    @if (blank($search) && blank($filterCategory))
                        <flux:button class="mt-6" variant="primary" icon="arrow-up-tray" wire:click="openUploadModal">
                            Importer le premier document
                        </flux:button>
                    @endif
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($this->documents as $document)
                        <article wire:key="archive-{{ $document->id }}"
                            class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-500/30">
                            {{-- En-tête carte --}}
                            <div class="flex items-start gap-3 border-b border-slate-100 p-4 dark:border-slate-800">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $this->iconBgClasses($document->category->tone()) }}">
                                    <flux:icon :icon="$document->fileIcon()" class="h-5 w-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:bg-slate-800">
                                            {{ $document->fileExtension() }}
                                        </span>
                                        @if ($document->is_confidential)
                                            <span class="rounded-md bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-600 dark:bg-rose-500/15 dark:text-rose-300">
                                                Confidentiel
                                            </span>
                                        @endif
                                    </div>
                                    <h3 class="mt-1 line-clamp-2 text-sm font-bold text-slate-900 dark:text-white">
                                        {{ $document->title }}
                                    </h3>
                                </div>
                            </div>

                            {{-- Corps --}}
                            <div class="flex flex-1 flex-col gap-3 p-4">
                                <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-[11px] font-bold {{ $this->toneClasses($document->category->tone()) }}">
                                    {{ $document->category->label() }}
                                </span>

                                @if ($document->source_establishment)
                                    <p class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                        <flux:icon.building-office-2 class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">{{ $document->source_establishment }}</span>
                                    </p>
                                @endif

                                <div class="mt-auto flex items-center justify-between text-xs text-slate-400">
                                    <span>
                                        @if ($document->document_date)
                                            Doc. {{ $document->document_date->format('d/m/Y') }}
                                        @else
                                            Ajouté {{ $document->created_at->format('d/m/Y') }}
                                        @endif
                                    </span>
                                    <span>{{ $document->humanSize() }}</span>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex border-t border-slate-100 dark:border-slate-800">
                                <button type="button" wire:click="openDetail({{ $document->id }})"
                                    class="flex flex-1 items-center justify-center gap-1.5 py-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-indigo-600 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-indigo-400">
                                    <flux:icon.eye class="h-4 w-4" />
                                    Détails
                                </button>
                                <a href="{{ route('patient.archivages.download', [$patient->id, $document->id]) }}"
                                    class="flex flex-1 items-center justify-center gap-1.5 border-l border-slate-100 py-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-indigo-600 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-indigo-400">
                                    <flux:icon.arrow-down-tray class="h-4 w-4" />
                                    Télécharger
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- Modal upload --}}
    <flux:modal wire:model.self="showUploadModal" class="max-w-2xl">
        <form wire:submit="uploadDocument" class="space-y-5">
            <div>
                <flux:heading size="lg">Archiver un document externe</flux:heading>
                <flux:subheading>
                    Document provenant d'un autre établissement ou système d'information
                </flux:subheading>
            </div>

            {{-- Zone de dépôt --}}
            <div
                x-data="{
                    dragging: false,
                    uploading: false,
                    error: null,
                    async handleFiles(files) {
                        if (!files?.length) return;
                        const file = files[0];
                        this.uploading = true;
                        this.error = null;
                        const formData = new FormData();
                        formData.append('file', file);
                        try {
                            const response = await fetch('{{ route('patient.archivages.upload', $patient->id) }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: formData,
                                credentials: 'same-origin',
                            });
                            const data = await response.json();
                            if (!response.ok) {
                                const message = data.message
                                    || data.errors?.file?.[0]
                                    || 'Echec du chargement du fichier.';
                                throw new Error(message);
                            }
                            await $wire.registerStagedUpload(data.path, data.original_name, data.mime_type, data.size);
                        } catch (exception) {
                            this.error = exception.message || 'Echec du chargement du fichier.';
                        } finally {
                            this.uploading = false;
                            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                        }
                    },
                }"
                class="relative overflow-hidden rounded-2xl border-2 border-dashed border-indigo-200 bg-indigo-50/50 p-6 text-center transition dark:border-indigo-500/30 dark:bg-indigo-950/20"
                x-on:dragover.prevent="dragging = true"
                x-on:dragleave.prevent="dragging = false"
                x-on:drop.prevent="dragging = false; handleFiles($event.dataTransfer.files)"
                x-bind:class="dragging ? 'border-indigo-400 bg-indigo-100/60 dark:bg-indigo-950/40' : ''"
            >
                <flux:icon.cloud-arrow-up class="mx-auto h-10 w-10 text-indigo-400" />
                <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Glissez un fichier ici ou cliquez pour parcourir
                </p>
                <p class="mt-1 text-xs text-slate-500">PDF, images, Word, Excel — max. 25 Mo</p>

                <input
                    type="file"
                    x-ref="fileInput"
                    class="hidden"
                    accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.tif,.tiff"
                    x-on:change="handleFiles($refs.fileInput.files)"
                />

                <button
                    type="button"
                    x-on:click="$refs.fileInput.click()"
                    class="relative z-10 mt-4 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm ring-1 ring-indigo-100 transition hover:bg-indigo-50 dark:bg-slate-900 dark:text-indigo-300 dark:ring-indigo-500/30 dark:hover:bg-slate-800"
                >
                    <flux:icon.folder-open class="h-4 w-4" />
                    Parcourir les fichiers
                </button>

                <p x-show="uploading" x-cloak class="mt-3 text-xs font-medium text-indigo-600">
                    Chargement et verification du fichier...
                </p>

                <p x-show="error" x-text="error" x-cloak class="mt-3 text-xs text-red-600"></p>

                @if ($stagedOriginalName)
                    <div class="relative z-10 mx-auto mt-4 flex max-w-md items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-left dark:border-emerald-500/30 dark:bg-emerald-950/30">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ $stagedOriginalName }}</p>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400">Fichier prêt · {{ $this->humanBytes($stagedFileSize) }}</p>
                        </div>
                        <button type="button" wire:click="removeStagedFile"
                            class="shrink-0 text-xs font-semibold text-rose-600 hover:text-rose-700">
                            Retirer
                        </button>
                    </div>
                @endif

                @error('uploadFile')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Titre du document *</label>
                    <input type="text" wire:model="uploadTitle" placeholder="Ex: Consultation cardiologie — Hôpital X"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900" />
                    @error('uploadTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Catégorie *</label>
                    <select wire:model="uploadCategory"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900">
                        @foreach ($this->categoryOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Date du document</label>
                    <input type="date" wire:model="uploadDocumentDate" max="{{ now()->format('Y-m-d') }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Établissement source</label>
                    <input type="text" wire:model="uploadSource" placeholder="Ex: CHU de Kinshasa, Clinique ABC..."
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Notes / contexte clinique</label>
                    <textarea wire:model="uploadDescription" rows="3" placeholder="Motif de la consultation, médecin référent, éléments importants..."
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-900"></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" wire:model="uploadConfidential" class="rounded border-slate-300 text-indigo-600" />
                        Marquer comme confidentiel (accès restreint au dossier)
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                <flux:button type="button" variant="ghost" wire:click="closeUploadModal">Annuler</flux:button>
                @if ($stagedFilePath)
                    <flux:button type="submit" variant="primary" icon="archive-box-arrow-down"
                        wire:loading.attr="disabled" wire:target="uploadDocument">
                        Archiver le document
                    </flux:button>
                @else
                    <flux:button type="button" variant="primary" icon="archive-box-arrow-down" disabled>
                        Archiver le document
                    </flux:button>
                @endif
            </div>
        </form>
    </flux:modal>

    {{-- Modal détail --}}
    <flux:modal wire:model.self="showDetailModal" class="max-w-xl">
        @if ($this->selectedDocument)
            @php($doc = $this->selectedDocument)
            <div class="space-y-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $this->iconBgClasses($doc->category->tone()) }}">
                        <flux:icon :icon="$doc->fileIcon()" class="h-7 w-7" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $this->toneClasses($doc->category->tone()) }}">
                                {{ $doc->category->label() }}
                            </span>
                            @if ($doc->is_confidential)
                                <span class="rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-bold text-rose-600">Confidentiel</span>
                            @endif
                        </div>
                        <flux:heading size="lg" class="mt-2">{{ $doc->title }}</flux:heading>
                        <p class="mt-1 text-sm text-slate-500">{{ $doc->original_filename }} · {{ $doc->humanSize() }}</p>
                    </div>
                </div>

                <dl class="grid gap-3 rounded-xl bg-slate-50 p-4 text-sm dark:bg-slate-800/50">
                    @if ($doc->source_establishment)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Établissement</dt>
                            <dd class="font-medium text-slate-900 dark:text-white text-right">{{ $doc->source_establishment }}</dd>
                        </div>
                    @endif
                    @if ($doc->document_date)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Date du document</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">{{ $doc->document_date->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Archivé le</dt>
                        <dd class="font-medium text-slate-900 dark:text-white">{{ $doc->created_at->format('d/m/Y à H:i') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Par</dt>
                        <dd class="font-medium text-slate-900 dark:text-white">{{ $doc->uploaderName() }}</dd>
                    </div>
                </dl>

                @if ($doc->description)
                    <div>
                        <p class="mb-1 text-[11px] font-bold uppercase tracking-widest text-slate-400">Notes</p>
                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $doc->description }}</p>
                    </div>
                @endif

                <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <flux:button href="{{ route('patient.archivages.download', [$patient->id, $doc->id]) }}" icon="arrow-down-tray" variant="primary">
                        Télécharger
                    </flux:button>
                    <flux:button type="button" variant="ghost" wire:click="$set('confirmingDelete', true)" icon="trash" class="text-rose-600">
                        Supprimer
                    </flux:button>
                </div>

                @if ($confirmingDelete)
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-500/30 dark:bg-rose-950/30">
                        <p class="text-sm font-semibold text-rose-800 dark:text-rose-300">Confirmer la suppression ?</p>
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">Cette action est irréversible. Le fichier sera définitivement supprimé.</p>
                        <div class="mt-3 flex gap-2">
                            <flux:button size="sm" variant="danger" wire:click="deleteDocument">Oui, supprimer</flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="$set('confirmingDelete', false)">Annuler</flux:button>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </flux:modal>
</div>
