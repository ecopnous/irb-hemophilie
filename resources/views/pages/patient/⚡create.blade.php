<?php
use Flux\Flux;
use TallStackUi\Traits\Interactions;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\DossierPatient;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;

new class extends Component {
    use Interactions, WithFileUploads;

    public int $currentStep = 1; // Suivre l'étape actuelle

    public $photo = null;
    public $nom, $postnom, $prenom, $genre, $email, $telephone, $ins;
    public $etat_civil = 'Célibataire';
    public $date_naissance;

    // --- Santé / Naissance ---
    public $poids_naissance;
    public $note;

    // --- Localisation ---
    public $province_id, $ville_id, $commune_id, $country_id, $user_id, $assurance_id, $categorisation_id;
    public $quartier, $avenue, $num_habitation;

    // --- Parents ---
    public $nom_pere, $nom_mere;
    public $province_pere, $tribut_pere, $profession_pere;
    public $province_mere, $tribut_mere, $profession_mere;

    // --- Fratrie & Famille ---
    public $type_famille = 'Monogame',
        $rang_fratrie = 1;
    public $nb_freres, $nb_soeurs;
    public $deces_freres, $deces_soeurs;

    public $tag_ids = [];

    // Définir les règles de validation par étape
    protected function rules()
    {
        if ($this->currentStep === 1) {
            return [
                'nom' => 'nullable|min:2',
                'postnom' => 'nullable|min:2',
                'prenom' => 'required|min:2',
                'etat_civil' => 'required|in:Célibataire,Marié,Divorsé,Veu(f)ve',
                'date_naissance' => 'nullable|date|before:today',
                'poids_naissance' => 'nullable|numeric|min:0.1|max:20',
                'genre' => 'required|in:M,F',
                'ins' => 'nullable|alpha_num|unique:dossier_patients,ins',
                'type_famille' => 'nullable|in:Monogame,Polygame,Recomposée,Adaptative,Orphelinat',
                'country_id' => 'nullable|exists:countries,id',
                'telephone' => 'nullable|numeric|digits_between:9,15',
                'email' => 'nullable|email',
            ];
        }

        if ($this->currentStep === 2) {
            return [
                'nom_pere' => 'nullable|min:2',
                'nom_mere' => 'nullable|min:2',
                'province_pere' => 'nullable|exists:provinces,id',
                'province_mere' => 'nullable|exists:provinces,id',
                'tribut_pere' => 'nullable|min:2',
                'tribut_mere' => 'nullable|min:2',
                'profession_pere' => 'nullable|min:2',
                'profession_mere' => 'nullable|min:2',
                'nb_freres' => 'nullable|integer|min:0|max:50',
                'nb_soeurs' => 'nullable|integer|min:0|max:50',
                'deces_freres' => 'nullable|integer|min:0|max:50',
                'deces_soeurs' => 'nullable|integer|min:0|max:50',
                'rang_fratrie' => [
                    'nullable',
                    'integer',
                    'min:1',
                    function ($attribute, $value, $fail) {
                        // Dans Livewire, on accède directement aux propriétés via $this
                        $nbFreres = (int) ($this->nb_freres ?? 0);
                        $nbSoeurs = (int) ($this->nb_soeurs ?? 0);
                        $limiteMax = $nbFreres + $nbSoeurs + 1;

                        if ($value > $limiteMax) {
                            $fail("Le rang ($value) ne peut pas dépasser le nombre total d'enfants ($limiteMax).");
                        }
                    },
                ],
            ];
        }

        if ($this->currentStep === 3) {
            return [
                'province_id' => 'nullable|exists:provinces,id',
                'ville_id' => 'nullable|exists:villes,id',
                'commune_id' => 'nullable|exists:communes,id',
                'quartier' => 'nullable|min:2',
                'avenue' => 'nullable|min:2',
                'num_habitation' => 'nullable|min:1',
            ];
        }

        return [
            'nom' => 'nullable|min:2',
            'postnom' => 'nullable|min:2',
            'prenom' => 'required|min:2',
            'etat_civil' => 'required|in:Célibataire,Marié,Divorsé,Veu(f)ve',
            'date_naissance' => 'nullable|date|before:today',
            'poids_naissance' => 'nullable|numeric|min:0.1|max:20',
            'genre' => 'required|in:M,F',
            'ins' => 'nullable|alpha_num|unique:dossier_patients,ins',
            'type_famille' => 'nullable|in:Monogame,Polygame,Recomposée,Adaptative,Orphelinat',
            'country_id' => 'nullable|exists:countries,id',
            'telephone' => 'nullable|numeric|digits_between:9,15',
            'email' => 'nullable|email',
            'nom_pere' => 'nullable|min:2',
            'nom_mere' => 'nullable|min:2',
            'province_pere' => 'nullable|exists:provinces,id',
            'province_mere' => 'nullable|exists:provinces,id',
            'tribut_pere' => 'nullable|min:2',
            'tribut_mere' => 'nullable|min:2',
            'profession_pere' => 'nullable|min:2',
            'profession_mere' => 'nullable|min:2',
            'nb_freres' => 'nullable|integer|min:0|max:50',
            'nb_soeurs' => 'nullable|integer|min:0|max:50',
            'deces_freres' => 'nullable|integer|min:0|max:50',
            'deces_soeurs' => 'nullable|integer|min:0|max:50',
            'rang_fratrie' => [
                'nullable',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    // Dans Livewire, on accède directement aux propriétés via $this
                    $nbFreres = (int) ($this->nb_freres ?? 0);
                    $nbSoeurs = (int) ($this->nb_soeurs ?? 0);
                    $limiteMax = $nbFreres + $nbSoeurs + 1;

                    if ($value > $limiteMax) {
                        $fail("Le rang ($value) ne peut pas dépasser le nombre total d'enfants ($limiteMax).");
                    }
                },
            ],
            'province_id' => 'nullable|exists:provinces,id',
            'ville_id' => 'nullable|exists:villes,id',
            'commune_id' => 'nullable|exists:communes,id',
            'quartier' => 'nullable|min:2',
            'avenue' => 'nullable|min:2',
            'num_habitation' => 'nullable|min:1',
            'photo' => 'nullable|image|max:2048', // Max 2MB
            'categorisation_id' => 'nullable|exists:categorisations,id',
            'assurance_id' => 'nullable|exists:assurances,id',
            'tag_ids' => 'nullable|array|min:0',
            'tag_ids.*' => 'exists:tags,id',
            'note' => 'nullable|string|max:500',
        ];
    }

    public function nextStep()
    {
        try {
            $this->validate();
            $this->currentStep++;
            Flux::toast(variant: 'success', heading: 'Validation réussie', text: 'Données de l\'étape ' . ($this->currentStep - 1) . ' validées avec succès!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Afficher le toast d'erreur
            Flux::toast(variant: 'error', heading: 'Validation échouée', text: 'Une erreur est survenue : ' . $e->getMessage());
            throw $e;
        }
    }

    public function previousStep()
    {
        $this->currentStep--;
    }

    public function updatedProvinceId($value): void
    {
        $this->reset(['ville_id', 'commune_id']);
    }

    public function updatedVilleId($value): void
    {
        $this->reset('commune_id');
    }

    public function save()
    {
        try {
            $this->validate();
            $this->dialog()->question('Enregistrement', 'Etes-vous sûr de vouloir enregistrer ce dossier ?')->confirm('Confirm', 'confirmed', 'Le dossier du patient a été validé avec succès!')->cancel('Cancel')->send();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Flux::toast(variant: 'error', heading: 'Validation échouée', text: 'Une erreur est survenue : ' . $e->getMessage());
            throw $e;
        }
    }

    public function confirmed(string $message): void
    {
        try {
            $dossier_patient_id = DB::transaction(function () {
                $photoPath = $this->photo ? $this->photo->storePublicly('patient', 'public') : null;

                $dossierPatient = DossierPatient::create([
                    // Identité
                    'photo' => $photoPath,
                    'nom' => $this->nom,
                    'postnom' => $this->postnom,
                    'prenom' => $this->prenom,
                    'email' => $this->email,
                    'telephone' => $this->telephone,
                    'ins' => $this->ins,
                    'genre' => $this->genre,
                    'etat_civil' => $this->etat_civil,
                    'date_naissance' => $this->date_naissance,

                    // Naissance
                    'poids_naissance' => $this->poids_naissance,
                    'note' => $this->note,

                    // Localisation
                    'province_id' => $this->province_id,
                    'ville_id' => $this->ville_id,
                    'commune_id' => $this->commune_id,
                    'country_id' => $this->country_id,
                    'quartier' => $this->quartier,
                    'avenue' => $this->avenue,
                    'num_habitation' => $this->num_habitation,

                    // Parents
                    'nom_pere' => $this->nom_pere,
                    'nom_mere' => $this->nom_mere,
                    'province_pere' => $this->province_pere,
                    'tribut_pere' => $this->tribut_pere,
                    'profession_pere' => $this->profession_pere,
                    'province_mere' => $this->province_mere,
                    'tribut_mere' => $this->tribut_mere,
                    'profession_mere' => $this->profession_mere,

                    // Famille
                    'type_famille' => $this->type_famille,
                    'rang_fratrie' => $this->rang_fratrie,
                    'nb_freres' => $this->nb_freres,
                    'nb_soeurs' => $this->nb_soeurs,
                    'deces_freres' => $this->deces_freres,
                    'deces_soeurs' => $this->deces_soeurs,

                    // utilisateur
                    'user_id' => $this->user_id,
                    'assurance_id' => $this->assurance_id,
                    'categorisation_id' => $this->categorisation_id,
                    'hopital_id' => current_hopital_id(),
                ]);

                // Synchronisation des tags (Table Pivot)
                $dossierPatient->tags()->sync($this->tag_ids);

                return $dossierPatient->id;
            });

            // $this->dialog()->success('Success', $message)->send();
            Flux::toast(variant: 'success', heading: 'Validation réussie', text: $message);
            $this->redirectRoute('patient.show', ['id' => $dossier_patient_id], navigate: true);
        } catch (\Exception $e) {
            Flux::toast(variant: 'error', heading: 'Erreur lors de l\'enregistrement', text: 'Une erreur est survenue : ' . $e->getMessage());
        }
    }

    // public function cancelled(string $message): void
    // {
    //     $this->dialog()->error('Cancelled', $message)->send();
    // }

    public function deleteUpload(array $content): void
    {
        if (!$this->photo) {
            return;
        }

        $files = Arr::wrap($this->photo);

        /** @var UploadedFile $file */
        $file = collect($files)->filter(fn(UploadedFile $item) => $item->getFilename() === $content['temporary_name'])->first();

        // 1. Here we delete the file. Even if we have a error here, we simply
        // ignore it because as long as the file is not persisted, it is
        // temporary and will be deleted at some point if there is a failure here.
        rescue(fn() => $file->delete(), report: false);

        $collect = collect($files)->filter(fn(UploadedFile $item) => $item->getFilename() !== $content['temporary_name']);

        // 2. We guarantee restore of remaining files regardless of upload
        // type, whether you are dealing with multiple or single uploads
        $this->photo = is_array($this->photo) ? $collect->toArray() : $collect->first();
    }
};
?>

