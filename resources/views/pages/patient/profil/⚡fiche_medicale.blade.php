<?php
use App\Models\DossierPatient;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Flux\Flux;

new #[Layout('layouts::app.other.profil_medical')] class extends Component {
    public $patient;
    public $update_demographiques = false;
    public $update_histoire_familiale = false;
    public $update_histoire_personnelle = false;
    public $update_histoire_maladie = false;
    public $update_autres_antecedents = false;
    public $update_localisation = false;

    public $photo = null;
    public $nom, $postnom, $prenom, $genre, $email, $telephone, $ins;
    public $etat_civil = 'Célibataire';
    public $date_naissance;

    // --- Santé / Naissance ---
    public $poids_naissance;
    public $note;

    // --- Localisation ---
    public $province_id, $ville_id, $commune_id, $country_id, $user_id, $assurance_id, $categorisation_id;
    public $quartier, $avenue, $num_habitation, $adresses_supplementaires;

    // --- Parents ---
    public $nom_pere, $nom_mere;
    public $province_pere, $tribut_pere, $profession_pere;
    public $province_mere, $tribut_mere, $profession_mere;

    // --- Fratrie & Famille ---
    public $type_famille = 'Monogame',
        $rang_fratrie = 1;
    public $nb_freres, $nb_soeurs;
    public $deces_freres, $deces_soeurs;
    public $histoire_famille_supplementaire;

    // --- Histoire personnelle ---
    public $age_gestationnel, $allaitement_maternel, $med_traditionnel, $moringa_oleifera;
    public $indications, $duree_prise;
    public $vaccins;
    public $histoire_perso_supplementaire;

    // --- Histoire de la maladie ---
    public $syndrome_mains_pieds, $fievre, $itere, $cvo;
    public $transfusion, $nbr_transfusion, $episodes_epistaxis, $nbr_cvo_an;
    public $premier_signes_supplementaires;

    // --- Autres antecedents ---
    public $antecedents_medicales, $antecedents_chirurgicaux, $antecedents_familiaux, $antecedents_obstetricaux, $antecedents_gynocola, $antecedents_neurologiques, $antecedents_cardiovasculaires, $antecedents_digestifs, $antecedents_endocrinologiques, $antecedents_hematologiques, $antecedents_supplementaires;
    public $tag_ids = [];

    public function mount($id)
    {
        $this->patient = DossierPatient::findOrFail($id);
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

        //
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

        //
        $this->age_gestationnel = $this->patient->age_gestationnel;
        $this->allaitement_maternel = $this->patient->allaitement_maternel;
        $this->med_traditionnel = $this->patient->med_traditionnel;
        $this->moringa_oleifera = $this->patient->moringa_oleifera;
        $this->indications = $this->patient->indications;
        $this->duree_prise = $this->patient->duree_prise;
        $this->vaccins = $this->patient->vaccins;
        $this->histoire_perso_supplementaire = $this->patient->histoire_perso_supplementaire;

        //
        $this->syndrome_mains_pieds = $this->patient->syndrome_mains_pieds;
        $this->fievre = $this->patient->fievre;
        $this->itere = $this->patient->itere;
        $this->cvo = $this->patient->cvo;
        $this->transfusion = $this->patient->transfusion;
        $this->nbr_transfusion = $this->patient->nbr_transfusion;
        $this->episodes_epistaxis = $this->patient->episodes_epistaxis;
        $this->nbr_cvo_an = $this->patient->nbr_cvo_an;
        $this->premier_signes_supplementaires = $this->patient->premier_signes_supplementaires;

        //
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
        $this->adresses_supplementaires = $this->patient->adresses_supplementaires;

        //
        $this->province_id = $this->patient->province_id;
        $this->ville_id = $this->patient->ville_id;
        $this->commune_id = $this->patient->commune_id;
        $this->quartier = $this->patient->quartier;
        $this->avenue = $this->patient->avenue;
        $this->num_habitation = $this->patient->num_habitation;

        view()->share('current_patient', $id);
    }

    public function updateDemographiques()
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

            $this->patient->update($validated);

            // Réinitialiser le mode édition
            $this->update_demographiques = false;

            // Afficher le toast de succès
            Flux::toast(variant: 'success', heading: 'Mise à jour réussie', text: 'Les données démographiques ont été mises à jour avec succès!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Afficher le toast d'erreur
            Flux::toast(variant: 'error', heading: 'Mise à jour échouée', text: 'Une erreur est survenue : ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateHistoireFamiliale()
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

            $this->patient->update($validated);

            // Réinitialiser le mode édition
            $this->update_histoire_familiale = false;

            // Afficher le toast de succès
            Flux::toast(variant: 'success', heading: 'Mise à jour réussie', text: "L'histoire familiale a été mise à jour avec succès!");
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Afficher le toast d'erreur
            Flux::toast(variant: 'error', heading: 'Mise à jour échouée', text: 'Une erreur est survenue : ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateHistoirePersonnelle()
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

            $this->patient->update($validated);

            // Réinitialiser le mode édition
            $this->update_histoire_personnelle = false;

            // Afficher le toast de succès
            Flux::toast(variant: 'success', heading: 'Mise à jour réussie', text: "L'histoire personnelle a été mise à jour avec succès!");
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Afficher le toast d'erreur
            Flux::toast(variant: 'error', heading: 'Mise à jour échouée', text: 'Une erreur est survenue : ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateHistoireMaladie()
    {
        try {
            $validated = $this->validate([
                'syndrome_mains_pieds' => 'nullable|integer|min:0',
                'fievre' => 'nullable|integer|min:0',
                'itere' => 'nullable|integer|min:0',
                'cvo' => 'nullable|integer|min:0',
                'transfusion' => 'nullable|integer|min:0',
                'nbr_transfusion' => 'nullable|integer|min:0',
                'episodes_epistaxis' => 'nullable|integer|min:0',
                'nbr_cvo_an' => 'nullable|integer|min:0',
                'premier_signes_supplementaires' => 'nullable|string',
            ]);

            $this->patient->update($validated);

            // Réinitialiser le mode édition
            $this->update_histoire_maladie = false;

            // Afficher le toast de succès
            Flux::toast(variant: 'success', heading: 'Mise à jour réussie', text: "L'histoire de la maladie a été mise à jour avec succès!");
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Afficher le toast d'erreur
            Flux::toast(variant: 'error', heading: 'Mise à jour échouée', text: 'Une erreur est survenue : ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateAutresAntecedents()
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
                'adresses_supplementaires' => 'nullable|string',
            ]);

            $this->patient->update($validated);

            // Réinitialiser le mode édition
            $this->update_autres_antecedents = false;

            // Afficher le toast de succès
            Flux::toast(variant: 'success', heading: 'Mise à jour réussie', text: 'Les autres antecedents ont été mise à jour avec succès!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Afficher le toast d'erreur
            Flux::toast(variant: 'error', heading: 'Mise à jour échouée', text: 'Une erreur est survenue : ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateLocalisation()
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

            $this->patient->update($validated);

            // Réinitialiser le mode édition
            $this->update_localisation = false;

            // Afficher le toast de succès
            Flux::toast(variant: 'success', heading: 'Mise à jour réussie', text: 'La localisation a été mise à jour avec succès!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Afficher le toast d'erreur
            Flux::toast(variant: 'error', heading: 'Mise à jour échouée', text: 'Une erreur est survenue : ' . $e->getMessage());
            throw $e;
        }
    }

    protected function isFilled(mixed $value): bool
    {
        return !is_null($value) && $value !== '' && $value !== [];
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

    public function isIncompleteDemographiques(): bool
    {
        return !$this->isFilled($this->nom) || !$this->isFilled($this->prenom) || !$this->isFilled($this->genre) || !$this->isFilled($this->date_naissance) || !$this->isFilled($this->type_famille) || !$this->isFilled($this->country_id);
    }

    public function isIncompleteHistoireFamiliale(): bool
    {
        return !$this->isFilled($this->nom_pere) || !$this->isFilled($this->province_pere) || !$this->isFilled($this->nom_mere) || !$this->isFilled($this->province_mere);
    }

    public function isIncompleteHistoirePersonnelle(): bool
    {
        return !$this->isFilled($this->age_gestationnel) || !$this->isFilled($this->allaitement_maternel) || !$this->isFilled($this->med_traditionnel) || !$this->isFilled($this->moringa_oleifera);
    }

    public function isIncompleteHistoireMaladie(): bool
    {
        return !$this->isFilled($this->syndrome_mains_pieds) || !$this->isFilled($this->fievre) || !$this->isFilled($this->itere) || !$this->isFilled($this->cvo) || !$this->isFilled($this->transfusion) || !$this->isFilled($this->nbr_transfusion) || !$this->isFilled($this->episodes_epistaxis) || !$this->isFilled($this->nbr_cvo_an);
    }

    public function isIncompleteAutresAntecedents(): bool
    {
        return !$this->isAnyFilled([$this->antecedents_medicales, $this->antecedents_chirurgicaux, $this->antecedents_familiaux, $this->antecedents_obstetricaux, $this->antecedents_gynocola, $this->antecedents_neurologiques, $this->antecedents_cardiovasculaires, $this->antecedents_digestifs, $this->antecedents_endocrinologiques, $this->antecedents_hematologiques, $this->antecedents_supplementaires]);
    }

    public function isIncompleteLocalisation(): bool
    {
        return !$this->isFilled($this->province_id) || !$this->isFilled($this->ville_id) || !$this->isFilled($this->commune_id) || !$this->isFilled($this->quartier) || !$this->isFilled($this->avenue) || !$this->isFilled($this->num_habitation);
    }
};
?>

<div class="mx-auto max-w-7xl space-y-6">

    <x-patient.patient-profil-header :nav="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Dossiers patients', 'link' => 'patient.index', 'icon' => 'folder'],
        ['label' => $patient->nin, 'icon' => 'identification'],
    ]" :patient="$patient" :current_patient="$current_patient">
        <x-slot name="subtitle">{{ ucfirst($patient->nom) }} {{ ucfirst($patient->postnom) }}
            {{ ucfirst($patient->prenom) }}</x-slot>
    </x-patient.patient-profil-header>

    <div class="space-y-6">
        <x-card header="Données démographiques" minimize loading="updateDemographiques"
            class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70 {{ $this->isIncompleteDemographiques() ? 'ring-1 ring-amber-300/70 dark:ring-amber-500/30' : '' }}">
            @if ($this->isIncompleteDemographiques())
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    Informations incomplètes : compléter les champs essentiels.
                </div>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <x-input label="Nom !" wire:model="nom" placeholder="Entrez le nom du patient" :readonly="!$update_demographiques" />
                <x-input label="Post-nom" wire:model="postnom" placeholder="Entrez le post-nom du patient"
                    :readonly="!$update_demographiques" />
                <x-input label="Prénom !" wire:model="prenom" placeholder="Entrez le prénom du patient"
                    :readonly="!$update_demographiques" />
                <x-select.styled :readonly="!$update_demographiques" label="Etat civil !" wire:model="etat_civil"
                    placeholder="Sélectionnez" required :options="[
                        ['label' => 'Célibataire', 'value' => 'Célibataire'],
                        ['label' => 'Marié', 'value' => 'Marié'],
                        ['label' => 'Divorsé', 'value' => 'Divorsé'],
                        ['label' => 'Veu(f)ve', 'value' => 'Veu(f)ve'],
                    ]" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <x-date disabled wire:model="date_naissance" label="Date de naissance !"
                    placeholder="Date de naissance" />
                <x-number wire:model="poids_naissance" label="Poids de naissance (Kg)" step="0.1" chevron
                    :readonly="!$update_demographiques" />
                <x-select.styled :readonly="!$update_demographiques" label="Genre !" wire:model="genre" required :options="[['label' => 'Homme', 'value' => 'M'], ['label' => 'Femme', 'value' => 'F']]" />
                <x-input label="N° Identité santé" wire:model="ins" placeholder="N° identité" :readonly="!$update_demographiques" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <x-select.styled :readonly="!$update_demographiques" wire:model="type_famille" label="Type de famille !" required
                    :options="['Monogame', 'Polygame', 'Recomposée', 'Adaptative', 'Orphelinat']" />
                <x-select.styled :readonly="!$update_demographiques" wire:model="country_id" label="Pays de naissance !"
                    :request="route('api.countries')" select="label:name|value:id" required />
                <x-input :readonly="!$update_demographiques" wire:model="telephone" label="Téléphone" />
                <x-input wire:model="email" label="E-mail" placeholder="email@exemple.com" :readonly="!$update_demographiques" />
            </div>
            <x-textarea wire:model="note" label="Infos supplémentaires" placeholder="Note ici..." maxlength="500" count
                :readonly="!$update_demographiques" />
            <div
                class="mt-6 rounded-2xl border border-sky-200 bg-sky-50/80 p-4 dark:border-sky-500/20 dark:bg-sky-500/10">
                <label class="flex items-start gap-3">
                    <x-toggle wire:model.live="update_demographiques" wire:loading.attr="disabled" />
                    <div class="flex justify-between w-full">
                        <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">Coché pour modifier les données
                        </p>
                        <flux:icon.loading wire:loading wire:target="update_demographiques" />
                    </div>
                </label>
            </div>

            @if ($update_demographiques)
                <x-slot:footer>
                    <div class="flex justify-end">
                        <flux:button variant="primary" icon="save" wire:click="updateDemographiques" color="indigo">
                            Enregistrer les modifications
                        </flux:button>
                    </div>
                </x-slot:footer>
            @endif
        </x-card>

        <x-card header="Histoire familiale" minimize="mount"
            class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            @if ($this->isIncompleteHistoireFamiliale())
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    Données familiales incomplètes : compléter noms et provinces des parents.
                </div>
            @endif
            <div
                class="flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-gray-200 dark:divide-gray-700">
                <div class="flex-1 pb-6 lg:pb-0 lg:pr-4">
                    <flux:subheading size="lg" class="mb-4 px-2">Père</flux:subheading>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input label="Nom du père !" wire:model="nom_pere" :readonly="!$update_histoire_familiale" />
                        <x-input label="Profession" wire:model="profession_pere" :readonly="!$update_histoire_familiale" />
                        <x-select.styled label="Province d'origine !" wire:model="province_pere" :request="route('api.provinces')"
                            select="label:name|value:id" :readonly="!$update_histoire_familiale" />
                        <x-input label="Tribu" wire:model="tribut_pere" :readonly="!$update_histoire_familiale" />
                    </div>
                </div>

                <div class="flex-1 pt-6 lg:pt-0 lg:pl-4">
                    <flux:subheading size="lg" class="mb-4 px-2">Mère</flux:subheading>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input label="Nom de la mère !" wire:model="nom_mere" :readonly="!$update_histoire_familiale" />
                        <x-input label="Profession" wire:model="profession_mere" :readonly="!$update_histoire_familiale" />
                        <x-select.styled label="Province d'origine !" wire:model="province_mere" :request="route('api.provinces')"
                            select="label:name|value:id" :readonly="!$update_histoire_familiale" />
                        <x-input label="Tribu" wire:model="tribut_mere" :readonly="!$update_histoire_familiale" />
                    </div>
                </div>
            </div>

            <div class="my-6">
                <flux:separator />
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                <x-number label="Rang Fratrie" wire:model="rang_fratrie" :readonly="!$update_histoire_familiale" />
                <x-number label="Frères vivants" wire:model="nb_freres" :readonly="!$update_histoire_familiale" />
                <x-number label="Soeurs vivantes" wire:model="nb_soeurs" :readonly="!$update_histoire_familiale" />
                <x-number label="Frères décédés" wire:model="deces_freres" :readonly="!$update_histoire_familiale" />
                <x-number label="Soeurs décédées" wire:model="deces_soeurs" :readonly="!$update_histoire_familiale" />
            </div>

            <x-textarea wire:model="histoire_famille_supplementaire" label="Infos supplémentaires"
                placeholder="Note ici..." maxlength="500" count :readonly="!$update_histoire_familiale" />

            <div
                class="mt-6 rounded-2xl border border-sky-200 bg-sky-50/80 p-4 dark:border-sky-500/20 dark:bg-sky-500/10">
                <label class="flex items-start gap-3">
                    <x-toggle wire:model.live="update_histoire_familiale" wire:loading.attr="disabled" />
                    <div class="flex justify-between w-full">
                        <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">Coché pour modifier les données
                        </p>
                        <flux:icon.loading wire:loading wire:target="update_histoire_familiale" />
                    </div>
                </label>
            </div>

            @if ($update_histoire_familiale)
                <x-slot:footer>
                    <div class="flex justify-end">
                        <flux:button variant="primary" icon="save" wire:click="updateHistoireFamiliale"
                            color="indigo">
                            Enregistrer les modifications
                        </flux:button>
                    </div>
                </x-slot:footer>
            @endif
        </x-card>

        <x-card header="Histoire personnelle" minimize="mount"
            class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            @if ($this->isIncompleteHistoirePersonnelle())
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    Données personnelles incomplètes : compléter les informations de grossesse et allaitement.
                </div>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <x-input label="Age gestationnel (sem/mois)" wire:model="age_gestationnel"
                    placeholder="Entrez l'âge gestationnel" :readonly="!$update_histoire_personnelle" />
                <x-select.styled :readonly="!$update_histoire_personnelle" label="Allaitement maternel" wire:model="allaitement_maternel"
                    placeholder="Choisir..." required :options="[['label' => 'OUI', 'value' => 1], ['label' => 'NON', 'value' => 0]]" />
                <x-select.styled :readonly="!$update_histoire_personnelle" label="Médicaments traditionnels" wire:model="med_traditionnel"
                    placeholder="Choisir..." required :options="[['label' => 'OUI', 'value' => 1], ['label' => 'NON', 'value' => 0]]" />
                <x-select.styled :readonly="!$update_histoire_personnelle" label="Moringa Oleifera" wire:model="moringa_oleifera"
                    placeholder="Choisir..." required :options="[['label' => 'OUI', 'value' => 1], ['label' => 'NON', 'value' => 0]]" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 mb-6 gap-4">
                <x-input label="Indications" wire:model="indications" placeholder="Indications médicales"
                    :readonly="!$update_histoire_personnelle" />
                <x-input label="Durée" wire:model="duree_prise" placeholder="Durée de la prise"
                    :readonly="!$update_histoire_personnelle" />
            </div>
            <div class="flex mb-6">
                <div class="flex-1">
                    <x-tag prefix="@" label="Vaccins" wire:model="vaccins" :readonly="!$update_histoire_personnelle"
                        hint="Appuyer sur la touche Entrée ou la virgule pour ajouter un vaccin" />
                </div>
            </div>
            <x-textarea wire:model="histoire_perso_supplementaire" label="Infos supplémentaires"
                placeholder="Note ici..." maxlength="500" count :readonly="!$update_histoire_personnelle" />

            <div
                class="mt-6 rounded-2xl border border-sky-200 bg-sky-50/80 p-4 dark:border-sky-500/20 dark:bg-sky-500/10">
                <label class="flex items-start gap-3">
                    <x-toggle wire:model.live="update_histoire_personnelle" wire:loading.attr="disabled" />
                    <div class="flex justify-between w-full">
                        <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">Coché pour modifier les données
                        </p>
                        <flux:icon.loading wire:loading wire:target="update_histoire_personnelle" />
                    </div>
                </label>
            </div>

            @if ($update_histoire_personnelle)
                <x-slot:footer>
                    <div class="flex justify-end">
                        <flux:button variant="primary" icon="save" wire:click="updateHistoirePersonnelle"
                            color="indigo">
                            Enregistrer les modifications
                        </flux:button>
                    </div>
                </x-slot:footer>
            @endif
        </x-card>

        <x-card header="Histoire de la maladie (Premiers Signes)" minimize="mount"
            class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            @if ($this->isIncompleteHistoireMaladie())
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    Informations de maladie incomplètes : compléter les premiers signes et transfusions.
                </div>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <x-input label="Syndrome mains-pieds (âge)" wire:model="syndrome_mains_pieds"
                    placeholder="Entrez l'âge" :readonly="!$update_histoire_maladie" />
                <x-input label="Fievre / infection (âge)" wire:model="fievre" placeholder="Entrez l'âge"
                    :readonly="!$update_histoire_maladie" />
                <x-input label="Itère (âge)" wire:model="itere" placeholder="Entrez l'âge" :readonly="!$update_histoire_maladie" />
                <x-input label="CVO" wire:model="cvo" placeholder="Entrez l'âge" :readonly="!$update_histoire_maladie" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <x-input label="Première transfusion (âge)" wire:model="transfusion" placeholder="Entrez l'âge"
                    :readonly="!$update_histoire_maladie" />
                <x-number label="Nbre total de transfusions (âge)" wire:model="nbr_transfusion"
                    placeholder="Nombre total de transfusions" :readonly="!$update_histoire_maladie" />
                <x-input label="Episodes d'épistaxis (âge)" wire:model="episodes_epistaxis"
                    placeholder="Entrez l'âge" :readonly="!$update_histoire_maladie" />
                <x-number label="Nbre de CVO/an" wire:model="nbr_cvo_an" placeholder="Nombre de CVO par an"
                    :readonly="!$update_histoire_maladie" />
            </div>
            <x-textarea wire:model="premier_signes_supplementaires" label="Infos supplémentaires"
                placeholder="Note ici..." maxlength="500" count :readonly="!$update_histoire_maladie" />
            <div
                class="mt-6 rounded-2xl border border-sky-200 bg-sky-50/80 p-4 dark:border-sky-500/20 dark:bg-sky-500/10">
                <label class="flex items-start gap-3">
                    <x-toggle wire:model.live="update_histoire_maladie" wire:loading.attr="disabled" />
                    <div class="flex justify-between w-full">
                        <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">Coché pour modifier les données
                        </p>
                        <flux:icon.loading wire:loading wire:target="update_histoire_maladie" />
                    </div>
                </label>
            </div>

            @if ($update_histoire_maladie)
                <x-slot:footer>
                    <div class="flex justify-end">
                        <flux:button variant="primary" icon="save" wire:click="updateHistoireMaladie"
                            color="indigo">
                            Enregistrer les modifications
                        </flux:button>
                    </div>
                </x-slot:footer>
            @endif
        </x-card>

        <x-card header="Allergies connues" minimize="mount"
            class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <x-slot:footer>
                <div class="flex justify-end">
                    <flux:button variant="primary" icon="save" wire:click="updateHistoireMaladie" color="indigo">
                        Ajouter un allergie
                    </flux:button>
                </div>
            </x-slot:footer>
        </x-card>

        <x-card header="Autres antecedents" minimize="mount"
            class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            @if ($this->isIncompleteAutresAntecedents())
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    Aucun antécédent renseigné : ajouter une ou plusieurs informations.
                </div>
            @endif
            <div class="mb-4">
                <x-textarea wire:model="antecedents_medicales" label="Antecedents medicale" placeholder="Note ici..."
                    maxlength="500" count :readonly="!$update_autres_antecedents" />
            </div>
            <div class="mb-4">
                <x-textarea wire:model="antecedents_chirurgicaux" label="Antecedents chirurgicaux"
                    placeholder="Note ici..." maxlength="500" count :readonly="!$update_autres_antecedents" />
            </div>
            <div class="mb-4">
                <x-textarea wire:model="antecedents_familiaux" label="Antecedents familiaux"
                    placeholder="Note ici..." maxlength="500" count :readonly="!$update_autres_antecedents" />
            </div>
            <div class="mb-4">
                <x-textarea wire:model="antecedents_obstetricaux" label="Antecedents obstetricaux"
                    placeholder="Note ici..." maxlength="500" count :readonly="!$update_autres_antecedents" />
            </div>
            <div class="mb-4">
                <x-textarea wire:model="antecedents_gynocola" label="Antecedents gynocola" placeholder="Note ici..."
                    maxlength="500" count :readonly="!$update_autres_antecedents" />
            </div>
            <div class="mb-4">
                <x-textarea wire:model="antecedents_neurologiques" label="Antecedents neurologiques"
                    placeholder="Note ici..." maxlength="500" count :readonly="!$update_autres_antecedents" />
            </div>
            <div class="mb-4">
                <x-textarea wire:model="antecedents_cardiovasculaires" label="Antecedents cardiovasculaires"
                    placeholder="Note ici..." maxlength="500" count :readonly="!$update_autres_antecedents" />
            </div>
            <div class="mb-4">
                <x-textarea wire:model="antecedents_digestifs" label="Antecedents digestifs"
                    placeholder="Note ici..." maxlength="500" count :readonly="!$update_autres_antecedents" />
            </div>
            <div class="mb-4">
                <x-textarea wire:model="antecedents_endocrinologiques" label="Antecedents endocrinologiques"
                    placeholder="Note ici..." maxlength="500" count :readonly="!$update_autres_antecedents" />
            </div>
            <div class="mb-4">
                <x-textarea wire:model="antecedents_hematologiques" label="Antecedents hematologiques"
                    placeholder="Note ici..." maxlength="500" count :readonly="!$update_autres_antecedents" />
            </div>
            <div class="mb-4">
                <x-textarea wire:model="antecedents_supplementaires" label="Antecedents supplementaires"
                    placeholder="Note ici..." maxlength="500" count :readonly="!$update_autres_antecedents" />
            </div>

            <div
                class="mt-6 rounded-2xl border border-sky-200 bg-sky-50/80 p-4 dark:border-sky-500/20 dark:bg-sky-500/10">
                <label class="flex items-start gap-3">
                    <x-toggle wire:model.live="update_autres_antecedents" wire:loading.attr="disabled" />
                    <div class="flex justify-between w-full">
                        <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">Coché pour modifier les données
                        </p>
                        <flux:icon.loading wire:loading wire:target="update_autres_antecedents" />
                    </div>
                </label>
            </div>

            @if ($update_autres_antecedents)
                <x-slot:footer>
                    <div class="flex justify-end">
                        <flux:button variant="primary" icon="save" wire:click="updateAutresAntecedents"
                            color="indigo">
                            Enregistrer les modifications
                        </flux:button>
                    </div>
                </x-slot:footer>
            @endif
        </x-card>

        <x-card header="Localisation actuelle" minimize="mount"
            class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            @if ($this->isIncompleteLocalisation())
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    Localisation incomplète : compléter adresse et éléments géographiques.
                </div>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <x-select.styled label="Province !" wire:model.live="province_id" :request="route('api.provinces')"
                    select="label:name|value:id" searchable required :readonly="!$update_localisation" />
                <x-select.styled label="Ville !" wire:model.live="ville_id" :request="['url' => route('api.villes'), 'params' => ['province' => $province_id]]"
                    select="label:name|value:id" required :readonly="!$update_localisation" />
                <x-select.styled label="Commune !" wire:model="commune_id" :request="['url' => route('api.communes'), 'params' => ['ville' => $ville_id]]"
                    select="label:name|value:id" required :readonly="!$update_localisation" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <x-input wire:model="quartier" label="Quartier !" :readonly="!$update_localisation" />
                <x-input wire:model="avenue" label="Avenue !" :readonly="!$update_localisation" />
                <x-input wire:model="num_habitation" label="N° Habitation !" :readonly="!$update_localisation" />
            </div>
            <x-textarea wire:model="adresses_supplementaires" label="autres adresses supplémentaires"
                placeholder="Note ici..." maxlength="500" count :readonly="!$update_localisation" />
            <div
                class="mt-6 rounded-2xl border border-sky-200 bg-sky-50/80 p-4 dark:border-sky-500/20 dark:bg-sky-500/10">
                <label class="flex items-start gap-3">
                    <x-toggle wire:model.live="update_localisation" wire:loading.attr="disabled" />
                    <div class="flex justify-between w-full">
                        <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">Coché pour modifier les données
                        </p>
                        <flux:icon.loading wire:loading wire:target="update_localisation" />
                    </div>
                </label>
            </div>

            @if ($update_localisation)
                <x-slot:footer>
                    <div class="flex justify-end">
                        <flux:button variant="primary" icon="save" wire:click="updateLocalisation"
                            color="indigo">
                            Enregistrer les modifications
                        </flux:button>
                    </div>
                </x-slot:footer>
            @endif
        </x-card>
    </div>
</div>
