<?php

use App\Models\Consultation;
use App\Models\DossierPatient;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app.other.profil_medical')] class extends Component {
    public DossierPatient $patient;

    public array $tag_ids = [];

    public bool $editingTags = false;

    public function mount($id): void
    {
        $this->patient = DossierPatient::query()
            ->with(['tags', 'assurance', 'commune', 'ville', 'province'])
            ->withCount('consultations')
            ->findOrFail($id);

        $this->tag_ids = $this->patient->tags->pluck('id')->map(fn ($tagId) => (string) $tagId)->all();
    }

    #[Computed]
    public function lastConsultation(): ?Consultation
    {
        return Consultation::query()
            ->where('dossier_patient_id', $this->patient->id)
            ->with(['user:id,name', 'departement:id,name'])
            ->latest('created_at')
            ->first();
    }

    public function startEditingTags(): void
    {
        $this->tag_ids = $this->patient->tags->pluck('id')->map(fn ($tagId) => (string) $tagId)->all();
        $this->editingTags = true;
    }

    public function cancelEditingTags(): void
    {
        $this->tag_ids = $this->patient->tags->pluck('id')->map(fn ($tagId) => (string) $tagId)->all();
        $this->editingTags = false;
    }

    public function saveTags(): void
    {
        $validated = $this->validate([
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        $this->patient->tags()->sync($validated['tag_ids'] ?? []);
        $this->patient->load('tags');
        $this->editingTags = false;

        Flux::toast(variant: 'success', heading: 'Tags mis à jour', text: 'Les étiquettes du dossier ont été enregistrées.');
    }
};
?>

<div class="mx-auto max-w-7xl space-y-6 transition-colors duration-300">
    <x-patient.patient-profil-header :nav="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Dossiers patients', 'link' => 'patient.index', 'icon' => 'folder'],
        ['label' => $patient->nin, 'icon' => 'identification'],
    ]" :patient="$patient" :current_patient="$patient->id">
        <x-slot name="title">{{ ucfirst($patient->nom) }} {{ ucfirst($patient->postnom) }}
            {{ ucfirst($patient->prenom) }}</x-slot>
        <x-slot name="subtitle">NIN {{ $patient->nin }}{{ $patient->ins ? ' · INS ' . $patient->ins : '' }}</x-slot>
    </x-patient.patient-profil-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        {{-- Colonne identité --}}
        <div class="space-y-6 xl:col-span-4">
            <section
                class="overflow-hidden rounded-4xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="h-28 bg-linear-to-br from-indigo-600 via-violet-600 to-cyan-500"></div>
                <div class="px-6 pb-6">
                    <div class="relative -mt-14 mb-4 flex justify-center">
                        <img class="size-28 rounded-[1.75rem] border-4 border-white object-cover shadow-xl dark:border-slate-900"
                            src="{{ $patient->photo_url }}" alt="Photo de {{ $patient->full_name }}">
                        @if ($patient->is_dead)
                            <span
                                class="absolute -bottom-1 rounded-lg border-2 border-white bg-slate-900 px-2 py-0.5 text-[10px] font-black uppercase tracking-tight text-white dark:border-slate-900">
                                Décédé
                            </span>
                        @endif
                    </div>

                    <div class="text-center">
                        <h2 class="text-xl font-black text-slate-900 dark:text-white">
                            {{ $patient->full_name }}
                        </h2>
                        <p class="mt-1 text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-300">
                            {{ $patient->genre === 'M' ? 'Masculin' : 'Féminin' }}
                            @if ($patient->date_naissance)
                                · {{ $patient->age }}
                            @endif
                        </p>
                        @if ($patient->formatted_birthdate)
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Né(e) le {{ $patient->formatted_birthdate }}
                            </p>
                        @endif
                    </div>

                    <div class="mt-6 space-y-3">
                        <div
                            class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-900/50">
                            <flux:icon.phone class="size-5 text-slate-400" />
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Téléphone</p>
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                                    {{ $patient->telephone ?? 'Non renseigné' }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-900/50">
                            <flux:icon.envelope class="size-5 text-slate-400" />
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Email</p>
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                                    {{ $patient->email ?? 'Non renseigné' }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-900/50">
                            <flux:icon.shield-check class="size-5 text-slate-400" />
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Couverture</p>
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                                    {{ $patient->assurance?->name ?? 'Paiement direct' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <flux:button class="mt-6 w-full justify-center" href="{{ route('patient.init_consult', $patient->id) }}"
                        variant="primary" color="indigo" icon="plus" wire:navigate>
                        Nouvelle consultation
                    </flux:button>
                </div>
            </section>

            {{-- Tags --}}
            <section
                class="rounded-4xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Profil clinique</p>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Tags du dossier</h3>
                    </div>
                    @if (! $editingTags)
                        <flux:button size="sm" variant="subtle" icon="pencil-square" wire:click="startEditingTags">
                            Modifier
                        </flux:button>
                    @endif
                </div>

                @if ($editingTags)
                    <div class="space-y-4">
                        <x-select.styled label="Tags" wire:model="tag_ids" :request="route('api.tags')"
                            select="label:name|value:id" multiple
                            hint="Ex. hémophile, drépanocytaire, à risque…" />
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" variant="subtle" wire:click="cancelEditingTags">Annuler</flux:button>
                            <flux:button size="sm" variant="primary" color="indigo" icon="check"
                                wire:click="saveTags" wire:loading.attr="disabled">
                                Enregistrer
                            </flux:button>
                        </div>
                    </div>
                @else
                    @if ($patient->tags->isEmpty())
                        <div
                            class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-6 text-center dark:border-slate-700 dark:bg-slate-900/40">
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Aucun tag associé</p>
                            <p class="mt-1 text-xs text-slate-400">Ajoutez des tags pour identifier le profil pathologique.</p>
                        </div>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($patient->tags as $tag)
                                <span
                                    class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-800 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200">
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                @endif
            </section>

            <section
                class="rounded-4xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Observations médicales</h3>
                <blockquote
                    class="mt-4 border-l-4 border-amber-400 pl-4 text-sm italic leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ $patient->note ? ucfirst($patient->note) : 'Aucune note clinique particulière pour ce dossier.' }}
                </blockquote>
            </section>
        </div>

        {{-- Colonne contenu --}}
        <div class="space-y-6 xl:col-span-8">
            {{-- Dernière consultation --}}
            <section
                class="overflow-hidden rounded-4xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div
                    class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/80 px-6 py-5 dark:border-slate-800 dark:bg-slate-900/80 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Synthèse</p>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Dernière consultation</h3>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span
                            class="rounded-full bg-slate-200 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ number_format($patient->consultations_count) }} consultation(s)
                        </span>
                        @if ($this->lastConsultation)
                            <flux:button size="sm" variant="subtle" icon="arrow-top-right-on-square"
                                href="{{ route('consultation.show', $this->lastConsultation->id) }}" wire:navigate>
                                Ouvrir
                            </flux:button>
                        @endif
                    </div>
                </div>

                @if ($last = $this->lastConsultation)
                    <div class="grid gap-4 p-6 sm:grid-cols-2 xl:grid-cols-4">
                        <x-analytics.kpi-card label="Date" :value="optional($last->created_at)->format('d/m/Y') ?? '-'" icon="calendar-days"
                            tone="blue" />
                        <x-analytics.kpi-card label="Référence" :value="$last->reference" icon="hashtag" tone="slate" />
                        <x-analytics.kpi-card label="Département"
                            :value="ucwords($last->departement?->name ?? '—')" icon="building-office-2" tone="cyan" />
                        <x-analytics.kpi-card label="Médecin" :value="$last->user?->name ?? 'Non assigné'" icon="user-circle"
                            tone="emerald" />
                    </div>

                    <div class="grid gap-4 border-t border-slate-100 px-6 py-5 dark:border-slate-800 sm:grid-cols-2 xl:grid-cols-4">
                        <x-analytics.kpi-card label="Poids"
                            :value="$last->poids !== null ? $last->poids : '—'" suffix="{{ $last->poids !== null ? ' kg' : '' }}"
                            icon="scale" tone="amber" />
                        <x-analytics.kpi-card label="Température"
                            :value="$last->temperature !== null ? $last->temperature : '—'"
                            suffix="{{ $last->temperature !== null ? ' °C' : '' }}" icon="fire" tone="rose" />
                        <x-analytics.kpi-card label="Pression artérielle"
                            :value="($last->systolite && $last->diastolique) ? $last->systolite . '/' . $last->diastolique : '—'"
                            suffix="{{ ($last->systolite && $last->diastolique) ? ' mmHg' : '' }}" icon="heart"
                            tone="slate" />
                        <x-analytics.kpi-card label="Statut"
                            :value="$last->issue ? 'Clôturée' : 'En cours'" icon="clipboard-document-check"
                            :tone="$last->issue ? 'slate' : 'emerald'" />
                    </div>

                    <div class="border-t border-slate-100 px-6 py-4 dark:border-slate-800">
                        <div class="flex flex-wrap gap-3 text-xs font-semibold text-slate-500 dark:text-slate-400">
                            <span class="rounded-lg bg-slate-100 px-2.5 py-1 dark:bg-slate-800">
                                Type : {{ $last->type === 'depistage' ? 'Examen' : 'Visite médicale' }}
                            </span>
                            @if ($last->type_fichier)
                                <span class="rounded-lg bg-slate-100 px-2.5 py-1 dark:bg-slate-800">
                                    Fiche : {{ ucfirst($last->type_fichier) }}
                                </span>
                            @endif
                            @if ($last->mois)
                                <span class="rounded-lg bg-slate-100 px-2.5 py-1 dark:bg-slate-800">
                                    Période : {{ $last->mois }}
                                </span>
                            @endif
                            <span @class([
                                'rounded-lg px-2.5 py-1',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200' => $last->is_clore,
                                'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200' => ! $last->is_clore,
                            ])>
                                Dossier {{ $last->is_clore ? 'classé' : 'ouvert' }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <flux:icon.clipboard-document-list class="mx-auto size-10 text-slate-300" />
                        <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">Aucune consultation enregistrée</p>
                        <p class="mt-1 text-xs text-slate-400">Créez une première visite pour alimenter ce résumé.</p>
                    </div>
                @endif
            </section>

            {{-- Filiation --}}
            <section
                class="rounded-4xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="mb-6 flex items-center gap-3">
                    <div class="h-6 w-1.5 rounded-full bg-indigo-600"></div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Filiation & origines</h3>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="relative rounded-2xl border border-indigo-100 bg-indigo-50/50 p-5 dark:border-indigo-500/20 dark:bg-indigo-500/5">
                        <span
                            class="absolute -top-3 left-5 rounded-full bg-indigo-600 px-3 py-0.5 text-[10px] font-black uppercase text-white">Père</span>
                        <h4 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ ucfirst($patient->nom_pere ?? 'Non renseigné') }}
                        </h4>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Province</dt>
                                <dd class="font-semibold text-slate-800 dark:text-slate-100">
                                    {{ ucfirst($patient->province_pere ?? '—') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Profession</dt>
                                <dd class="font-semibold text-slate-800 dark:text-slate-100">
                                    {{ ucfirst($patient->profession_pere ?? '—') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Tribu</dt>
                                <dd class="font-semibold text-slate-800 dark:text-slate-100">
                                    {{ ucfirst($patient->tribut_pere ?? '—') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="relative rounded-2xl border border-pink-100 bg-pink-50/50 p-5 dark:border-pink-500/20 dark:bg-pink-500/5">
                        <span
                            class="absolute -top-3 left-5 rounded-full bg-pink-600 px-3 py-0.5 text-[10px] font-black uppercase text-white">Mère</span>
                        <h4 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ ucfirst($patient->nom_mere ?? 'Non renseignée') }}
                        </h4>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Province</dt>
                                <dd class="font-semibold text-slate-800 dark:text-slate-100">
                                    {{ ucfirst($patient->province_mere ?? '—') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Profession</dt>
                                <dd class="font-semibold text-slate-800 dark:text-slate-100">
                                    {{ ucfirst($patient->profession_mere ?? '—') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Tribu</dt>
                                <dd class="font-semibold text-slate-800 dark:text-slate-100">
                                    {{ ucfirst($patient->tribut_mere ?? '—') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 md:grid-cols-2">
                <section
                    class="rounded-4xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Naissance</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Poids de naissance</dt>
                            <dd class="font-bold text-slate-900 dark:text-white">{{ $patient->poids_naissance ?? 'N/A' }}
                                kg</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Rang dans la fratrie</dt>
                            <dd class="font-black text-indigo-600 dark:text-indigo-300">
                                {{ $patient->rang_fratrie ?? '1' }} /
                                {{ ($patient->nb_freres ?? 0) + ($patient->nb_soeurs ?? 0) + 1 }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section
                    class="rounded-4xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Adresse actuelle</h3>
                    <p class="mt-4 text-sm font-medium leading-relaxed text-slate-800 dark:text-slate-100">
                        {{ $patient->num_habitation ? 'N°' . $patient->num_habitation . ', ' : '' }}Av.
                        {{ $patient->avenue ?? '—' }}<br>
                        Q. {{ $patient->quartier ?? '—' }}, C. {{ $patient->commune?->name ?? '—' }}
                    </p>
                    <p class="mt-2 text-xs font-bold text-indigo-600 dark:text-indigo-300">
                        {{ $patient->ville?->name ?? '—' }}, {{ $patient->province?->name ?? '—' }}
                    </p>
                </section>
            </div>
        </div>
    </div>

    <section class="rounded-4xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="mb-4">
            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Planification</p>
            <h3 class="text-xl font-black text-slate-900 dark:text-white">Visites programmées</h3>
        </div>
        <livewire:visite-programme-for-patient-table :dossierPatientId="$patient->id" />
    </section>
</div>
