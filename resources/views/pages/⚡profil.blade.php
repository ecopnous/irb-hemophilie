<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mon profil'), Layout('layouts::app')] class extends Component {
    public bool $editing = false;

    public string $name = '';
    public string $prenom = '';
    public string $post_nom = '';
    public string $email = '';
    public ?string $phone = null;
    public ?string $date_naissance = null;
    public ?string $nationalite = null;
    public string $genre = 'M';

    public function mount(): void
    {
        $this->syncFromUser();
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user()->loadMissing(['hopital', 'departement']);
    }

    public function syncFromUser(): void
    {
        $user = $this->user;

        $this->name = (string) $user->name;
        $this->prenom = (string) ($user->prenom ?? '');
        $this->post_nom = (string) ($user->post_nom ?? '');
        $this->email = (string) $user->email;
        $this->phone = $user->phone;
        $this->date_naissance = $user->date_naissance;
        $this->nationalite = $user->nationalite;
        $this->genre = (string) ($user->genre ?? 'M');
    }

    public function isOnline(): bool
    {
        return $this->user->last_seen_at?->greaterThanOrEqualTo(now()->subMinutes(6)) ?? false;
    }

    public function displayGenre(?string $genre): string
    {
        return match ($genre) {
            'M' => 'Homme',
            'F' => 'Femme',
            default => '—',
        };
    }

    public function startEditing(): void
    {
        $this->syncFromUser();
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->resetValidation();
        $this->syncFromUser();
        $this->editing = false;
    }

    public function saveProfile(): void
    {
        $user = $this->user;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'post_nom' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'nationalite' => ['nullable', 'string', 'max:255'],
            'genre' => ['required', 'in:M,F'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'prenom' => $validated['prenom'],
            'post_nom' => $validated['post_nom'] ?: null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'date_naissance' => $validated['date_naissance'] ?: null,
            'nationalite' => $validated['nationalite'] ?: null,
            'genre' => $validated['genre'],
        ];

        if ($user->email !== $validated['email'] && $user instanceof MustVerifyEmail) {
            $payload['email_verified_at'] = null;
        }

        $user->update($payload);

        $this->editing = false;
        $this->dispatch('profil-saved');
        Flux::toast(variant: 'success', heading: 'Profil mis à jour', text: 'Vos informations personnelles ont été enregistrées.');
    }
};
?>