<div>
    <div class="mb-8">
        <x-breadcrumbs :items="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Dossiers patients', 'link' => 'patient.index', 'icon' => 'folder'],
            ['label' => 'Nouveau dossier', 'icon' => 'folder-open'],
        ]" />
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mt-2">
            Dossiers Médicaux
        </h1>
    </div>
    <form wire:submit.prevent="save" class="space-y-4">
        <x-step wire:model="currentStep" panels>
            <x-step.items step="1" title="Identités" description="Etape 1" defer>
                <x-card loading>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <x-input label="Nom" wire:model="nom" placeholder="Entrez le nom du patient" />
                        <x-input label="Post-nom" wire:model="postnom" placeholder="Entrez le post-nom du patient" />
                        <x-input label="Prénom" wire:model="prenom" placeholder="Entrez le prénom du patient" />
                        <x-select.styled label="Etat civil" wire:model="etat_civil" placeholder="Sélectionnez" required
                            :options="[
                                ['label' => 'Célibataire', 'value' => 'Célibataire'],
                                ['label' => 'Marié', 'value' => 'Marié'],
                                ['label' => 'Divorsé', 'value' => 'Divorsé'],
                                ['label' => 'Veu(f)ve', 'value' => 'Veu(f)ve'],
                            ]" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <x-date wire:model="date_naissance" label="Date de naissance "
                            placeholder="Date de naissance" />
                        <x-number wire:model="poids_naissance" label="Poids de naissance (Kg)" min="1"
                            max="20" step="0.1" chevron />
                        <x-select.styled label="Genre" wire:model="genre" required :options="[['label' => 'Homme', 'value' => 'M'], ['label' => 'Femme', 'value' => 'F']]" />
                        <x-input label="N° Identité santé" wire:model="ins" placeholder="N° identité" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <x-select.styled wire:model="type_famille" label="Type de famille" required :options="['Monogame', 'Polygame', 'Recomposée', 'Adaptative', 'Orphelinat']" />
                        <x-select.styled wire:model="country_id" label="Pays de naissance" :request="route('api.countries')"
                            select="label:name|value:id" required />
                        <x-input.select wire:model="telephone" label="Téléphone">
                            <x-slot:left>
                                <x-select.native :options="['+243', '+242', '+241']" required />
                            </x-slot:left>
                        </x-input.select>
                        <x-input wire:model="email" label="E-mail" placeholder="email@exemple.com" />
                    </div>
                </x-card>
                <div class="flex justify-end mt-4">
                    <flux:button variant="primary" icon:trailing="chevron-double-right" wire:click="nextStep"
                        class="w-full sm:w-auto">Etape suivante</flux:button>
                </div>
            </x-step.items>

            <x-step.items step="2" title="Famille" description="Etape 2" lazy>
                <x-card loading>
                    <div
                        class="flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="flex-1 pb-6 lg:pb-0 lg:pr-4">
                            <flux:subheading size="lg" class="mb-4 px-2">{{ __('Père') }}</flux:subheading>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-input label="Nom du père " wire:model="nom_pere" />
                                <x-input label="Profession" wire:model="profession_pere" />
                                <x-select.styled label="Province d'origine " wire:model="province_pere"
                                    :request="route('api.provinces')" select="label:name|value:id" />
                                <x-input label="Tribu" wire:model="tribut_pere" />
                            </div>
                        </div>

                        <div class="flex-1 pt-6 lg:pt-0 lg:pl-4">
                            <flux:subheading size="lg" class="mb-4 px-2">{{ __('Mère') }}</flux:subheading>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-input label="Nom de la mère " wire:model="nom_mere" />
                                <x-input label="Profession" wire:model="profession_mere" />
                                <x-select.styled label="Province d'origine " wire:model="province_mere"
                                    :request="route('api.provinces')" select="label:name|value:id" />
                                <x-input label="Tribu" wire:model="tribut_mere" />
                            </div>
                        </div>
                    </div>

                    <div class="my-6">
                        <flux:separator variant="subtle" />
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <x-number label="Rang Fratrie" wire:model="rang_fratrie" />
                        <x-number label="Frères vivants" wire:model="nb_freres" />
                        <x-number label="Soeurs vivantes" wire:model="nb_soeurs" />
                        <x-number label="Frères décédés" wire:model="deces_freres" />
                        <x-number label="Soeurs décédées" wire:model="deces_soeurs" />
                    </div>
                </x-card>
                <div class="flex flex-col sm:flex-row justify-between gap-3 mt-4">
                    <flux:button variant="ghost" icon="chevron-double-left" wire:click="previousStep"
                        class="w-full sm:w-auto">Précédent</flux:button>
                    <flux:button variant="primary" icon:trailing="chevron-double-right" wire:click="nextStep"
                        class="w-full sm:w-auto">Suivant</flux:button>
                </div>
            </x-step.items>

            <x-step.items step="3" title="Adresse" description="Etape 3" lazy>
                <x-card loading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <x-select.styled label="Province " wire:model.live="province_id" :request="route('api.provinces')"
                            select="label:name|value:id" searchable />
                        <x-select.styled label="Ville " wire:model.live="ville_id" :request="['url' => route('api.villes'), 'params' => ['province' => $province_id]]"
                            select="label:name|value:id" :disabled="!$province_id" />
                        <x-select.styled label="Commune " wire:model="commune_id" :request="['url' => route('api.communes'), 'params' => ['ville' => $ville_id]]"
                            select="label:name|value:id" :disabled="!$ville_id" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input wire:model="quartier" label="Quartier " />
                        <x-input wire:model="avenue" label="Avenue " />
                        <x-input wire:model="num_habitation" label="N° Habitation " />
                    </div>
                </x-card>
                <div class="flex flex-col sm:flex-row justify-between gap-3 mt-4">
                    <flux:button variant="ghost" icon="chevron-double-left" wire:click="previousStep"
                        class="w-full sm:w-auto">Précédent</flux:button>
                    <flux:button variant="primary" icon:trailing="chevron-double-right" wire:click="nextStep"
                        class="w-full sm:w-auto">Suivant</flux:button>
                </div>
            </x-step.items>

            <x-step.items step="4" title="Autres" description="Etape Finale" lazy>
                <x-card loading>
                    <div class="mb-4">
                        <x-upload label="Photo du patient" accept="image/*" wire:model="photo" delete />
                    </div>
                    <div class="mb-4">
                        <x-select.styled label="Tags" wire:model="tag_ids" :request="route('api.tags')"
                            select="label:name|value:id" multiple hint="Sélectionnez au moins un tag" />
                    </div>

                    <div class="mt-4">
                        <x-textarea wire:model="note" label="Infos supplémentaires" placeholder="Note ici..."
                            maxlength="500" count />
                    </div>
                </x-card>
                <div class="flex flex-col sm:flex-row justify-between gap-3 mt-6">
                    <flux:button variant="ghost" icon="chevron-double-left" wire:click="previousStep"
                        class="w-full sm:w-auto">Précédent</flux:button>
                    <flux:button variant="primary" type="submit" icon="folder-plus" class="w-full sm:w-auto">
                        Sauvergarder</flux:button>
                </div>
            </x-step.items>
        </x-step>
    </form>

</div>
