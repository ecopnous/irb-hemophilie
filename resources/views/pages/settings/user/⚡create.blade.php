<?php

use App\Models\Configs\Departement;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;
use Livewire\Attributes\Layout;

new #[Title('Nouvel utilisateur'), Layout('layouts::app.other.support_tech')] class extends Component {
    use Interactions;

    public string $name = '';
    public string $prenom = '';
    public string $post_nom = '';
    public string $email = '';
    public string $phone = '';
    public string $date_naissance = '';
    public string $nationalite = 'Congo-Kinshasa';
    public string $genre = 'M';
    public string $role = 'user';
    public ?int $departement_id = null;
    public string $matricule = '';
    public string $grade = '';
    public string $cnom = '';
    public string $onic = '';
    public string $password = '';
    public string $password_confirmation = '';
    public $departements = [];

    public function mount(): void
    {
        abort_unless(current_hopital_id(), 403, 'Aucun hopital courant en session.');

        $this->departements = Departement::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'post_nom' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'date_naissance' => ['required', 'date'],
            'nationalite' => ['required', 'string', 'max:255'],
            'genre' => ['required', 'in:M,F'],
            'role' => ['required', 'string', 'max:255'],
            'departement_id' => ['nullable', 'integer', 'exists:departements,id'],
            'matricule' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'cnom' => ['nullable', 'string', 'max:255'],
            'onic' => ['nullable', 'string', 'max:255'],
            // 'password' => ['required', 'confirmed', Password::default()],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'prenom' => $validated['prenom'],
            'post_nom' => $validated['post_nom'] ?: null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'date_naissance' => $validated['date_naissance'],
            'nationalite' => $validated['nationalite'],
            'genre' => $validated['genre'],
            'role' => $validated['role'],
            'departement_id' => $validated['departement_id'],
            'hopital_id' => current_hopital_id(),
            'matricule' => $validated['matricule'] ?: null,
            'grade' => $validated['grade'] ?: null,
            'cnom' => $validated['cnom'] ?: null,
            'onic' => $validated['onic'] ?: null,
            // 'password' => Hash::make('P'),
        ]);

        $this->toast()->success('Utilisateur enregistre avec succes.')->send();
        $this->redirectRoute('settings.user.show', ['id' => $user->id], navigate: true);
    }
};
?>

<section class="w-full">
    <flux:heading class="sr-only">{{ __('Gestions des utilisateurs') }}</flux:heading>
    <x-header_default :title="__('Nouvel utilisateur')" subtitle="Ajouter un utilisateur a l'hopital actuel" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
        ['label' => 'Hopitaux', 'icon' => 'building-office'],
    ]" />

    <div class="rounded-md mb-4 border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700">
        Hopital d'affectation : <strong>{{ current_hopital_nom() }}</strong>
    </div>

    <x-card>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-3">
                <x-input wire:model="name" label="Nom *" clearable />
                <x-input wire:model="post_nom" label="Post-nom" clearable />
                <x-input wire:model="prenom" label="Prenom *" clearable />
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <x-input wire:model="email" label="Email *" clearable />
                <x-input wire:model="phone" label="Telephone" clearable />
                <x-input wire:model="date_naissance" type="date" label="Date de naissance *" />
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <x-input wire:model="nationalite" label="Nationalite *" clearable />
                <x-select.native wire:model="genre" label="Genre *" :options="[['label' => 'Masculin', 'value' => 'M'], ['label' => 'Feminin', 'value' => 'F']]" />
                <x-select.native wire:model.live="role" label="Rôle *" placeholders="Choisir..." :options="[
                    ['label' => 'Utilisateur', 'value' => 'user'],
                    ['label' => 'Support technique', 'value' => 'support_tech'],
                    ['label' => 'Administrateur', 'value' => 'admin'],
                    ['label' => 'Super Administrateur', 'value' => 'super_admin'],
                ]" />
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <x-select.styled wire:model.live="departement_id" label="Departement" :placeholders="[
                    'default' => 'Selectionner un departement',
                    'search' => 'Entrez le nom du departement',
                    'empty' => 'Aucun departement trouve',
                ]" lazy="10"
                    :options="$this->departements" select="label:name|value:id" searchable />
                <x-input wire:model="matricule" label="Matricule" clearable />
                <x-select.native wire:model.live="grade" label="Grade *" placeholders="Choisir..." :options="[
                    ['label' => 'Secretaire', 'value' => 'secretaire'],
                    ['label' => 'Administrateur', 'value' => 'administrateur'],
                    ['label' => 'Comptable', 'value' => 'comptable'],
                    ['label' => 'Infirmiere', 'value' => 'infirmiere'],
                    ['label' => 'Medecin', 'value' => 'medecin'],
                    ['label' => 'Laborantin', 'value' => 'laborantin'],
                    ['label' => 'Radiologue', 'value' => 'radiologue'],
                    ['label' => 'Pharmacien', 'value' => 'pharmacien'],
                    ['label' => 'Technicien', 'value' => 'technicien'],
                ]" />
                @if ($grade == 'medecin')
                    <x-input wire:model="cnom" label="CNOM" clearable />
                @elseif ($grade == 'infirmiere')
                    <x-input wire:model="onic" label="ONIC" clearable />
                @endif
            </div>

            <div class="pt-2">
                <flux:button type="submit" icon="save" variant="primary" color="blue">
                    Enregistrer l'utilisateur
                </flux:button>
            </div>
        </form>
    </x-card>
</section>