<div class="mx-auto max-w-7xl space-y-6">
    <x-breadcrumbs :items="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Mon profil', 'icon' => 'user-circle'],
    ]" />

    <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="bg-linear-to-r from-indigo-600 via-violet-600 to-sky-500 px-6 py-8 sm:px-8">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="flex items-center gap-5">
                    <div
                        class="flex size-20 items-center justify-center rounded-[1.5rem] border-4 border-white/30 bg-white/20 text-2xl font-black text-white shadow-lg backdrop-blur-sm">
                        {{ $this->user->initials() }}
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-white/70">Profil utilisateur
                        </p>
                        <h1 class="mt-1 text-2xl font-black text-white sm:text-3xl">
                            {{ trim(collect([$this->user->name, $this->user->post_nom, $this->user->prenom])->filter()->implode(' ')) }}
                        </h1>
                        <p class="mt-1 text-sm text-white/85">
                            {{ ucfirst($this->user->grade ?: 'Personnel') }}
                            @if ($this->user->departement)
                                · {{ ucfirst($this->user->departement->name) }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span @class([
                        'rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide',
                        'bg-emerald-400/20 text-emerald-100' => $this->isOnline(),
                        'bg-white/15 text-white/80' => ! $this->isOnline(),
                    ])>
                        {{ $this->isOnline() ? 'En ligne' : 'Hors ligne' }}
                    </span>
                    <flux:button size="sm" variant="filled" class="!bg-white/15 !text-white hover:!bg-white/25"
                        icon="pencil-square" wire:click="startEditing" x-on:click="$tsui.open.modal('profil-edit-modal')">
                        Modifier
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-100 sm:grid-cols-2 lg:grid-cols-4 dark:bg-slate-800">
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Hôpital</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                    {{ $this->user->hopital?->name ?? current_hopital_nom() }}</p>
            </div>
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Département</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                    {{ $this->user->departement?->name ?? '—' }}</p>
            </div>
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Rôle</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ ucfirst($this->user->role) }}</p>
            </div>
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Dernière activité</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                    {{ $this->user->last_seen_at?->diffForHumans() ?? '—' }}</p>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:p-6">
            <h2 class="text-base font-black text-slate-900 dark:text-white">Identité</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-patient.fiche-field label="Nom" :value="ucfirst($this->user->name)" />
                <x-patient.fiche-field label="Post-nom" :value="$this->user->post_nom ?: '—'" />
                <x-patient.fiche-field label="Prénom" :value="ucfirst($this->user->prenom ?: '—')" />
                <x-patient.fiche-field label="Genre" :value="$this->displayGenre($this->user->genre)" />
                <x-patient.fiche-field label="Date de naissance" :value="$this->user->date_naissance ?: '—'" />
                <x-patient.fiche-field label="Nationalité" :value="$this->user->nationalite ?: '—'" />
            </div>
        </section>

        <section
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:p-6">
            <h2 class="text-base font-black text-slate-900 dark:text-white">Contact & compte</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-patient.fiche-field label="E-mail" :value="$this->user->email" />
                <x-patient.fiche-field label="Téléphone" :value="$this->user->phone ?: '—'" />
                <x-patient.fiche-field label="Matricule" :value="$this->user->matricule ?: '—'" />
                <x-patient.fiche-field label="Grade" :value="ucfirst($this->user->grade ?: '—')" />
            </div>
            <div class="mt-6 flex flex-wrap gap-2">
                <flux:button href="{{ route('settings.index') }}" variant="ghost" size="sm" icon="cog-6-tooth" wire:navigate>
                    Parametres
                </flux:button>
                <flux:button href="{{ route('security.edit') }}" variant="ghost" size="sm" icon="shield-check"
                    wire:navigate>
                    Sécurité
                </flux:button>
            </div>
        </section>

        <section
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:p-6 lg:col-span-2">
            <h2 class="text-base font-black text-slate-900 dark:text-white">Références professionnelles</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <x-patient.fiche-field label="CNOM" :value="$this->user->cnom ?: '—'" />
                <x-patient.fiche-field label="ONIC" :value="$this->user->onic ?: '—'" />
                <x-patient.fiche-field label="Identifiant" :value="'#' . $this->user->id" />
                <x-patient.fiche-field label="E-mail vérifié"
                    :value="$this->user->email_verified_at ? 'Oui · ' . $this->user->email_verified_at->format('d/m/Y') : 'Non'" />
            </div>
            <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                L'affectation (hôpital, département, grade, matricule) est gérée par l'administration.
            </p>
        </section>
    </div>

    <x-modal id="profil-edit-modal" title="Modifier mon profil" size="4xl" center persistent
        x-on:profil-saved.window="$tsui.close.modal('profil-edit-modal')">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-input label="Nom *" wire:model="name" />
            <x-input label="Post-nom" wire:model="post_nom" />
            <x-input label="Prénom *" wire:model="prenom" />
            <x-select.styled label="Genre *" wire:model="genre" :options="[['label' => 'Homme', 'value' => 'M'], ['label' => 'Femme', 'value' => 'F']]" />
            <x-date wire:model="date_naissance" label="Date de naissance" />
            <x-input label="Nationalité" wire:model="nationalite" />
            <x-input label="E-mail *" wire:model="email" type="email" />
            <x-input label="Téléphone" wire:model="phone" />
        </div>

        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelEditing"
                    x-on:click="$tsui.close.modal('profil-edit-modal')">
                    Annuler
                </flux:button>
                <flux:button variant="primary" color="indigo" wire:click="saveProfile">
                    Enregistrer
                </flux:button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
