<?php

use App\Models\Configs\Hopital;
use App\Models\Localisations\Commune;
use App\Models\Localisations\Province;
use App\Models\Localisations\Ville;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Title('Nouvel hopital'), Layout('layouts::app.other.support_tech')] class extends Component {
    use Interactions;

    public string $reference = '';
    public string $name = '';
    public string $code_postal = '';
    public string $type = 'public';
    public string $devise = 'cdf';
    public int $statut = 1;
    public string $numero_licence = '';
    public string $autorite_regulation = '';
    public string $site_web = '';
    public string $quartier = '';
    public string $avenue = '';
    public string $numero = '';
    public string $description = '';

    public ?int $country_id = 52;
    public ?int $province_id = null;
    public ?int $ville_id = null;
    public ?int $commune_id = null;

    public $provinces;
    public $villes = [];
    public $communes = [];

    public function mount(): void
    {
        $this->provinces = Province::query()->orderBy('name')->get();

        $last = Hopital::query()->latest('id')->first();
        $number = $last ? $last->id + 1 : 1;

        $this->reference = 'H-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    public function updatedProvinceId($value): void
    {
        if (!$value) {
            $this->villes = [];
            $this->communes = [];
            $this->reset(['ville_id', 'commune_id']);

            return;
        }

        $this->villes = Ville::query()->where('province_id', $value)->orderBy('name')->get();

        $this->communes = [];
        $this->reset(['ville_id', 'commune_id']);
    }

    public function updatedVilleId($value): void
    {
        if (!$value) {
            $this->communes = [];
            $this->reset('commune_id');

            return;
        }

        $this->communes = Commune::query()->where('ville_id', $value)->orderBy('name')->get();

        $this->reset('commune_id');
    }

    #[Computed]
    public function completionStats(): array
    {
        $requiredFields = [$this->reference, $this->name, $this->code_postal, $this->type, $this->devise, $this->province_id, $this->ville_id, $this->commune_id, $this->quartier, $this->avenue, $this->numero];

        return [
            'filled' => collect($requiredFields)->filter(fn($value) => filled($value))->count(),
            'total' => count($requiredFields),
        ];
    }

    #[Computed]
    public function selectedProvinceName(): string
    {
        return (string) (collect($this->provinces)->firstWhere('id', $this->province_id)?->name ?? 'Non definie');
    }

    #[Computed]
    public function selectedVilleName(): string
    {
        return (string) (collect($this->villes)->firstWhere('id', $this->ville_id)?->name ?? 'Non definie');
    }

    #[Computed]
    public function selectedCommuneName(): string
    {
        return (string) (collect($this->communes)->firstWhere('id', $this->commune_id)?->name ?? 'Non definie');
    }

    #[Computed]
    public function statusLabel(): string
    {
        return $this->statut ? 'Actif' : 'Inactif';
    }

    #[Computed]
    public function typeLabel(): string
    {
        return match ($this->type) {
            'prive' => 'Prive',
            'clinique' => 'Clinique',
            default => 'Public',
        };
    }

    #[Computed]
    public function currencyLabel(): string
    {
        return strtoupper($this->devise);
    }

    #[Computed]
    public function locationSummary(): string
    {
        $parts = array_filter([$this->selectedProvinceName, $this->selectedVilleName !== 'Non definie' ? $this->selectedVilleName : null, $this->selectedCommuneName !== 'Non definie' ? $this->selectedCommuneName : null]);

        return $parts === [] ? 'Aucune localisation selectionnee' : implode(' / ', $parts);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'reference' => ['required', 'string', 'max:255', 'unique:hopitals,reference'],
            'name' => ['required', 'string', 'max:255'],
            'code_postal' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:public,prive,clinique'],
            'devise' => ['required', 'in:cdf,usd,eur'],
            'statut' => ['required', 'boolean'],
            'numero_licence' => ['nullable', 'string', 'max:255'],
            'autorite_regulation' => ['nullable', 'string', 'max:255'],
            'site_web' => ['nullable', 'url:http,https', 'max:255'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'ville_id' => ['required', 'integer', 'exists:villes,id'],
            'commune_id' => ['required', 'integer', 'exists:communes,id'],
            'quartier' => ['required', 'string', 'max:255'],
            'avenue' => ['required', 'string', 'max:255'],
            'numero' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Hopital::query()->create([
            'reference' => $validated['reference'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'devise' => $validated['devise'],
            'code_postal' => $validated['code_postal'],
            'is_actif' => (bool) $validated['statut'],
            'numero_licence' => $validated['numero_licence'] ?: null,
            'autorite_regulation' => $validated['autorite_regulation'] ?: null,
            'site_web' => $validated['site_web'] ?: null,
            'country_id' => $validated['country_id'],
            'province_id' => $validated['province_id'],
            'ville_id' => $validated['ville_id'],
            'commune_id' => $validated['commune_id'],
            'quartier' => $validated['quartier'],
            'avenue' => $validated['avenue'],
            'numero' => $validated['numero'],
            'description' => $validated['description'] ?: null,
        ]);

        $this->toast()->success('Hopital enregistre avec succes.')->send();
        $this->redirectRoute('settings.hopital.index', navigate: true);
    }
};
?>

<section class="w-full space-y-6">
    <x-header_default :title="__('Nouvel Hopital')" :subtitle="__(
        'Renseignez les informations ci-dessous pour enregistrer un hopital de maniere propre, rapide et lisible.',
    )" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Support technique', 'icon' => 'cog-6-tooth'],
        ['label' => 'Hopitaux', 'link' => 'settings.hopital.index', 'icon' => 'building-office'],
        ['label' => 'Nouvel hopital', 'icon' => 'plus'],
    ]">
        <x-slot:actions>
            <x-button icon="arrow-left" position="left" href="{{ route('settings.hopital.index') }}" wire:navigate>
                Retour
            </x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_22rem]">
        <div class="space-y-6">
            <x-card loading>
                <form wire:submit="save" class="space-y-8">
                    <section class="space-y-5">
                        <div class="space-y-2">
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">
                                Identite
                            </p>
                            <div>
                                <h2 class="text-xl font-black text-slate-900 dark:text-white">
                                    Informations principales
                                </h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Commencez par l identite de l hopital et les parametres de base utilises dans le
                                    systeme.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <x-input wire:model="reference" placeholder="Reference" label="Reference *" readonly />
                            <x-input wire:model="name" placeholder="Nom complet de l hopital" label="Nom de l hopital *"
                                clearable />
                            <x-input wire:model="code_postal" placeholder="Code postal" label="Code postal *"
                                clearable />
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <x-select.native wire:model.live="type" label="Type *" :options="[
                                ['label' => 'Public', 'value' => 'public'],
                                ['label' => 'Prive', 'value' => 'prive'],
                                ['label' => 'Clinique', 'value' => 'clinique'],
                            ]" />
                            <x-select.native wire:model.live="devise" label="Devise *" :options="[
                                ['label' => 'Franc congolais', 'value' => 'cdf'],
                                ['label' => 'Dollar americain', 'value' => 'usd'],
                                ['label' => 'Euro', 'value' => 'eur'],
                            ]" />
                            <x-select.native wire:model.live="statut" label="Etat de l hopital *" :options="[['label' => 'Actif', 'value' => 1], ['label' => 'Inactif', 'value' => 0]]" />
                        </div>
                    </section>

                    <section class="space-y-5">
                        <div class="space-y-2">
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">
                                Cadre legal
                            </p>
                            <div>
                                <h2 class="text-xl font-black text-slate-900 dark:text-white">
                                    Informations administratives
                                </h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Ajoutez les informations officielles qui servent au cadrage administratif et a la
                                    conformite.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <x-input wire:model="numero_licence" label="Numero de licence"
                                placeholder="Reference administrative" clearable />
                            <x-input wire:model="autorite_regulation" label="Autorite de regulation"
                                placeholder="Structure de supervision" clearable />
                            <x-input wire:model="site_web" placeholder="https://domaine.example" label="Site web"
                                clearable />
                        </div>
                    </section>

                    <section class="space-y-5">
                        <div class="space-y-2">
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">
                                Localisation
                            </p>
                            <div>
                                <h2 class="text-xl font-black text-slate-900 dark:text-white">
                                    Adresse et rattachement
                                </h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Selectionnez d abord la province, puis la ville et enfin la commune pour garder un
                                    parcours de saisie logique.
                                </p>
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <p>
                                    <span class="font-semibold text-slate-900 dark:text-white">Pays actif :</span>
                                    Republique Democratique du Congo
                                </p>
                                <p>
                                    <span class="font-semibold text-slate-900 dark:text-white">Parcours actuel :</span>
                                    {{ $this->locationSummary }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <x-select.styled wire:model.live="province_id" label="Province *" :placeholders="[
                                'default' => 'Selectionner la province',
                                'search' => 'Rechercher une province',
                                'empty' => 'Aucune province trouvee',
                            ]"
                                lazy="10" :options="$this->provinces" select="label:name|value:id" searchable />

                            <div class="space-y-2">
                                <x-select.styled wire:model.live="ville_id" wire:key="hopital-ville-{{ $province_id }}"
                                    label="Ville *" :placeholders="[
                                        'default' => 'Selectionner la ville',
                                        'search' => 'Rechercher une ville',
                                        'empty' => 'Aucune ville trouvee',
                                    ]" :disabled="blank($province_id) || count($villes) === 0" loading lazy="10"
                                    :options="$this->villes" select="label:name|value:id" searchable />
                                <p class="text-xs text-slate-500 dark:text-slate-400" wire:loading
                                    wire:target="province_id">
                                    Chargement des villes...
                                </p>
                            </div>

                            <div class="space-y-2">
                                <x-select.styled wire:model="commune_id" wire:key="hopital-commune-{{ $ville_id }}"
                                    label="Commune *" :placeholders="[
                                        'default' => 'Selectionner la commune',
                                        'search' => 'Rechercher une commune',
                                        'empty' => 'Aucune commune trouvee',
                                    ]" :disabled="blank($ville_id) || count($communes) === 0" lazy="10"
                                    :options="$this->communes" select="label:name|value:id" searchable />
                                <p class="text-xs text-slate-500 dark:text-slate-400" wire:loading
                                    wire:target="ville_id">
                                    Chargement des communes...
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" wire:key="hopital-address-text-fields">
                            <div wire:key="hopital-quartier-field">
                                <x-input wire:model="quartier" label="Quartier *" placeholder="Quartier" clearable />
                            </div>
                            <div wire:key="hopital-avenue-field">
                                <x-input wire:model="avenue" label="Avenue *" placeholder="Avenue" clearable />
                            </div>
                            <div wire:key="hopital-numero-field">
                                <x-input wire:model="numero" label="Numero d'habitation *" placeholder="Numero"
                                    clearable />
                            </div>
                        </div>
                    </section>

                    <section class="space-y-5">
                        <div class="space-y-2">
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">
                                Notes
                            </p>
                            <div>
                                <h2 class="text-xl font-black text-slate-900 dark:text-white">
                                    Description complementaire
                                </h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Ajoutez un court contexte si vous avez besoin de preciser le profil ou la mission
                                    de l hopital.
                                </p>
                            </div>
                        </div>

                        <x-textarea wire:model="description" label="Description" maxlength="500" count />
                    </section>

                    <div
                        class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Les champs obligatoires sont verifies a l enregistrement.
                        </p>

                        <div class="flex flex-wrap gap-3">
                            <x-button href="{{ route('settings.hopital.index') }}" wire:navigate outline>
                                Annuler
                            </x-button>
                            <flux:button type="submit" icon="save" variant="primary" color="blue"
                                wire:loading.attr="disabled" wire:target="save">
                                Enregistrer l hopital
                            </flux:button>
                        </div>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="space-y-6">
            <div class="xl:sticky xl:top-6 space-y-6">
                <div
                    class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">
                            Apercu
                        </p>
                        <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">
                            Resume de la fiche
                        </h3>
                    </div>

                    <div class="space-y-4 px-5 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Reference</p>
                            <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $reference }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                                Nom affiche
                            </p>
                            <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                                {{ $name ?: 'Nom non renseigne' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-900/70">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Type</p>
                                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                                    {{ $this->typeLabel }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-900/70">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Devise</p>
                                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                                    {{ $this->currencyLabel }}
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-900/70">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Statut</p>
                            <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                                {{ $this->statusLabel }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                                Localisation
                            </p>
                            <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">
                                {{ $this->locationSummary }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-blue-200 bg-blue-50/80 px-4 py-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">
                                Avancement
                            </p>
                            <p class="mt-2 text-2xl font-black text-blue-950 dark:text-blue-100">
                                {{ $this->completionStats['filled'] }}/{{ $this->completionStats['total'] }}
                            </p>
                            <p class="mt-1 text-xs text-blue-800/80 dark:text-blue-200/80">
                                Champs structurants renseignes
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">
                        Conseils
                    </p>
                    <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        <p>Renseignez d abord les informations principales pour stabiliser l identite de l hopital.</p>
                        <p>La ville et la commune se chargent automatiquement apres la province pour eviter les erreurs.
                        </p>
                        <p>Le formulaire reste lisible sur mobile comme sur grand ecran, avec un resume toujours
                            visible.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
