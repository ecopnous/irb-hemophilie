<?php

use App\Models\Laboratoire;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public Laboratoire $labo;
    public bool $showPrelevementModal = false;
    public bool $showValidationModal = false;
    public ?string $prelevement_date = null;
    public ?string $prelevement_heure = null;
    public $preleveur_id = null;
    public ?string $commentaire = '';
    public ?string $successMessage = null;
    public $actes_labo;
    public array $resultats = [];
    public array $selectedValidationActeIds = [];
    public ?string $notes = '';
    public ?string $antibiotiques = '';
    public ?string $renseignement = '';

    public function mount(int $id): void
    {
        $this->loadLaboratoire($id);
    }

    protected function loadLaboratoire(int $id): void
    {
        $this->labo = Laboratoire::query()
            ->with(['consultation.dossierPatient', 'consultation.actes' => fn($query) => $query->wherePivot('ref', 'labo')->with('service'), 'consultation.user', 'consultation.departement', 'userPreleveur', 'userValideur'])
            ->findOrFail($id);

        $this->syncStateFromLaboratoire();
    }

    protected function syncStateFromLaboratoire(): void
    {
        $this->prelevement_date = $this->labo->date_heure_prelevemnt?->format('Y-m-d');
        $this->prelevement_heure = $this->labo->date_heure_prelevemnt?->format('H:i');
        $this->preleveur_id = $this->labo->user_id;
        $this->renseignement = (string) ($this->labo->renseignement ?? '');
        $this->notes = (string) ($this->labo->note ?? '');
        $this->antibiotiques = (string) ($this->labo->antibiotique ?? '');
        $this->commentaire = (string) ($this->labo->commentaire ?? '');
        $this->actes_labo = $this->labo->consultation->actes;
        $this->selectedValidationActeIds = [];

        $this->resultats = [];
        foreach ($this->actes_labo as $acte) {
            $this->resultats[$acte->id] = data_get($acte, 'pivot.resultat', '');
        }
    }

    public function acteIsValidated($acte): bool
    {
        $value = data_get($acte, 'pivot.valide');

        if (is_bool($value) || is_int($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    public function actePivotValue($acte, string $key, $default = null)
    {
        return data_get($acte, "pivot.{$key}", $default);
    }

    protected function laboratoireActeIds(): array
    {
        return collect($this->actes_labo)->pluck('id')->map(fn($id) => (int) $id)->values()->all();
    }

    protected function currentValidatedActeIds(): array
    {
        return collect($this->actes_labo)->filter(fn($acte) => $this->acteIsValidated($acte))->pluck('id')->map(fn($id) => (int) $id)->values()->all();
    }

    protected function hasValidationTimestampColumn(): bool
    {
        static $hasColumn;

        if ($hasColumn !== null) {
            return $hasColumn;
        }

        return $hasColumn = Schema::hasColumn('laboratoires', 'date_heure_validation');
    }

    protected function laboratoirePayload(array $extra = []): array
    {
        return array_merge(
            [
                'renseignement' => filled($this->renseignement) ? $this->renseignement : null,
                'note' => filled($this->notes) ? $this->notes : null,
                'antibiotique' => filled($this->antibiotiques) ? $this->antibiotiques : null,
                'commentaire' => filled($this->commentaire) ? $this->commentaire : null,
            ],
            $extra,
        );
    }

    protected function persistDraftResults(): void
    {
        foreach ($this->actes_labo as $acte) {
            if ($this->acteIsValidated($acte)) {
                continue;
            }

            $acteId = (int) $acte->id;
            $this->labo->consultation->actes()->updateExistingPivot($acteId, [
                'resultat' => filled($this->resultats[$acteId] ?? null) ? $this->resultats[$acteId] : null,
            ]);
        }
    }

    protected function markActesAsValidated(array $acteIds): void
    {
        $alreadyValidatedIds = $this->currentValidatedActeIds();

        foreach ($acteIds as $acteId) {
            if (in_array((int) $acteId, $alreadyValidatedIds, true)) {
                continue;
            }

            $this->labo->consultation->actes()->updateExistingPivot((int) $acteId, [
                'valide' => true,
                'user_id' => Auth::id(),
            ]);
        }
    }

    protected function willAllActesBeValidated(array $selectedActeIds): bool
    {
        $validatedIds = collect($this->currentValidatedActeIds())
            ->merge(collect($selectedActeIds)->map(fn($id) => (int) $id))
            ->unique()
            ->values()
            ->all();

        return collect($this->laboratoireActeIds())->diff($validatedIds)->isEmpty();
    }

    public function getValidatedActesCountProperty(): int
    {
        return count($this->currentValidatedActeIds());
    }

    public function getTotalActesCountProperty(): int
    {
        return count($this->laboratoireActeIds());
    }

    public function getLaboStatusLabelProperty(): string
    {
        return ucfirst((string) $this->labo->statut);
    }

    public function getLaboStatusBadgeClassProperty(): string
    {
        return match ($this->labo->statut) {
            'en attente' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
            'en cours' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
            'terminé' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            'bloqué' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }

    public function getPreleveursProperty(): array
    {
        return User::query()
            ->where('hopital_id', current_hopital_id())
            ->get(['id', 'name'])
            ->map(
                fn($user) => [
                    'label' => $user->name,
                    'value' => $user->id,
                ],
            )
            ->all();
    }

    public function resetResultInputs(): void
    {
        foreach ($this->actes_labo as $acte) {
            if ($this->acteIsValidated($acte)) {
                continue;
            }

            $this->resultats[(int) $acte->id] = '';
        }
    }

    public function saveDraft(): void
    {
        DB::transaction(function () {
            $this->persistDraftResults();

            $this->labo->update(
                $this->laboratoirePayload([
                    'statut' => $this->labo->statut === 'en attente' ? 'en cours' : $this->labo->statut,
                ]),
            );
        });

        $this->loadLaboratoire($this->labo->id);
        $this->successMessage = 'Resultats et notes du laboratoire enregistres en brouillon.';
    }

    public function openPrelevementModal(): void
    {
        $this->showPrelevementModal = true;
        $this->dispatch('prelevement-modal-open');
    }

    public function closePrelevementModal(): void
    {
        $this->showPrelevementModal = false;
        $this->dispatch('prelevement-modal-close');
    }

    public function savePrelevement(): void
    {
        $this->validate([
            'prelevement_date' => ['required', 'date'],
            'prelevement_heure' => ['required'],
            'preleveur_id' => ['required', 'exists:users,id'],
            'renseignement' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->labo->update(
            $this->laboratoirePayload([
                'date_heure_prelevemnt' => $this->prelevement_date . ' ' . $this->prelevement_heure . ':00',
                'user_id' => $this->preleveur_id,
                'statut' => $this->labo->statut === 'en attente' ? 'en cours' : $this->labo->statut,
            ]),
        );

        $this->loadLaboratoire($this->labo->id);
        $this->successMessage = 'Informations de prelevement enregistrees avec succes.';
        $this->showPrelevementModal = false;
        $this->dispatch('prelevement-modal-close');
    }

    public function openValidationModal(): void
    {
        $this->selectedValidationActeIds = [];
        $this->resetValidation('selectedValidationActeIds');
        $this->showValidationModal = true;
        $this->dispatch('validation-modal-open');
    }

    public function closeValidationModal(): void
    {
        $this->showValidationModal = false;
        $this->selectedValidationActeIds = [];
        $this->resetValidation('selectedValidationActeIds');
        $this->dispatch('validation-modal-close');
    }

    public function validateSelectedActes(): void
    {
        $validated = $this->validate(
            [
                'selectedValidationActeIds' => ['required', 'array', 'min:1'],
                'selectedValidationActeIds.*' => ['integer', Rule::in($this->laboratoireActeIds())],
                'commentaire' => ['nullable', 'string', 'max:1000'],
            ],
            [
                'selectedValidationActeIds.required' => 'Veuillez selectionner au moins un examen a valider.',
                'selectedValidationActeIds.min' => 'Veuillez selectionner au moins un examen a valider.',
            ],
        );

        $acteIdsToValidate = collect($validated['selectedValidationActeIds'])->map(fn($id) => (int) $id)->reject(fn($id) => in_array($id, $this->currentValidatedActeIds(), true))->values()->all();

        if ($acteIdsToValidate === []) {
            $this->addError('selectedValidationActeIds', 'Les examens deja valides ne peuvent plus etre modifies.');
            return;
        }

        DB::transaction(function () use ($acteIdsToValidate) {
            $this->persistDraftResults();
            $this->markActesAsValidated($acteIdsToValidate);

            $status = $this->willAllActesBeValidated($acteIdsToValidate) ? 'terminé' : 'en cours';
            $payload = $this->laboratoirePayload([
                'statut' => $status,
                'user_valideur_id' => Auth::id(),
            ]);

            if ($status === 'terminé' && $this->hasValidationTimestampColumn()) {
                $payload['date_heure_validation'] = now();
            }

            $this->labo->update($payload);
        });

        $this->loadLaboratoire($this->labo->id);
        $this->successMessage = 'Les examens selectionnes ont ete valides avec succes.';
        $this->showValidationModal = false;
        $this->dispatch('validation-modal-close');
    }

    public function validateBon(): void
    {
        DB::transaction(function () {
            $this->persistDraftResults();
            $this->markActesAsValidated($this->laboratoireActeIds());

            $payload = $this->laboratoirePayload([
                'statut' => 'terminé',
                'user_valideur_id' => Auth::id(),
            ]);

            if ($this->hasValidationTimestampColumn()) {
                $payload['date_heure_validation'] = now();
            }

            $this->labo->update($payload);
        });

        $this->loadLaboratoire($this->labo->id);
        $this->successMessage = 'Le bon de laboratoire a ete valide et tous les examens sont maintenant marques comme valides.';
    }
};
?>

<div class="mx-auto max-w-7xl space-y-6">
    @if ($successMessage)
        <div x-data="{ show: true, init() { setTimeout(() => { this.show = false }, 3500) } }" x-init="init()" x-show="show"
            x-transition:leave="transition ease-in duration-300">
            <x-alert color="emerald" dismissible>
                {{ $successMessage }}
            </x-alert>
        </div>
    @endif

    <section
        class="overflow-hidden rounded-3xl border border-slate-200/70 bg-white/80 px-6 py-6 shadow-lg shadow-slate-200/40 backdrop-blur dark:border-slate-700 dark:bg-slate-950/70 dark:shadow-none">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <nav
                    class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                    <span class="hover:text-primary-500 transition-colors">Reception</span>
                    <span class="text-slate-300 dark:text-slate-600">/</span>
                    <span class="text-slate-900 dark:text-white">Saisie des resultats</span>
                </nav>
                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Bon de laboratoire #{{ $labo->consultation->reference }}
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">
                    La page est maintenant reliee a la vraie structure de la consultation: les examens de laboratoire
                    viennent du pivot `acte_consultation`, et leur validation est geree examen par examen ou sur tout
                    le bon.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:min-w-[18rem]">
                <div
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Validation</p>
                    <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">
                        {{ $this->validatedActesCount }}/{{ $this->totalActesCount }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Examens valides</p>
                </div>
                <div class="rounded-2xl px-4 py-3 {{ $this->laboStatusBadgeClass }}">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] opacity-80">Statut du bon</p>
                    <p class="mt-2 text-2xl font-black">{{ $this->laboStatusLabel }}</p>
                    <p class="mt-1 text-xs opacity-80">
                        {{ $labo->date_heure_validation?->format('d/m/Y H:i') ?: 'Validation non datee' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <flux:button href="{{ route('laboratoire.print', $labo->id) }}" target="_blank" icon="printer" variant="primary" color="indigo">
                Imprimer bon laboratoire
            </flux:button>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.7fr_1fr]">
        <main class="space-y-6">
            <x-card shadow="sm">
                <div
                    class="rounded-xl border border-slate-200/80 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Saisie des analyses
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Saisissez les resultats, puis validez uniquement les examens voulus ou l ensemble du
                            bon.
                        </p>
                    </div>
                </div>

                <div
                    class="mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <table class="min-w-full border-collapse text-left text-xs">
                        <thead>
                            <tr
                                class="border-b border-slate-200 bg-slate-50/70 text-xs font-bold tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400">
                                <th scope="col" class="px-6 py-4 font-semibold">Analyse</th>
                                <th scope="col" class="px-6 py-4 font-semibold w-48 text-center">Resultat</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-center">Valeur normale</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-center">Validation</th>
                                <th scope="col" class="hidden px-6 py-4 font-semibold text-right md:table-cell">
                                    Service</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70 bg-white dark:divide-slate-800/70 dark:bg-slate-950">
                            @forelse ($actes_labo as $acte)
                                @php($isValidated = $this->acteIsValidated($acte))
                                <tr
                                    class="transition-colors duration-150 hover:bg-slate-50/50 dark:hover:bg-slate-900/40">
                                    <td class="px-6 py-4.5">
                                        <div class="space-y-1">
                                            <div class="font-semibold text-slate-900 dark:text-slate-100">
                                                {{ $acte->name }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4.5">
                                        <div class="mx-auto max-w-[160px]">
                                            <x-input placeholder="0.0" suffix="{{ $acte->unite ?? '' }}"
                                                wire:model="resultats.{{ $acte->id }}" :disabled="$isValidated"
                                                class="border-transparent bg-slate-50/50 text-center font-medium transition-all focus:border-primary-500 focus:bg-white dark:bg-slate-900/50 dark:focus:bg-slate-950" />
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4.5 text-center">
                                        <span
                                            class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10 dark:bg-slate-800 dark:text-slate-400">
                                            {{ $acte->valeur_normal ?? '[-]' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4.5 text-center">
                                        @if ($isValidated)
                                            <span
                                                class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                                Valide
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                                En attente
                                            </span>
                                        @endif
                                    </td>
                                    <td
                                        class="hidden whitespace-nowrap px-6 py-4.5 text-right font-medium text-slate-500 dark:text-slate-400 md:table-cell">
                                        {{ $acte->service?->name ?? 'N/A' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                        Aucun examen de laboratoire n est lie a cette consultation.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-slot:footer>
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                        Les resultats peuvent etre ajustes jusqu a la validation. La validation globale marque tous les
                        examens comme valides, meme si un resultat reste vide.
                    </div>
                    <div class="mt-6 flex flex-wrap justify-end gap-3">
                        <x-button flat text="Reinitialiser" color="secondary" wire:click="resetResultInputs" />
                        <x-button icon="cloud-arrow-down" text="Enregistrer le brouillon" color="primary"
                            wire:click="saveDraft" wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed" wire:target="saveDraft" />
                    </div>
                </x-slot:footer>
            </x-card>

            <x-card shadow="sm" header="Contexte du laboratoire"
                class="border-none ring-1 ring-slate-200 dark:ring-slate-800">
                <div class="space-y-4 text-sm text-slate-600 dark:text-slate-300">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input label="Note laboratoire" wire:model="notes" placeholder="Note ou precision interne" />
                        <x-input label="Antibiotiques" wire:model="antibiotiques"
                            placeholder="Traitement en cours si renseigne" />
                    </div>

                    <x-textarea label="Renseignement clinique" wire:model="renseignement"
                        hint="Contexte clinique transmis au laboratoire."
                        placeholder="Saisissez le renseignement utile..." />

                    <x-textarea label="Commentaire de validation" wire:model="commentaire"
                        hint="Ce commentaire accompagne la validation individuelle ou globale du bon."
                        placeholder="Saisissez une note de validation..." />

                    <div class="flex flex-wrap items-center justify-start gap-3 sm:justify-end">
                        <x-button icon="check-circle" text="Validation individuelle des examens" color="secondary"
                            wire:click="openValidationModal" wire:loading.attr="disabled"
                            wire:target="openValidationModal,validateSelectedActes" />
                        <x-button icon="document-check" text="Valider le bon" color="emerald"
                            wire:click="validateBon" wire:loading.attr="disabled" wire:target="validateBon">
                            <span wire:loading.remove wire:target="validateBon">Valider le bon</span>
                            <span wire:loading wire:target="validateBon">Validation...</span>
                        </x-button>
                    </div>
                </div>
            </x-card>
        </main>

        <aside class="space-y-6">
            <x-card shadow="sm" header="Informations patient"
                class="border-none ring-1 ring-slate-200 dark:ring-slate-800">
                <div class="space-y-5 text-sm text-slate-600 dark:text-slate-300">
                    <div class="rounded-3xl bg-slate-50 p-4 text-slate-700 dark:bg-slate-950 dark:text-slate-200">
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-400">ID Patient</div>
                        <div class="mt-3 text-xl font-bold text-primary-600 dark:text-primary-400">
                            #{{ $labo->consultation->dossierPatient->nin ?? 'N/A' }}
                        </div>
                        <div class="mt-2 text-sm font-medium text-slate-800 dark:text-slate-300">
                            {{ ucfirst($labo->consultation->dossierPatient->nom ?? '') }}
                            {{ ucfirst($labo->consultation->dossierPatient->postnom ?? '') }}
                            {{ ucfirst($labo->consultation->dossierPatient->prenom ?? '') }}
                        </div>
                    </div>

                    <div class="grid gap-3 rounded-3xl bg-slate-50 p-4 dark:bg-slate-950">
                        <div
                            class="flex items-center justify-between gap-2 text-xs uppercase tracking-[0.18em] text-slate-400">
                            <span>Genre / Age</span>
                            <span class="text-right text-slate-700 dark:text-slate-300">
                                {{ $labo->consultation->dossierPatient->genre === 'F' ? 'Feminin' : 'Masculin' }}
                                - {{ $labo->consultation->dossierPatient->age ?? 'N/A' }} ans
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 text-xs uppercase tracking-[0.18em] text-slate-400">
                            <span>Prescripteur</span>
                            <span class="text-right text-slate-700 dark:text-slate-300">
                                {{ $labo->consultation->user?->name ?? 'N/A' }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 text-xs uppercase tracking-[0.18em] text-slate-400">
                            <span>Date du bon</span>
                            <span class="text-right text-slate-700 dark:text-slate-300">
                                {{ $labo->created_at->format('d/m/Y') }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-2 text-xs uppercase tracking-[0.18em] text-slate-400">
                            <span>Valideur</span>
                            <span class="text-right text-slate-700 dark:text-slate-300">
                                {{ $labo->userValideur?->name ?? 'Non renseigne' }}
                            </span>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card shadow="sm" header="Prelevement"
                class="border-none ring-1 ring-slate-200 dark:ring-slate-800">
                <div class="space-y-4">
                    @if ($labo->date_heure_prelevemnt)
                        <div class="rounded-3xl bg-emerald-50 p-4 dark:bg-emerald-900/10">
                            <div class="flex items-center gap-3">
                                <div
                                    class="rounded-2xl bg-emerald-100 p-3 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                    <x-icon name="check-circle" class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                                        Prelevement effectue
                                    </div>
                                    <p class="text-xs text-emerald-600 dark:text-emerald-400">
                                        {{ $labo->date_heure_prelevemnt->format('d/m/Y H:i') }}
                                    </p>
                                    @if ($labo->userPreleveur)
                                        <p class="text-xs text-emerald-600 dark:text-emerald-400">
                                            Par: {{ $labo->userPreleveur->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <x-button wire:click="openPrelevementModal" block color="emerald"
                            text="Modifier les informations" icon="pencil" sm />
                    @else
                        <div class="flex items-center gap-3 rounded-3xl bg-amber-50 p-4 dark:bg-amber-900/10">
                            <div
                                class="rounded-2xl bg-amber-100 p-3 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                <x-icon name="beaker" class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                                    Statut du prelevement
                                </div>
                                <p class="text-xs text-amber-600 dark:text-amber-400">Non renseigne</p>
                            </div>
                        </div>
                        <x-button wire:click="openPrelevementModal" block color="amber"
                            text="Inserer les informations" icon="plus" sm />
                    @endif
                </div>
            </x-card>

            <div class="space-y-2">
                <x-card shadow="sm" header="Note laboratoire" minimize="mount">
                    <div class="text-sm text-slate-600 dark:text-slate-300">
                        <p>{{ $labo->note ?: 'Aucune note laboratoire fournie.' }}</p>
                    </div>
                </x-card>

                <x-card shadow="sm" header="Renseignement" minimize="mount">
                    <div class="text-sm text-slate-600 dark:text-slate-300">
                        <p>{{ $labo->renseignement ?: 'Aucun renseignement fourni.' }}</p>
                    </div>
                </x-card>

                <x-card shadow="sm" header="Antibiotiques" minimize="mount">
                    <div class="text-sm text-slate-600 dark:text-slate-300">
                        <p>{{ $labo->antibiotique ?: 'Aucun antibiotique renseigne.' }}</p>
                    </div>
                </x-card>
            </div>
        </aside>
    </div>

    <x-modal wire:model="showPrelevementModal" id="prelevement-modal" title="Informations de prelevement" blur
        x-on:prelevement-modal-open.window="$tsui.open.modal('prelevement-modal')"
        x-on:prelevement-modal-close.window="$tsui.close.modal('prelevement-modal')" z-index="z-20" persistent>
        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <x-date label="Date du prelevement" wire:model="prelevement_date" required />
                    @error('prelevement_date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input label="Heure du prelevement" type="time" wire:model="prelevement_heure" required />
                    @error('prelevement_heure')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <x-select.styled label="Preleveur" wire:model="preleveur_id" :options="$this->preleveurs"
                    placeholder="Selectionner un preleveur" required />
                @error('preleveur_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-textarea label="Renseignements complementaires" wire:model="renseignement"
                    placeholder="Ajouter des notes sur le prelevement..." rows="4" />
                @error('renseignement')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button flat wire:click="closePrelevementModal" x-on:click="$tsui.close.modal('prelevement-modal')"
                    color="secondary">
                    Annuler
                </x-button>
                <x-button type="button" color="emerald" wire:loading.attr="disabled" wire:target="savePrelevement"
                    wire:click="savePrelevement">
                    <span wire:loading.remove wire:target="savePrelevement">Enregistrer</span>
                    <span wire:loading wire:target="savePrelevement">Enregistrement...</span>
                </x-button>
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal wire:model="showValidationModal" id="laboratoire-validation-modal"
        title="Validation individuelle des examens" blur
        x-on:validation-modal-open.window="$tsui.open.modal('laboratoire-validation-modal')"
        x-on:validation-modal-close.window="$tsui.close.modal('laboratoire-validation-modal')" z-index="z-20"
        persistent>
        <div class="space-y-5">
            <div
                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-300">
                Cochez uniquement les examens a valider maintenant. Seuls ces examens passeront a
                <span class="font-semibold text-emerald-700 dark:text-emerald-300">valide = true</span>.
            </div>

            @error('selectedValidationActeIds')
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $message }}
                </div>
            @enderror

            <div class="max-h-[24rem] space-y-3 overflow-y-auto pr-1">
                @forelse ($actes_labo as $acte)
                    @php($isValidated = $this->acteIsValidated($acte))
                    <label wire:key="validation-acte-{{ $acte->id }}"
                        class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 transition dark:border-slate-700 dark:bg-slate-950 {{ $isValidated ? 'cursor-not-allowed opacity-75' : 'cursor-pointer hover:border-emerald-300 hover:bg-emerald-50/40 dark:hover:border-emerald-500/40 dark:hover:bg-emerald-500/10' }}">
                        <input type="checkbox" value="{{ $acte->id }}" wire:model="selectedValidationActeIds"
                            @disabled($isValidated)
                            class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $acte->name }}</p>
                                @if ($isValidated)
                                    <span
                                        class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                        Deja valide
                                    </span>
                                @else
                                    <span
                                        class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                        En attente
                                    </span>
                                @endif
                            </div>
                            <div class="mt-2 flex flex-wrap gap-4 text-xs text-slate-500 dark:text-slate-400">
                                <span>Resultat: {{ $resultats[$acte->id] ?: 'vide' }}</span>
                                <span>Normal: {{ $acte->valeur_normal ?: '[-]' }}</span>
                                <span>Service: {{ $acte->service?->name ?: 'N/A' }}</span>
                            </div>
                            @if ($isValidated)
                                <p class="mt-2 text-xs font-medium text-emerald-600 dark:text-emerald-300">
                                    Cet examen est deja valide. Il reste consultable mais n est plus modifiable.
                                </p>
                            @endif
                        </div>
                    </label>
                @empty
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-400">
                        Aucun examen disponible pour la validation individuelle.
                    </div>
                @endforelse
            </div>
        </div>

        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button flat wire:click="closeValidationModal"
                    x-on:click="$tsui.close.modal('laboratoire-validation-modal')" color="secondary">
                    Annuler
                </x-button>
                <x-button type="button" color="emerald" wire:loading.attr="disabled"
                    wire:target="validateSelectedActes" wire:click="validateSelectedActes">
                    <span wire:loading.remove wire:target="validateSelectedActes">Valider la selection</span>
                    <span wire:loading wire:target="validateSelectedActes">Validation...</span>
                </x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
