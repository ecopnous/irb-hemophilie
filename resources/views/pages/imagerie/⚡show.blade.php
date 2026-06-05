<?php

use App\Models\Consultation;
use App\Models\Imagerie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Bon d\'imagerie')] class extends Component {
    public Consultation $consultation;
    public ?int $selectedActeId = null;
    public array $imagerieForm = [];
    public array $acteForm = [];
    public ?string $successMessage = null;

    public function mount(int $id): void
    {
        abort_unless(current_hopital_id(), 403, 'Aucun hopital courant en session.');

        $this->selectedActeId = request()->integer('acte') ?: null;
        $this->loadConsultation($id);
    }

    protected function loadConsultation(int $id): void
    {
        $consultationQuery = Consultation::query()
            ->whereHopitalId(current_hopital_id())
            ->with([
                'dossierPatient',
                'departement',
                'service',
                'user',
                'assurance',
                'projet',
                'imagerie',
                'actes' => fn ($query) => $query->with('departement', 'service'),
            ]);

        $consultation = $consultationQuery->find($id);

        if (!$consultation) {
            $imagerie = Imagerie::query()
                ->where('hopital_id', current_hopital_id())
                ->findOrFail($id);

            $consultation = Consultation::query()
                ->whereHopitalId(current_hopital_id())
                ->with([
                    'dossierPatient',
                    'departement',
                    'service',
                    'user',
                    'assurance',
                    'projet',
                    'imagerie',
                    'actes' => fn ($query) => $query->with('departement', 'service'),
                ])
                ->findOrFail($imagerie->consultation_id);
        }

        abort_unless($consultation->imagerie || $this->consultationHasImagerieActes($consultation), 404, 'Aucun bon d imagerie lie a cette consultation.');

        $this->consultation = $consultation;
        $this->syncStateFromConsultation();
    }

    protected function syncStateFromConsultation(): void
    {
        $this->imagerieForm = [
            'renseignement' => (string) ($this->consultation->imagerie?->renseignement ?? ''),
            'note' => (string) ($this->consultation->imagerie?->note ?? ''),
            'antibiotique' => (string) ($this->consultation->imagerie?->antibiotique ?? ''),
        ];

        $availableActeIds = $this->imagerieActes()->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (!$this->selectedActeId || !in_array($this->selectedActeId, $availableActeIds, true)) {
            $this->selectedActeId = $availableActeIds[0] ?? null;
        }

        $this->syncSelectedActeForm();
    }

    protected function consultationHasImagerieActes(Consultation $consultation): bool
    {
        return $consultation->actes->contains(fn ($acte) => $this->acteBelongsToImagerie($acte));
    }

    protected function acteBelongsToImagerie($acte): bool
    {
        $departement = $acte->departement;

        if (!$departement) {
            return false;
        }

        $name = strtolower((string) $departement->name);
        $ref = strtolower((string) ($departement->ref ?? ''));

        return str_contains($name, 'imagerie') || $ref === 'img';
    }

    public function imagerieActes()
    {
        return $this->consultation->actes
            ->filter(fn ($acte) => $this->acteBelongsToImagerie($acte))
            ->values();
    }

    public function selectedActe()
    {
        return $this->imagerieActes()->firstWhere('id', $this->selectedActeId);
    }

    protected function syncSelectedActeForm(): void
    {
        $selectedActe = $this->selectedActe();

        $this->acteForm = [
            'clinique' => (string) data_get($selectedActe, 'pivot.clinique', ''),
            'protocole' => (string) data_get($selectedActe, 'pivot.protocole', ''),
            'cloture' => (string) data_get($selectedActe, 'pivot.cloture', ''),
        ];
    }

    public function selectActe(int $acteId): void
    {
        if (!$this->imagerieActes()->contains(fn ($acte) => (int) $acte->id === $acteId)) {
            return;
        }

        $this->selectedActeId = $acteId;
        $this->syncSelectedActeForm();
    }

    public function saveCompteRendu(): void
    {
        $validated = $this->validate([
            'selectedActeId' => ['required', 'integer'],
            'imagerieForm.renseignement' => ['nullable', 'string', 'max:255'],
            'imagerieForm.note' => ['nullable', 'string', 'max:255'],
            'imagerieForm.antibiotique' => ['nullable', 'string', 'max:255'],
            'acteForm.clinique' => ['nullable', 'string', 'max:255'],
            'acteForm.protocole' => ['nullable', 'string', 'max:255'],
            'acteForm.cloture' => ['nullable', 'string', 'max:255'],
        ]);

        $selectedActe = $this->selectedActe();

        if (!$selectedActe) {
            $this->addError('selectedActeId', 'Veuillez selectionner un examen d imagerie valide.');

            return;
        }

        DB::transaction(function () use ($validated, $selectedActe) {
            $this->consultation->actes()->updateExistingPivot((int) $selectedActe->id, [
                'clinique' => $validated['acteForm']['clinique'] ?: null,
                'protocole' => $validated['acteForm']['protocole'] ?: null,
                'cloture' => $validated['acteForm']['cloture'] ?: null,
            ]);

            $imagerie = Imagerie::query()->updateOrCreate(
                ['consultation_id' => $this->consultation->id],
                [
                    'consultation_id' => $this->consultation->id,
                    'hopital_id' => current_hopital_id(),
                    'renseignement' => filled($validated['imagerieForm']['renseignement'])
                        ? $validated['imagerieForm']['renseignement']
                        : $this->defaultRenseignement(),
                    'note' => $validated['imagerieForm']['note'] ?: null,
                    'antibiotique' => $validated['imagerieForm']['antibiotique'] ?: null,
                    'statut' => $this->resolveImagerieStatusAfterSave((int) $selectedActe->id, $validated['acteForm']),
                ],
            );

            $this->consultation->update([
                'imagerie_id' => $imagerie->id,
            ]);
        });

        $rememberedActeId = $this->selectedActeId;

        $this->loadConsultation($this->consultation->id);
        $this->selectedActeId = $rememberedActeId;
        $this->syncSelectedActeForm();
        $this->successMessage = 'Le compte rendu d imagerie a ete enregistre avec succes.';
    }

    protected function resolveImagerieStatusAfterSave(?int $overrideActeId = null, array $overridePayload = []): string
    {
        $examens = $this->imagerieActes();

        if ($examens->isEmpty()) {
            return 'en attente';
        }

        $completed = $examens->filter(function ($acte) use ($overrideActeId, $overridePayload) {
            $clinique = (int) $acte->id === $overrideActeId
                ? ($overridePayload['clinique'] ?? null)
                : data_get($acte, 'pivot.clinique');
            $protocole = (int) $acte->id === $overrideActeId
                ? ($overridePayload['protocole'] ?? null)
                : data_get($acte, 'pivot.protocole');
            $cloture = (int) $acte->id === $overrideActeId
                ? ($overridePayload['cloture'] ?? null)
                : data_get($acte, 'pivot.cloture');

            return filled($clinique) || filled($protocole) || filled($cloture);
        })->count();

        if ($completed === 0) {
            return 'en attente';
        }

        if ($completed === $examens->count()) {
            return 'terminé';
        }

        return 'en cours';
    }

    protected function defaultRenseignement(): string
    {
        $existing = trim((string) ($this->consultation->imagerie?->renseignement ?? ''));

        if ($existing !== '') {
            return $existing;
        }

        $examens = $this->imagerieActes()->pluck('name')->implode(', ');

        return $examens !== '' ? 'Demande imagerie - ' . $examens : 'Demande imagerie';
    }

    public function acteIsDocumented($acte): bool
    {
        return filled(data_get($acte, 'pivot.clinique'))
            || filled(data_get($acte, 'pivot.protocole'))
            || filled(data_get($acte, 'pivot.cloture'));
    }

    public function acteStatusLabel($acte): string
    {
        return $this->acteIsDocumented($acte) ? 'Renseigne' : 'A completer';
    }

    public function acteStatusClass($acte): string
    {
        return $this->acteIsDocumented($acte)
            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
            : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300';
    }

    public function pivotExcerpt($acte, string $field): string
    {
        $value = (string) data_get($acte, "pivot.{$field}", '');

        return $value !== '' ? Str::limit($value, 70) : '-';
    }

    public function patientIdentity(): string
    {
        $patient = $this->consultation->dossierPatient;

        if (!$patient) {
            return 'Patient inconnu';
        }

        return trim(sprintf(
            '%s %s %s',
            strtoupper((string) $patient->nom),
            strtoupper((string) $patient->postnom),
            ucfirst((string) $patient->prenom),
        ));
    }

    public function patientAgeLabel(): string
    {
        $patient = $this->consultation->dossierPatient;

        return $patient?->age ? $patient->age . ' ans' : 'Age indisponible';
    }

    public function requestedExamNames(): string
    {
        $names = $this->imagerieActes()->pluck('name')->implode(', ');

        return $names !== '' ? $names : 'Aucun examen demande';
    }

    public function selectedActeTitle(): string
    {
        return $this->selectedActe()?->name ?? 'Selectionner un examen';
    }

    public function selectedActeServiceLabel(): string
    {
        $acte = $this->selectedActe();

        return $acte?->service?->name ?: ($acte?->departement?->name ?: 'Imagerie');
    }

    public function getCompletedActesCountProperty(): int
    {
        return $this->imagerieActes()->filter(fn ($acte) => $this->acteIsDocumented($acte))->count();
    }

    public function getStatusLabelProperty(): string
    {
        return ucfirst((string) ($this->consultation->imagerie?->statut ?? 'en attente'));
    }

    public function getStatusBadgeClassProperty(): string
    {
        return match ($this->consultation->imagerie?->statut) {
            'en cours' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
            'terminé' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            'bloqué' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
            default => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        };
    }
};
?>

