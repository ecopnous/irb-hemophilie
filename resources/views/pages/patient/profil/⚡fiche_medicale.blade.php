<?php

use App\Enums\AllergyType;
use App\Models\Allergy;
use App\Models\DossierPatient;
use App\Models\Localisations\Country;
use App\Models\Localisations\Province;
use App\Services\Patient\PremierSigneService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app.other.profil_medical')] class extends Component {
    public DossierPatient $patient;

    public ?string $activeSection = null;

    public $photo = null;
    public $nom, $postnom, $prenom, $genre, $email, $telephone, $ins;
    public $etat_civil = 'Célibataire';
    public $date_naissance;

    public $poids_naissance;
    public $note;

    public $province_id, $ville_id, $commune_id, $country_id, $user_id, $assurance_id, $categorisation_id;
    public $quartier, $avenue, $num_habitation, $adresses_supplementaires;

    public $nom_pere, $nom_mere;
    public $province_pere, $tribut_pere, $profession_pere;
    public $province_mere, $tribut_mere, $profession_mere;

    public $type_famille = 'Monogame', $rang_fratrie = 1;
    public $nb_freres, $nb_soeurs;
    public $deces_freres, $deces_soeurs;
    public $histoire_famille_supplementaire;

    public $age_gestationnel, $allaitement_maternel, $med_traditionnel, $moringa_oleifera;
    public $indications, $duree_prise;
    public $vaccins;
    public $histoire_perso_supplementaire;

    public array $premierSignesForm = [];
    public $premier_signes_supplementaires;

    public array $sectionCompletionStatus = [];

    public $antecedents_medicales, $antecedents_chirurgicaux, $antecedents_familiaux, $antecedents_obstetricaux, $antecedents_gynocola, $antecedents_neurologiques, $antecedents_cardiovasculaires, $antecedents_digestifs, $antecedents_endocrinologiques, $antecedents_hematologiques, $antecedents_supplementaires;
    public $tag_ids = [];

    public ?int $allergyEditId = null;
    public ?int $pendingDeleteAllergyId = null;
    public string $allergyType = 'medicament';
    public ?string $allergyAutre = null;
    public string $allergySymptome = '';
    public string $allergySolution = '';
    public ?string $allergyDescription = null;
    public string $allergyDateDebut = '';
    public ?string $allergyDateFin = null;

    public function mount($id): void
    {
        $this->loadPatient($id);
        $this->syncFromPatient();
    }

    protected function loadPatient(int $id): void
    {
        $this->patient = DossierPatient::query()
            ->with([
                'province',
                'ville',
                'commune',
                'allergies' => fn ($query) => $query->latest('date_debut'),
                'premierSignes.definition',
            ])
            ->findOrFail($id);
    }

    public function syncFromPatient(): void
    {
        $this->nom = $this->patient->nom;
        $this->postnom = $this->patient->postnom;
        $this->prenom = $this->patient->prenom;
        $this->genre = $this->patient->genre;
        $this->email = $this->patient->email;
        $this->telephone = $this->patient->telephone;
        $this->ins = $this->patient->ins;
        $this->etat_civil = $this->patient->etat_civil;
        $this->date_naissance = $this->patient->date_naissance;
        $this->poids_naissance = $this->patient->poids_naissance;
        $this->type_famille = $this->patient->type_famille;
        $this->country_id = $this->patient->country_id;
        $this->note = $this->patient->note;

        $this->nom_pere = $this->patient->nom_pere;
        $this->profession_pere = $this->patient->profession_pere;
        $this->province_pere = $this->patient->province_pere;
        $this->tribut_pere = $this->patient->tribut_pere;
        $this->nom_mere = $this->patient->nom_mere;
        $this->profession_mere = $this->patient->profession_mere;
        $this->province_mere = $this->patient->province_mere;
        $this->tribut_mere = $this->patient->tribut_mere;
        $this->rang_fratrie = $this->patient->rang_fratrie;
        $this->nb_freres = $this->patient->nb_freres;
        $this->nb_soeurs = $this->patient->nb_soeurs;
        $this->deces_freres = $this->patient->deces_freres;
        $this->deces_soeurs = $this->patient->deces_soeurs;
        $this->histoire_famille_supplementaire = $this->patient->histoire_famille_supplementaire;

        $this->age_gestationnel = $this->patient->age_gestationnel;
        $this->allaitement_maternel = $this->patient->allaitement_maternel;
        $this->med_traditionnel = $this->patient->med_traditionnel;
        $this->moringa_oleifera = $this->patient->moringa_oleifera;
        $this->indications = $this->patient->indications;
        $this->duree_prise = $this->patient->duree_prise;
        $this->vaccins = $this->patient->vaccins;
        $this->histoire_perso_supplementaire = $this->patient->histoire_perso_supplementaire;

        $this->premier_signes_supplementaires = $this->patient->premier_signes_supplementaires;
        $this->premierSignesForm = app(PremierSigneService::class)->toFormArray($this->patient);

        $this->antecedents_medicales = $this->patient->antecedents_medicales;
        $this->antecedents_chirurgicaux = $this->patient->antecedents_chirurgicaux;
        $this->antecedents_familiaux = $this->patient->antecedents_familiaux;
        $this->antecedents_obstetricaux = $this->patient->antecedents_obstetricaux;
        $this->antecedents_gynocola = $this->patient->antecedents_gynocola;
        $this->antecedents_neurologiques = $this->patient->antecedents_neurologiques;
        $this->antecedents_cardiovasculaires = $this->patient->antecedents_cardiovasculaires;
        $this->antecedents_digestifs = $this->patient->antecedents_digestifs;
        $this->antecedents_endocrinologiques = $this->patient->antecedents_endocrinologiques;
        $this->antecedents_hematologiques = $this->patient->antecedents_hematologiques;
        $this->antecedents_supplementaires = $this->patient->antecedents_supplementaires;
        $this->adresses_supplementaires = $this->patient->adresses_supplementaires;

        $this->province_id = $this->patient->province_id;
        $this->ville_id = $this->patient->ville_id;
        $this->commune_id = $this->patient->commune_id;
        $this->quartier = $this->patient->quartier;
        $this->avenue = $this->patient->avenue;
        $this->num_habitation = $this->patient->num_habitation;

        $this->syncSectionCompletionStatus();
    }

    public function ficheSectionKeys(): array
    {
        return [
            'demographiques',
            'histoire_familiale',
            'histoire_personnelle',
            'histoire_maladie',
            'autres_antecedents',
            'localisation',
        ];
    }

    protected function syncSectionCompletionStatus(): void
    {
        $stored = $this->patient->fiche_section_status ?? [];

        foreach ($this->ficheSectionKeys() as $key) {
            $this->sectionCompletionStatus[$key] = ($stored[$key] ?? false) ? 1 : 0;
        }
    }

    public function sectionIsComplete(string $key): bool
    {
        return (bool) ($this->patient->fiche_section_status[$key] ?? false);
    }

    public function updated($property): void
    {
        if (! str_starts_with($property, 'sectionCompletionStatus.')) {
            return;
        }

        $section = str_replace('sectionCompletionStatus.', '', $property);
        $this->persistSectionStatus($section);
    }

    protected function persistSectionStatus(string $section): void
    {
        if (! in_array($section, $this->ficheSectionKeys(), true)) {
            return;
        }

        $status = $this->patient->fiche_section_status ?? [];
        $status[$section] = (bool) (int) ($this->sectionCompletionStatus[$section] ?? 0);

        $this->patient->update(['fiche_section_status' => $status]);
        $this->loadPatient($this->patient->id);
    }

    protected function sectionStatusPayloadFor(string $section): array
    {
        $status = $this->patient->fiche_section_status ?? [];
        $status[$section] = (bool) (int) ($this->sectionCompletionStatus[$section] ?? 0);

        return ['fiche_section_status' => $status];
    }

    public function openSection(string $section): void
    {
        $this->resetValidation();
        $this->activeSection = $section;
        $this->syncFromPatient();
    }

    public function sectionTitle(?string $section = null): string
    {
        return match ($section ?? $this->activeSection) {
            'demographiques' => 'Données démographiques',
            'histoire_familiale' => 'Histoire familiale',
            'histoire_personnelle' => 'Histoire personnelle',
            'histoire_maladie' => 'Histoire de la maladie',
            'autres_antecedents' => 'Autres antécédents',
            'localisation' => 'Localisation actuelle',
            default => 'Modifier la fiche',
        };
    }

    public function updateDemographiques(): void
    {
        try {
            $validated = $this->validate([
                'nom' => 'required|string|max:255',
                'postnom' => 'nullable|string|max:255',
                'prenom' => 'required|string|max:255',
                'genre' => 'required|in:M,F',
                'email' => 'nullable|email|max:255',
                'telephone' => 'nullable|string|max:20',
                'ins' => 'nullable|string|max:50',
                'etat_civil' => 'required|in:Célibataire,Marié,Divorsé,Veu(f)ve',
                'date_naissance' => 'required|date|before:today',
                'poids_naissance' => 'nullable|numeric|min:0|max:20',
                'type_famille' => 'required|in:Monogame,Polygame,Recomposée,Adaptative,Orphelinat',
                'country_id' => 'required|exists:countries,id',
                'note' => 'nullable|string',
            ]);

            $this->patient->update(array_merge($validated, $this->sectionStatusPayloadFor('demographiques')));
            $this->afterSectionSave('Les données démographiques ont été mises à jour.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Flux::toast(variant: 'error', heading: 'Mise à jour échouée', text: 'Vérifiez les champs obligatoires.');
            throw $e;
        }
    }

    public function updateHistoireFamiliale(): void
    {
        try {
            $validated = $this->validate([
                'nom_pere' => 'nullable|string|max:255',
                'profession_pere' => 'nullable|string|max:255',
                'province_pere' => 'nullable|exists:provinces,id',
                'tribut_pere' => 'nullable|string|max:255',
                'nom_mere' => 'nullable|string|max:255',
                'profession_mere' => 'nullable|string|max:255',
                'province_mere' => 'nullable|exists:provinces,id',
                'tribut_mere' => 'nullable|string|max:255',
                'rang_fratrie' => 'nullable|integer|min:1',
                'nb_freres' => 'nullable|integer|min:0',
                'nb_soeurs' => 'nullable|integer|min:0',
                'deces_freres' => 'nullable|integer|min:0',
                'deces_soeurs' => 'nullable|integer|min:0',
                'histoire_famille_supplementaire' => 'nullable|string',
            ]);

            $this->patient->update(array_merge($validated, $this->sectionStatusPayloadFor('histoire_familiale')));
            $this->afterSectionSave("L'histoire familiale a été mise à jour.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            Flux::toast(variant: 'error', heading: 'Mise à jour échouée', text: 'Vérifiez les champs saisis.');
            throw $e;
        }
    }

    public function updateHistoirePersonnelle(): void
    {
        try {
            $validated = $this->validate([
                'age_gestationnel' => 'nullable|integer|min:0',
                'allaitement_maternel' => 'nullable|boolean',
                'med_traditionnel' => 'nullable|boolean',
                'moringa_oleifera' => 'nullable|boolean',
                'indications' => 'nullable|string|max:255',
                'duree_prise' => 'nullable|string|max:255',
                'vaccins' => 'nullable|string',
                'histoire_perso_supplementaire' => 'nullable|string',
            ]);

            $this->patient->update(array_merge($validated, $this->sectionStatusPayloadFor('histoire_personnelle')));
            $this->afterSectionSave("L'histoire personnelle a été mise à jour.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            Flux::toast(variant: 'error', heading: 'Mise à jour échouée', text: 'Vérifiez les champs saisis.');
            throw $e;
        }
    }

    public function updateHistoireMaladie(): void
    {
        try {
            $service = app(PremierSigneService::class);
            $this->validate($service->validationRules());

            $service->sync($this->patient, $this->premierSignesForm);

            $this->patient->update(array_merge([
                'premier_signes_supplementaires' => $this->premier_signes_supplementaires ?: null,
            ], $this->sectionStatusPayloadFor('histoire_maladie')));

            $this->afterSectionSave("L'histoire de la maladie a été mise à jour.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            Flux::toast(variant: 'error', heading: 'Mise à jour échouée', text: 'Vérifiez les valeurs numériques saisies.');
            throw $e;
        }
    }

    public function updateAutresAntecedents(): void
    {
        try {
            $validated = $this->validate([
                'antecedents_medicales' => 'nullable|string',
                'antecedents_chirurgicaux' => 'nullable|string',
                'antecedents_familiaux' => 'nullable|string',
                'antecedents_obstetricaux' => 'nullable|string',
                'antecedents_gynocola' => 'nullable|string',
                'antecedents_neurologiques' => 'nullable|string',
                'antecedents_cardiovasculaires' => 'nullable|string',
                'antecedents_digestifs' => 'nullable|string',
                'antecedents_endocrinologiques' => 'nullable|string',
                'antecedents_hematologiques' => 'nullable|string',
                'antecedents_supplementaires' => 'nullable|string',
            ]);

            $this->patient->update(array_merge($validated, $this->sectionStatusPayloadFor('autres_antecedents')));
            $this->afterSectionSave('Les antécédents ont été mis à jour.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Flux::toast(variant: 'error', heading: 'Mise à jour échouée', text: 'Vérifiez les champs saisis.');
            throw $e;
        }
    }

    public function updateLocalisation(): void
    {
        try {
            $validated = $this->validate([
                'province_id' => 'nullable|exists:provinces,id',
                'ville_id' => 'nullable|exists:villes,id',
                'commune_id' => 'nullable|exists:communes,id',
                'quartier' => 'nullable|string|max:255',
                'avenue' => 'nullable|string|max:255',
                'num_habitation' => 'nullable|string|max:255',
                'adresses_supplementaires' => 'nullable|string',
            ]);

            $this->patient->update(array_merge($validated, $this->sectionStatusPayloadFor('localisation')));
            $this->afterSectionSave('La localisation a été mise à jour.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Flux::toast(variant: 'error', heading: 'Mise à jour échouée', text: 'Vérifiez les champs saisis.');
            throw $e;
        }
    }

    protected function afterSectionSave(string $message): void
    {
        $this->loadPatient($this->patient->id);
        $this->syncFromPatient();
        $this->activeSection = null;
        $this->dispatch('fiche-medicale-saved');
        Flux::toast(variant: 'success', heading: 'Mise à jour réussie', text: $message);
    }

    public function allergyTypeOptions(): array
    {
        return AllergyType::options();
    }

    public function allergyRequiresAutre(): bool
    {
        return $this->allergyType === AllergyType::Autre->value;
    }

    public function openAllergyModal(?int $id = null): void
    {
        $this->resetValidation();
        $this->resetAllergyForm();

        if ($id) {
            $allergy = $this->patient->allergies()->findOrFail($id);
            $this->allergyEditId = $allergy->id;
            $this->allergyType = $allergy->type?->value ?? AllergyType::Medicament->value;
            $this->allergyAutre = $allergy->autre;
            $this->allergySymptome = (string) $allergy->symptome;
            $this->allergySolution = (string) $allergy->solution;
            $this->allergyDescription = $allergy->description;
            $this->allergyDateDebut = $allergy->date_debut?->format('Y-m-d') ?? '';
            $this->allergyDateFin = $allergy->date_fin?->format('Y-m-d');
        } else {
            $this->allergyDateDebut = now()->format('Y-m-d');
        }
    }

    protected function resetAllergyForm(): void
    {
        $this->allergyEditId = null;
        $this->allergyType = AllergyType::Medicament->value;
        $this->allergyAutre = null;
        $this->allergySymptome = '';
        $this->allergySolution = '';
        $this->allergyDescription = null;
        $this->allergyDateDebut = now()->format('Y-m-d');
        $this->allergyDateFin = null;
    }

    protected function allergyValidationRules(): array
    {
        return [
            'allergyType' => ['required', 'in:medicament,alimentaire,environnementale,animaux,autre'],
            'allergyAutre' => ['required_if:allergyType,autre', 'nullable', 'string', 'max:255'],
            'allergySymptome' => ['required', 'string', 'max:255'],
            'allergySolution' => ['required', 'string', 'max:255'],
            'allergyDescription' => ['nullable', 'string', 'max:500'],
            'allergyDateDebut' => ['required', 'date'],
            'allergyDateFin' => ['nullable', 'date', 'after_or_equal:allergyDateDebut'],
        ];
    }

    public function saveAllergy(): void
    {
        try {
            $validated = $this->validate($this->allergyValidationRules());

            $payload = [
                'type' => $validated['allergyType'],
                'autre' => $validated['allergyType'] === 'autre' ? ($validated['allergyAutre'] ?: null) : null,
                'symptome' => $validated['allergySymptome'],
                'solution' => $validated['allergySolution'],
                'description' => $validated['allergyDescription'] ?: null,
                'date_debut' => $validated['allergyDateDebut'],
                'date_fin' => $validated['allergyDateFin'] ?: null,
            ];

            if ($this->allergyEditId) {
                $this->patient->allergies()->whereKey($this->allergyEditId)->update($payload);
                $message = 'Allergie mise à jour.';
            } else {
                $this->patient->allergies()->create($payload);
                $message = 'Allergie ajoutée au dossier.';
            }

            $this->loadPatient($this->patient->id);
            $this->resetAllergyForm();
            $this->dispatch('allergy-saved');
            Flux::toast(variant: 'success', heading: 'Allergie enregistrée', text: $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Flux::toast(variant: 'error', heading: 'Enregistrement échoué', text: 'Vérifiez les champs obligatoires.');
            throw $e;
        }
    }

    public function confirmDeleteAllergy(int $id): void
    {
        if (! $this->patient->allergies()->whereKey($id)->exists()) {
            return;
        }

        $this->pendingDeleteAllergyId = $id;
        $this->dispatch('allergy-delete-open');
    }

    public function deleteAllergy(): void
    {
        if (! $this->pendingDeleteAllergyId) {
            return;
        }

        $this->patient->allergies()->whereKey($this->pendingDeleteAllergyId)->delete();
        $this->pendingDeleteAllergyId = null;
        $this->loadPatient($this->patient->id);
        $this->dispatch('allergy-deleted');
        Flux::toast(variant: 'success', heading: 'Allergie supprimée', text: 'L\'allergie a été retirée du dossier.');
    }

    protected function isFilled(mixed $value): bool
    {
        return ! is_null($value) && $value !== '' && $value !== [];
    }

    protected function isAnyFilled(array $values): bool
    {
        foreach ($values as $value) {
            if ($this->isFilled($value)) {
                return true;
            }
        }

        return false;
    }

    public function display(mixed $value, string $empty = '—'): string
    {
        return $this->isFilled($value) ? (string) $value : $empty;
    }

    public function displayBool(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return (bool) $value ? 'Oui' : 'Non';
    }

    public function displayGenre(?string $genre): string
    {
        return match ($genre) {
            'M' => 'Homme',
            'F' => 'Femme',
            default => '—',
        };
    }

    public function displayDate($value): string
    {
        if (! $value) {
            return '—';
        }

        return $value instanceof \DateTimeInterface
            ? $value->format('d/m/Y')
            : (string) $value;
    }

    public function provinceLabel(mixed $provinceId): string
    {
        if (! $this->isFilled($provinceId)) {
            return '—';
        }

        return Province::query()->whereKey($provinceId)->value('name') ?? '—';
    }

    public function countryLabel(): string
    {
        if (! $this->isFilled($this->patient->country_id)) {
            return '—';
        }

        return Country::query()->whereKey($this->patient->country_id)->value('name') ?? '—';
    }

    public function vaccinTags(): array
    {
        if (! $this->isFilled($this->vaccins)) {
            return [];
        }

        return collect(explode(',', (string) $this->vaccins))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
    }

    public function antecedentFields(): array
    {
        return [
            'antecedents_medicales' => 'Médicaux',
            'antecedents_chirurgicaux' => 'Chirurgicaux',
            'antecedents_familiaux' => 'Familiaux',
            'antecedents_obstetricaux' => 'Obstétricaux',
            'antecedents_gynocola' => 'Gynécologiques',
            'antecedents_neurologiques' => 'Neurologiques',
            'antecedents_cardiovasculaires' => 'Cardiovasculaires',
            'antecedents_digestifs' => 'Digestifs',
            'antecedents_endocrinologiques' => 'Endocrinologiques',
            'antecedents_hematologiques' => 'Hématologiques',
            'antecedents_supplementaires' => 'Compléments',
        ];
    }

    public function premierSigneRows()
    {
        return app(PremierSigneService::class)->presentationRows($this->patient);
    }

    public function premierSigneProgress(): array
    {
        return app(PremierSigneService::class)->progress($this->patient);
    }

    public function completionSummary(): array
    {
        $sections = [
            ['key' => 'demographiques', 'label' => 'Démographie', 'complete' => $this->sectionIsComplete('demographiques')],
            ['key' => 'histoire_familiale', 'label' => 'Famille', 'complete' => $this->sectionIsComplete('histoire_familiale')],
            ['key' => 'histoire_personnelle', 'label' => 'Personnel', 'complete' => $this->sectionIsComplete('histoire_personnelle')],
            ['key' => 'histoire_maladie', 'label' => 'Maladie', 'complete' => $this->sectionIsComplete('histoire_maladie')],
            ['key' => 'autres_antecedents', 'label' => 'Antécédents', 'complete' => $this->sectionIsComplete('autres_antecedents')],
            ['key' => 'localisation', 'label' => 'Adresse', 'complete' => $this->sectionIsComplete('localisation')],
        ];

        $completed = collect($sections)->where('complete', true)->count();

        return [
            'sections' => $sections,
            'completed' => $completed,
            'total' => count($sections),
            'percent' => (int) round(($completed / count($sections)) * 100),
        ];
    }
};
?>

@php($summary = $this->completionSummary())

<div class="mx-auto max-w-7xl space-y-6">
    <x-patient.patient-profil-header :nav="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Dossiers patients', 'link' => 'patient.index', 'icon' => 'folder'],
        ['label' => $patient->nin, 'icon' => 'identification'],
    ]" :patient="$patient" :current_patient="$patient->id">
        <x-slot name="subtitle">{{ ucfirst($patient->nom) }} {{ ucfirst($patient->postnom) }}
            {{ ucfirst($patient->prenom) }}</x-slot>
    </x-patient.patient-profil-header>

    <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="bg-linear-to-r from-indigo-600 via-violet-600 to-sky-500 px-5 py-5 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-white/70">Fiche médicale</p>
                    <h2 class="mt-1 text-2xl font-black text-white">Synthèse du dossier clinique</h2>
                    <p class="mt-1 text-sm text-white/80">
                        Consultation rapide des informations — modification par section via modale.
                    </p>
                </div>
                <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-right backdrop-blur-sm">
                    <p class="text-3xl font-black text-white">{{ $summary['percent'] }}%</p>
                    <p class="text-xs font-semibold uppercase tracking-wider text-white/80">
                        {{ $summary['completed'] }}/{{ $summary['total'] }} sections
                    </p>
                </div>
            </div>
        </div>

        <div class="grid gap-2 border-t border-slate-100 p-4 sm:grid-cols-3 lg:grid-cols-6 dark:border-slate-800">
            @foreach ($summary['sections'] as $item)
                <div @class([
                    'rounded-xl border px-3 py-2.5 text-center',
                    'border-emerald-200 bg-emerald-50/80 dark:border-emerald-500/20 dark:bg-emerald-950/20' => $item['complete'],
                    'border-amber-200 bg-amber-50/80 dark:border-amber-500/20 dark:bg-amber-950/20' => ! $item['complete'],
                ])>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {{ $item['label'] }}
                    </p>
                    <p @class([
                        'mt-1 text-xs font-bold',
                        'text-emerald-700 dark:text-emerald-300' => $item['complete'],
                        'text-amber-700 dark:text-amber-300' => ! $item['complete'],
                    ])>
                        {{ $item['complete'] ? 'Complet' : 'Incomplet' }}
                    </p>
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-patient.fiche-section title="Données démographiques" icon="identification" accent="indigo"
            section="demographiques" :incomplete="! $this->sectionIsComplete('demographiques')"
            description="Identité et données de naissance du patient">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-patient.fiche-field label="Nom" :value="ucfirst((string) $nom)" :missing="!filled($nom)" />
                <x-patient.fiche-field label="Post-nom" :value="$this->display($postnom)" />
                <x-patient.fiche-field label="Prénom" :value="ucfirst((string) $prenom)" :missing="!filled($prenom)" />
                <x-patient.fiche-field label="État civil" :value="$this->display($etat_civil)" />
                <x-patient.fiche-field label="Date de naissance" :value="$this->displayDate($date_naissance)" :missing="!filled($date_naissance)" />
                <x-patient.fiche-field label="Genre" :value="$this->displayGenre($genre)" :missing="!filled($genre)" />
                <x-patient.fiche-field label="Poids de naissance" :value="filled($poids_naissance) ? $poids_naissance . ' kg' : '—'" />
                <x-patient.fiche-field label="N° identité santé" :value="$this->display($ins)" />
                <x-patient.fiche-field label="Type de famille" :value="$this->display($type_famille)" :missing="!filled($type_famille)" />
                <x-patient.fiche-field label="Pays de naissance" :value="$this->countryLabel()" :missing="!filled($country_id)" />
                <x-patient.fiche-field label="Téléphone" :value="$this->display($telephone)" />
                <x-patient.fiche-field label="E-mail" :value="$this->display($email)" />
            </div>
            @if (filled($note))
                <div
                    class="mt-5 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Note complémentaire</p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-200">{{ $note }}</p>
                </div>
            @endif
        </x-patient.fiche-section>

        <x-patient.fiche-section title="Localisation actuelle" icon="map-pin" accent="emerald" section="localisation"
            :incomplete="! $this->sectionIsComplete('localisation')"
            description="Adresse et localisation géographique">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-patient.fiche-field label="Province" :value="$patient->province?->name ?? '—'" :missing="!filled($province_id)" />
                <x-patient.fiche-field label="Ville" :value="$patient->ville?->name ?? '—'" :missing="!filled($ville_id)" />
                <x-patient.fiche-field label="Commune" :value="$patient->commune?->name ?? '—'" :missing="!filled($commune_id)" />
                <x-patient.fiche-field label="Quartier" :value="$this->display($quartier)" :missing="!filled($quartier)" />
                <x-patient.fiche-field label="Avenue" :value="$this->display($avenue)" :missing="!filled($avenue)" />
                <x-patient.fiche-field label="N° habitation" :value="$this->display($num_habitation)" :missing="!filled($num_habitation)" />
            </div>
            @if (filled($adresses_supplementaires))
                <div
                    class="mt-5 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Adresses
                        supplémentaires</p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                        {{ $adresses_supplementaires }}</p>
                </div>
            @endif
        </x-patient.fiche-section>

        <x-patient.fiche-section title="Histoire familiale" icon="users" accent="violet" section="histoire_familiale"
            :incomplete="! $this->sectionIsComplete('histoire_familiale')"
            description="Informations sur les parents et la fratrie">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="space-y-4 rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-violet-600 dark:text-violet-300">Père
                    </p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-patient.fiche-field label="Nom" :value="$this->display($nom_pere)" :missing="!filled($nom_pere)" />
                        <x-patient.fiche-field label="Profession" :value="$this->display($profession_pere)" />
                        <x-patient.fiche-field label="Province" :value="$this->provinceLabel($province_pere)" :missing="!filled($province_pere)" />
                        <x-patient.fiche-field label="Tribu" :value="$this->display($tribut_pere)" />
                    </div>
                </div>
                <div class="space-y-4 rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-violet-600 dark:text-violet-300">Mère
                    </p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-patient.fiche-field label="Nom" :value="$this->display($nom_mere)" :missing="!filled($nom_mere)" />
                        <x-patient.fiche-field label="Profession" :value="$this->display($profession_mere)" />
                        <x-patient.fiche-field label="Province" :value="$this->provinceLabel($province_mere)" :missing="!filled($province_mere)" />
                        <x-patient.fiche-field label="Tribu" :value="$this->display($tribut_mere)" />
                    </div>
                </div>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-5">
                <x-patient.fiche-field label="Rang fratrie" :value="$this->display($rang_fratrie)" />
                <x-patient.fiche-field label="Frères vivants" :value="$this->display($nb_freres)" />
                <x-patient.fiche-field label="Sœurs vivantes" :value="$this->display($nb_soeurs)" />
                <x-patient.fiche-field label="Frères décédés" :value="$this->display($deces_freres)" />
                <x-patient.fiche-field label="Sœurs décédées" :value="$this->display($deces_soeurs)" />
            </div>
            @if (filled($histoire_famille_supplementaire))
                <div
                    class="mt-5 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Compléments</p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                        {{ $histoire_famille_supplementaire }}</p>
                </div>
            @endif
        </x-patient.fiche-section>

        <x-patient.fiche-section title="Histoire personnelle" icon="heart" accent="sky"
            section="histoire_personnelle" :incomplete="! $this->sectionIsComplete('histoire_personnelle')"
            description="Données de grossesse et de petite enfance">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-patient.fiche-field label="Âge gestationnel" :value="filled($age_gestationnel) ? $age_gestationnel . ' sem/mois' : '—'" :missing="!filled($age_gestationnel)" />
                <x-patient.fiche-field label="Allaitement maternel" :value="$this->displayBool($allaitement_maternel)" :missing="!filled($allaitement_maternel)" />
                <x-patient.fiche-field label="Médicaments traditionnels" :value="$this->displayBool($med_traditionnel)" :missing="!filled($med_traditionnel)" />
                <x-patient.fiche-field label="Moringa oleifera" :value="$this->displayBool($moringa_oleifera)" :missing="!filled($moringa_oleifera)" />
                <x-patient.fiche-field label="Indications" :value="$this->display($indications)" />
                <x-patient.fiche-field label="Durée de prise" :value="$this->display($duree_prise)" />
            </div>
            @if ($this->vaccinTags() !== [])
                <div class="mt-5">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Vaccins</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($this->vaccinTags() as $tag)
                            <span
                                class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-800 dark:border-sky-500/30 dark:bg-sky-950/40 dark:text-sky-200">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @else
                <x-patient.fiche-field class="mt-5" label="Vaccins" value="—" :missing="true" />
            @endif
            @if (filled($histoire_perso_supplementaire))
                <div
                    class="mt-5 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Compléments</p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                        {{ $histoire_perso_supplementaire }}</p>
                </div>
            @endif
        </x-patient.fiche-section>

        <x-patient.fiche-section title="Histoire de la maladie" icon="presentation-chart-line" accent="rose"
            section="histoire_maladie" :incomplete="! $this->sectionIsComplete('histoire_maladie')"
            description="Premiers signes et évolution de la maladie"
            class="xl:col-span-2">
            <div
                class="mb-4 flex items-center justify-between gap-3 rounded-2xl border border-rose-100 bg-rose-50/60 px-4 py-3 dark:border-rose-500/20 dark:bg-rose-950/20">
                <p class="text-xs font-medium text-rose-900 dark:text-rose-200">
                    Répondez aux références au fil du temps. Seules les questions remplies sont enregistrées.
                </p>
                <span class="shrink-0 text-sm font-black text-rose-700 dark:text-rose-300">
                    {{ $this->premierSigneProgress()['percent'] }}%
                </span>
            </div>
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                @foreach ($this->premierSigneRows() as $row)
                    <x-patient.premier-signe-display :definition="$row['definition']" :answer="$row['answer']"
                        wire:key="premier-signe-{{ $row['definition']->key }}" />
                @endforeach
            </div>
            @if (filled($premier_signes_supplementaires))
                <div
                    class="mt-5 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Compléments généraux
                    </p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                        {{ $premier_signes_supplementaires }}</p>
                </div>
            @endif
        </x-patient.fiche-section>

        <x-patient.fiche-section title="Autres antécédents" icon="clipboard-document-list" accent="amber"
            section="autres_antecedents" :incomplete="! $this->sectionIsComplete('autres_antecedents')"
            description="Antécédents médicaux et chirurgicaux" class="xl:col-span-2">
            <div class="grid grid-cols-2 gap-3">
                @foreach ($this->antecedentFields() as $field => $label)
                    @php($value = $this->{$field})
                    <div @class([
                        'rounded-2xl border px-4 py-3',
                        'border-slate-100 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-900/40' => filled($value),
                        'border-amber-200/80 bg-amber-50/40 dark:border-amber-500/20 dark:bg-amber-950/10' => ! filled($value),
                    ])>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ $label }}
                        </p>
                        <p @class([
                            'mt-1.5 text-sm leading-relaxed',
                            'text-slate-700 dark:text-slate-200' => filled($value),
                            'italic text-amber-700 dark:text-amber-300' => ! filled($value),
                        ])>
                            {{ filled($value) ? $value : 'Non renseigné' }}
                        </p>
                    </div>
                @endforeach
            </div>
        </x-patient.fiche-section>
    </div>

    <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div
            class="border-b border-slate-100 bg-linear-to-r from-rose-500/10 to-rose-500/0 px-5 py-4 dark:border-slate-800">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-2xl border border-rose-200/70 bg-white/80 dark:border-rose-500/20 dark:bg-slate-900/80">
                        <flux:icon.shield-exclamation class="size-5 text-rose-600 dark:text-rose-300" />
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Allergies connues</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Informations de sécurité thérapeutique
                        </p>
                    </div>
                </div>
                <flux:button size="sm" variant="primary" color="rose" icon="plus"
                    wire:click="openAllergyModal" x-on:click="$tsui.open.modal('allergy-modal')">
                    Ajouter une allergie
                </flux:button>
            </div>
        </div>
        <div class="p-5 sm:p-6">
            @if ($patient->allergies->isNotEmpty())
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    @foreach ($patient->allergies as $allergy)
                        <x-patient.allergy-card :allergy="$allergy" wire:key="allergy-{{ $allergy->id }}" />
                    @endforeach
                </div>
            @else
                <div
                    class="rounded-2xl border border-dashed border-rose-200 bg-rose-50/30 px-5 py-8 text-center dark:border-rose-500/20 dark:bg-rose-950/10">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Aucune allergie enregistrée</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Ajoutez les allergies connues pour sécuriser les prescriptions et actes médicaux.
                    </p>
                    <flux:button class="mt-4" size="sm" variant="primary" color="rose" icon="plus"
                        wire:click="openAllergyModal" x-on:click="$tsui.open.modal('allergy-modal')">
                        Déclarer une allergie
                    </flux:button>
                </div>
            @endif
        </div>
    </section>

    <x-modal id="fiche-medicale-edit-modal" :title="$this->sectionTitle()" size="6xl" center z-index="z-20" persistent
        x-on:fiche-medicale-saved.window="$tsui.close.modal('fiche-medicale-edit-modal')">
        <div class="space-y-5">
            <div
                class="rounded-2xl border border-indigo-100 bg-indigo-50/80 p-4 text-sm text-indigo-900 dark:border-indigo-500/20 dark:bg-indigo-950/30 dark:text-indigo-100">
                <p class="font-semibold">{{ ucfirst($patient->nom) }} {{ ucfirst($patient->prenom) }}</p>
                <p class="mt-1 text-xs">NIN {{ $patient->nin }} · Modification de la section
                    {{ strtolower($this->sectionTitle()) }}</p>
            </div>

            <flux:icon.loading wire:loading wire:target="openSection" />

            <div wire:loading.remove wire:target="openSection">
                @if ($activeSection === 'demographiques')
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <x-input label="Nom *" wire:model="nom" />
                        <x-input label="Post-nom" wire:model="postnom" />
                        <x-input label="Prénom *" wire:model="prenom" />
                        <x-select.styled label="État civil *" wire:model="etat_civil" :options="[
                            ['label' => 'Célibataire', 'value' => 'Célibataire'],
                            ['label' => 'Marié', 'value' => 'Marié'],
                            ['label' => 'Divorsé', 'value' => 'Divorsé'],
                            ['label' => 'Veu(f)ve', 'value' => 'Veu(f)ve'],
                        ]" />
                        <x-date wire:model="date_naissance" label="Date de naissance *" />
                        <x-number wire:model="poids_naissance" label="Poids de naissance (kg)" step="0.1" />
                        <x-select.styled label="Genre *" wire:model="genre" :options="[['label' => 'Homme', 'value' => 'M'], ['label' => 'Femme', 'value' => 'F']]" />
                        <x-input label="N° identité santé" wire:model="ins" />
                        <x-select.styled wire:model="type_famille" label="Type de famille *"
                            :options="['Monogame', 'Polygame', 'Recomposée', 'Adaptative', 'Orphelinat']" />
                        <x-select.styled wire:model="country_id" label="Pays de naissance *"
                            :request="route('api.countries')" select="label:name|value:id" />
                        <x-input wire:model="telephone" label="Téléphone" />
                        <x-input wire:model="email" label="E-mail" />
                    </div>
                    <x-textarea class="mt-4" wire:model="note" label="Infos supplémentaires" rows="4"
                        maxlength="500" count />
                @endif

                @if ($activeSection === 'histoire_familiale')
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div class="space-y-4 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                            <flux:subheading>Père</flux:subheading>
                            <x-input label="Nom du père" wire:model="nom_pere" />
                            <x-input label="Profession" wire:model="profession_pere" />
                            <x-select.styled label="Province d'origine" wire:model="province_pere"
                                :request="route('api.provinces')" select="label:name|value:id" />
                            <x-input label="Tribu" wire:model="tribut_pere" />
                        </div>
                        <div class="space-y-4 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                            <flux:subheading>Mère</flux:subheading>
                            <x-input label="Nom de la mère" wire:model="nom_mere" />
                            <x-input label="Profession" wire:model="profession_mere" />
                            <x-select.styled label="Province d'origine" wire:model="province_mere"
                                :request="route('api.provinces')" select="label:name|value:id" />
                            <x-input label="Tribu" wire:model="tribut_mere" />
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-5">
                        <x-number label="Rang fratrie" wire:model="rang_fratrie" />
                        <x-number label="Frères vivants" wire:model="nb_freres" />
                        <x-number label="Sœurs vivantes" wire:model="nb_soeurs" />
                        <x-number label="Frères décédés" wire:model="deces_freres" />
                        <x-number label="Sœurs décédées" wire:model="deces_soeurs" />
                    </div>
                    <x-textarea class="mt-4" wire:model="histoire_famille_supplementaire" label="Compléments"
                        rows="4" maxlength="500" count />
                @endif

                @if ($activeSection === 'histoire_personnelle')
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-input label="Âge gestationnel (sem/mois)" wire:model="age_gestationnel" />
                        <x-select.styled label="Allaitement maternel" wire:model="allaitement_maternel"
                            :options="[['label' => 'Oui', 'value' => 1], ['label' => 'Non', 'value' => 0]]" />
                        <x-select.styled label="Médicaments traditionnels" wire:model="med_traditionnel"
                            :options="[['label' => 'Oui', 'value' => 1], ['label' => 'Non', 'value' => 0]]" />
                        <x-select.styled label="Moringa oleifera" wire:model="moringa_oleifera"
                            :options="[['label' => 'Oui', 'value' => 1], ['label' => 'Non', 'value' => 0]]" />
                        <x-input label="Indications" wire:model="indications" />
                        <x-input label="Durée de prise" wire:model="duree_prise" />
                    </div>
                    <x-tag class="mt-4" prefix="@" label="Vaccins" wire:model="vaccins"
                        hint="Appuyer sur Entrée ou la virgule pour ajouter un vaccin" />
                    <x-textarea class="mt-4" wire:model="histoire_perso_supplementaire" label="Compléments"
                        rows="4" maxlength="500" count />
                @endif

                @if ($activeSection === 'histoire_maladie')
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        Renseignez uniquement les références connues aujourd'hui. L'âge ou le nombre n'est pas
                        obligatoire, même en cas de réponse Oui.
                    </p>
                    <div class="space-y-4">
                        @foreach (app(\App\Services\Patient\PremierSigneService::class)->definitions() as $definition)
                            <x-patient.premier-signe-editor :definition="$definition" :wire-key="$definition->key"
                                :present="$premierSignesForm[$definition->key]['present'] ?? null"
                                wire:key="premier-signe-editor-{{ $definition->key }}" />
                        @endforeach
                    </div>
                    <x-textarea class="mt-4" wire:model="premier_signes_supplementaires"
                        label="Compléments généraux" rows="4" maxlength="1000" count
                        placeholder="Observations transversales sur les premiers signes..." />
                @endif

                @if ($activeSection === 'autres_antecedents')
                    <div class="space-y-4">
                        @foreach ($this->antecedentFields() as $field => $label)
                            <x-textarea wire:model="{{ $field }}" :label="$label" rows="3"
                                maxlength="500" count />
                        @endforeach
                    </div>
                @endif

                @if ($activeSection === 'localisation')
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <x-select.styled label="Province" wire:model.live="province_id" :request="route('api.provinces')"
                            select="label:name|value:id" searchable />
                        <x-select.styled label="Ville" wire:model.live="ville_id"
                            :request="['url' => route('api.villes'), 'params' => ['province' => $province_id]]" select="label:name|value:id"
                            searchable />
                        <x-select.styled label="Commune" wire:model="commune_id"
                            :request="['url' => route('api.communes'), 'params' => ['ville' => $ville_id]]" select="label:name|value:id"
                            searchable />
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <x-input wire:model="quartier" label="Quartier" />
                        <x-input wire:model="avenue" label="Avenue" />
                        <x-input wire:model="num_habitation" label="N° habitation" />
                    </div>
                    <x-textarea class="mt-4" wire:model="adresses_supplementaires"
                        label="Autres adresses supplémentaires" rows="4" maxlength="500" count />
                @endif
            </div>

            @if ($activeSection)
                <x-patient.fiche-section-status-field :section="$activeSection" class="mt-4" />
            @endif
        </div>

        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('fiche-medicale-edit-modal')">
                    Annuler
                </flux:button>
                @if ($activeSection === 'demographiques')
                    <flux:button variant="primary" color="indigo" wire:click="updateDemographiques">Enregistrer
                    </flux:button>
                @elseif ($activeSection === 'histoire_familiale')
                    <flux:button variant="primary" color="indigo" wire:click="updateHistoireFamiliale">Enregistrer
                    </flux:button>
                @elseif ($activeSection === 'histoire_personnelle')
                    <flux:button variant="primary" color="indigo" wire:click="updateHistoirePersonnelle">Enregistrer
                    </flux:button>
                @elseif ($activeSection === 'histoire_maladie')
                    <flux:button variant="primary" color="indigo" wire:click="updateHistoireMaladie">Enregistrer
                    </flux:button>
                @elseif ($activeSection === 'autres_antecedents')
                    <flux:button variant="primary" color="indigo" wire:click="updateAutresAntecedents">Enregistrer
                    </flux:button>
                @elseif ($activeSection === 'localisation')
                    <flux:button variant="primary" color="indigo" wire:click="updateLocalisation">Enregistrer
                    </flux:button>
                @endif
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal id="allergy-modal" :title="$allergyEditId ? 'Modifier l\'allergie' : 'Ajouter une allergie'" size="4xl" center persistent
        x-on:allergy-saved.window="$tsui.close.modal('allergy-modal')">
        <div class="space-y-5">
            <div
                class="rounded-2xl border border-rose-100 bg-rose-50/80 p-4 text-sm text-rose-900 dark:border-rose-500/20 dark:bg-rose-950/30 dark:text-rose-100">
                <p class="font-semibold">{{ ucfirst($patient->nom) }} {{ ucfirst($patient->prenom) }}</p>
                <p class="mt-1 text-xs">Documentez le type, les symptômes et la conduite à tenir en cas d'exposition.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-select.styled label="Type d'allergie *" wire:model.live="allergyType" :options="$this->allergyTypeOptions()" />
                @if ($this->allergyRequiresAutre())
                    <x-input label="Précision (autre) *" wire:model="allergyAutre"
                        placeholder="Ex. pénicilline, arachide..." />
                @endif
                <x-date wire:model="allergyDateDebut" label="Date de début *" />
                <x-date wire:model="allergyDateFin" label="Date de fin (si résolue)" />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Symptômes *" wire:model="allergySymptome"
                    placeholder="Ex. urticaire, dyspnée, choc..." />
                <x-input label="Conduite à tenir *" wire:model="allergySolution"
                    placeholder="Ex. éviter le produit, antihistaminique..." />
            </div>

            <x-textarea wire:model="allergyDescription" label="Description complémentaire" rows="3" maxlength="500"
                count placeholder="Contexte, gravité, traitements antérieurs..." />
        </div>

        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('allergy-modal')">
                    Annuler
                </flux:button>
                <flux:button variant="primary" color="rose" wire:click="saveAllergy">
                    {{ $allergyEditId ? 'Enregistrer' : 'Ajouter' }}
                </flux:button>
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal id="allergy-delete-modal" title="Supprimer l'allergie" size="2xl" center persistent
        x-on:allergy-delete-open.window="$tsui.open.modal('allergy-delete-modal')"
        x-on:allergy-deleted.window="$tsui.close.modal('allergy-delete-modal')">
        <p class="text-sm text-slate-600 dark:text-slate-300">
            Confirmez la suppression de cette allergie du dossier patient. Cette action est irréversible.
        </p>

        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('allergy-delete-modal')">
                    Annuler
                </flux:button>
                <flux:button variant="danger" wire:click="deleteAllergy">
                    Supprimer
                </flux:button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