<div class="space-y-6">
    @if ($successMessage)
        <div x-data="{ show: true, init() { setTimeout(() => { this.show = false }, 3500) } }" x-init="init()"
            x-show="show" x-transition:leave="transition ease-in duration-300">
            <x-alert color="emerald" dismissible>
                {{ $successMessage }}
            </x-alert>
        </div>
    @endif

    <section
        class="overflow-hidden rounded-[2rem] border border-fuchsia-100 bg-gradient-to-br from-white via-fuchsia-50/70 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="flex flex-col gap-6 px-6 py-6 md:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Imagerie', 'link' => 'imagerie.index', 'icon' => 'photo'],
                        ['label' => $consultation->reference, 'icon' => 'document-text'],
                    ]" />

                    <div class="space-y-1">
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-fuchsia-600 dark:text-fuchsia-300">
                            Imagerie medicale
                        </p>
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                            Bon d imagerie #{{ $consultation->reference }}
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Fiche adaptee au circuit reel: seuls les examens d imagerie demandes sur cette consultation
                            apparaissent ici.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:min-w-[24rem]">
                    <div
                        class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Examens demandes</p>
                        <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                            {{ $this->imagerieActes()->count() }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-emerald-100 bg-emerald-50/90 px-4 py-3 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">
                            Comptes rendus
                        </p>
                        <p class="mt-2 text-3xl font-black text-emerald-900 dark:text-emerald-100">
                            {{ $this->completedActesCount }}
                        </p>
                    </div>
                    <div class="rounded-2xl px-4 py-3 {{ $this->statusBadgeClass }}">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] opacity-80">Statut du bon</p>
                        <p class="mt-2 text-2xl font-black">{{ $this->statusLabel }}</p>
                    </div>
                </div>
            </div>

            <div
                class="flex flex-col gap-3 border-t border-fuchsia-100/80 pt-4 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $this->patientIdentity() }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Patient {{ $consultation->dossierPatient?->nin ?: '-' }} | {{ $this->patientAgeLabel() }} |
                        {{ $consultation->dossierPatient?->genre === 'F' ? 'Feminin' : 'Masculin' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button href="{{ route('imagerie.print', $consultation->id) }}" target="_blank" icon="printer" variant="primary" color="indigo">
                        Imprimer bon imagerie
                    </flux:button>
                    <span
                        class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">
                        {{ $consultation->departement?->name ?: 'Departement non renseigne' }}
                    </span>
                    <span
                        class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">
                        {{ optional($consultation->created_at)->format('d/m/Y H:i') ?: '-' }}
                    </span>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.45fr,1fr]">
        <main class="space-y-6">
            <x-card shadow="sm">
                <div class="rounded-xl border border-slate-200/80 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                Compte rendu de l examen selectionne
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Renseignez le contexte clinique, le protocole et la conclusion pour l examen en cours.
                            </p>
                        </div>

                        @if ($this->selectedActe())
                            <div
                                class="rounded-2xl border border-fuchsia-200 bg-white px-4 py-3 text-right shadow-sm dark:border-fuchsia-500/20 dark:bg-slate-950/70">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-fuchsia-600 dark:text-fuchsia-300">
                                    Examen actif
                                </p>
                                <p class="mt-1 font-black text-slate-900 dark:text-white">{{ $this->selectedActeTitle() }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $this->selectedActeServiceLabel() }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-6 space-y-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-textarea wire:model="imagerieForm.renseignement" label="Renseignement de la demande"
                            rows="3" maxlength="255" count
                            hint="Contexte general du bon d imagerie." />
                        <div class="grid gap-4">
                            <x-input wire:model="imagerieForm.note" label="Note de service" maxlength="255" />
                            <x-input wire:model="imagerieForm.antibiotique" label="Antibiotique / preparation"
                                maxlength="255" />
                        </div>
                    </div>

                    @if ($this->selectedActe())
                        <div class="grid grid-cols-1 gap-4">
                            <x-textarea wire:model="acteForm.clinique" label="Clinique"
                                placeholder="Motif, signes cliniques, contexte..." rows="4" maxlength="255" count />
                            <x-textarea wire:model="acteForm.protocole" label="Protocole"
                                placeholder="Description de l examen realise..." rows="5" maxlength="255" count />
                            <x-textarea wire:model="acteForm.cloture" label="Conclusion"
                                placeholder="Conclusion ou interpretation principale..." rows="4" maxlength="255" count />
                        </div>
                    @else
                        <div
                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
                            Aucun examen d imagerie n est actuellement rattache a cette consultation.
                        </div>
                    @endif
                </div>

                <x-slot:footer>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            L enregistrement met a jour le bon d imagerie et le compte rendu de l examen selectionne.
                        </p>
                        <x-button icon="arrow-down-tray" text="Enregistrer le compte rendu" color="primary"
                            wire:click="saveCompteRendu" wire:loading.attr="disabled" wire:target="saveCompteRendu" />
                    </div>
                </x-slot:footer>
            </x-card>

            <x-card shadow="sm">
                <div class="rounded-xl border border-slate-200/80 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                Examens reellement demandes
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Le tableau ci-dessous ne reprend que les actes d imagerie attaches a cette consultation.
                            </p>
                        </div>
                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-fuchsia-100 px-3 py-1.5 text-sm font-bold text-fuchsia-700 dark:bg-fuchsia-500/15 dark:text-fuchsia-300">
                            <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                            {{ $this->imagerieActes()->count() }} examen(s)
                        </span>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <table class="min-w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-bold tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400">
                                <th scope="col" class="px-6 py-4 font-semibold">Examen</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Service</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Clinique</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Protocole</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Conclusion</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-center">Etat</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70 bg-white dark:divide-slate-800/70 dark:bg-slate-950">
                            @forelse ($this->imagerieActes() as $acte)
                                <tr
                                    class="transition-colors duration-150 {{ $selectedActeId === $acte->id ? 'bg-fuchsia-50/80 dark:bg-fuchsia-500/10' : 'hover:bg-slate-50/50 dark:hover:bg-slate-900/40' }}">
                                    <td class="px-6 py-4.5">
                                        <div class="space-y-1">
                                            <p class="font-semibold text-slate-900 dark:text-white">{{ $acte->name }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                                Ref: {{ data_get($acte, 'pivot.ref', 'img') }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4.5 text-slate-600 dark:text-slate-300">
                                        {{ $acte->service?->name ?: ($acte->departement?->name ?: 'Imagerie') }}
                                    </td>
                                    <td class="px-6 py-4.5 text-slate-600 dark:text-slate-300">
                                        {{ $this->pivotExcerpt($acte, 'clinique') }}
                                    </td>
                                    <td class="px-6 py-4.5 text-slate-600 dark:text-slate-300">
                                        {{ $this->pivotExcerpt($acte, 'protocole') }}
                                    </td>
                                    <td class="px-6 py-4.5 text-slate-600 dark:text-slate-300">
                                        {{ $this->pivotExcerpt($acte, 'cloture') }}
                                    </td>
                                    <td class="px-6 py-4.5 text-center">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $this->acteStatusClass($acte) }}">
                                            {{ $this->acteStatusLabel($acte) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4.5 text-right">
                                        <x-button sm icon="pencil-square"
                                            wire:click="selectActe({{ $acte->id }})">
                                            {{ $selectedActeId === $acte->id ? 'En cours' : 'Renseigner' }}
                                        </x-button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                        Aucun examen d imagerie n est rattache a cette consultation.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card shadow="sm">
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-6 dark:border-slate-700 dark:bg-slate-900/60">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl bg-fuchsia-100 p-3 text-fuchsia-700 dark:bg-fuchsia-500/15 dark:text-fuchsia-300">
                            <x-icon name="photo" class="h-6 w-6" />
                        </div>
                        <div class="space-y-2">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Zone images</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Cet espace est pret a accueillir les cliches et pieces jointes d imagerie quand le flux
                                de medias sera branche. Pour le moment, il sert de zone de reserve UI.
                            </p>
                        </div>
                    </div>
                </div>
            </x-card>
        </main>

        <aside class="space-y-6">
            <x-card shadow="sm" header="Synthese patient" class="border-none ring-1 ring-slate-200 dark:ring-slate-800">
                <div class="space-y-5 text-sm text-slate-600 dark:text-slate-300">
                    <div class="rounded-3xl bg-slate-50 p-4 dark:bg-slate-950">
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Reference consultation</div>
                        <div class="mt-3 text-xl font-bold text-fuchsia-600 dark:text-fuchsia-300">
                            {{ $consultation->reference }}
                        </div>
                        <div class="mt-2 text-sm font-medium text-slate-800 dark:text-slate-300">
                            {{ $this->patientIdentity() }}
                        </div>
                    </div>

                    <div class="grid gap-3 rounded-3xl bg-slate-50 p-4 dark:bg-slate-950">
                        <div class="flex items-center justify-between gap-3 text-xs uppercase tracking-[0.18em] text-slate-400">
                            <span>NIN</span>
                            <span class="text-right text-slate-700 dark:text-slate-300">
                                {{ $consultation->dossierPatient?->nin ?: '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-xs uppercase tracking-[0.18em] text-slate-400">
                            <span>Genre / Age</span>
                            <span class="text-right text-slate-700 dark:text-slate-300">
                                {{ $consultation->dossierPatient?->genre === 'F' ? 'Feminin' : 'Masculin' }} -
                                {{ $this->patientAgeLabel() }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-xs uppercase tracking-[0.18em] text-slate-400">
                            <span>Medecin</span>
                            <span class="text-right text-slate-700 dark:text-slate-300">
                                {{ $consultation->user?->name ?: 'Non assigne' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-xs uppercase tracking-[0.18em] text-slate-400">
                            <span>Provenance</span>
                            <span class="text-right text-slate-700 dark:text-slate-300">
                                {{ $consultation->departement?->name ?: '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-xs uppercase tracking-[0.18em] text-slate-400">
                            <span>Service</span>
                            <span class="text-right text-slate-700 dark:text-slate-300">
                                {{ $consultation->service?->name ?: '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-xs uppercase tracking-[0.18em] text-slate-400">
                            <span>Date du bon</span>
                            <span class="text-right text-slate-700 dark:text-slate-300">
                                {{ optional($consultation->created_at)->format('d/m/Y H:i') ?: '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card shadow="sm" header="Demande d imagerie" class="border-none ring-1 ring-slate-200 dark:ring-slate-800">
                <div class="space-y-4 text-sm text-slate-600 dark:text-slate-300">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/70">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Examens demandes</p>
                        <p class="mt-2 font-semibold text-slate-900 dark:text-white">{{ $this->requestedExamNames() }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/70">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Renseignement</p>
                        <p class="mt-2 leading-6">
                            {{ $consultation->imagerie?->renseignement ?: 'Aucun renseignement saisi.' }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/70">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Note</p>
                            <p class="mt-2">{{ $consultation->imagerie?->note ?: '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/70">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Antibiotique</p>
                            <p class="mt-2">{{ $consultation->imagerie?->antibiotique ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card shadow="sm" header="Progression" class="border-none ring-1 ring-slate-200 dark:ring-slate-800">
                <div class="space-y-4">
                    <div class="overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-3 rounded-full bg-fuchsia-500 transition-all"
                            style="width: {{ $this->imagerieActes()->count() > 0 ? ($this->completedActesCount / $this->imagerieActes()->count()) * 100 : 0 }}%">
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        {{ $this->completedActesCount }} examen(s) documente(s) sur
                        {{ $this->imagerieActes()->count() }}.
                    </p>
                </div>
            </x-card>
        </aside>
    </div>
</div>
