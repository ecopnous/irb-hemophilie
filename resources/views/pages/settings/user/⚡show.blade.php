<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fiche utilisateur'), Layout('layouts::app.other.support_tech')] class extends Component {
    public User $user;

    public function mount(int $id): void
    {
        $this->user = User::query()
            ->with(['hopital', 'departement'])
            ->where('hopital_id', current_hopital_id())
            ->findOrFail($id);
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

    public function displayRole(?string $role): string
    {
        return match ($role) {
            'super_admin' => 'Super administrateur',
            'admin' => 'Administrateur',
            'support_tech' => 'Support technique',
            'user' => 'Utilisateur',
            default => ucfirst((string) $role),
        };
    }

    public function fullName(): string
    {
        return trim(collect([$this->user->name, $this->user->post_nom, $this->user->prenom])->filter()->implode(' '));
    }
};
?>

<section class="max-w-7xl space-y-6" wire:poll.60s>
    <flux:heading class="sr-only">{{ __('Fiche utilisateur') }}</flux:heading>

    <x-header_default
        :title="$this->fullName()"
        :subtitle="'Fiche du corps médical · ' . ucfirst($user->grade ?: 'personnel')"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings.hopital.index', 'icon' => 'cog-6-tooth'],
            ['label' => 'Corps médical', 'link' => 'settings.user.index', 'icon' => 'users'],
            ['label' => $this->fullName(), 'icon' => 'user-circle'],
        ]"
    >
        <x-slot:actions>
            <flux:button href="{{ route('settings.user.index') }}" variant="ghost" icon="arrow-left" wire:navigate>
                Retour à la liste
            </flux:button>
        </x-slot:actions>
    </x-header_default>

    <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="bg-linear-to-r from-indigo-600 via-violet-600 to-sky-500 px-6 py-8 sm:px-8">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="flex items-center gap-5">
                    <div
                        class="flex size-20 items-center justify-center rounded-[1.5rem] border-4 border-white/30 bg-white/20 text-2xl font-black text-white shadow-lg backdrop-blur-sm">
                        {{ $user->initials() }}
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-white/70">Corps médical</p>
                        <h1 class="mt-1 text-2xl font-black text-white sm:text-3xl">
                            {{ $this->fullName() }}
                        </h1>
                        <p class="mt-1 text-sm text-white/85">
                            {{ ucfirst($user->grade ?: 'Personnel') }}
                            @if ($user->departement)
                                · {{ ucfirst($user->departement->name) }}
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
                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white/90">
                        {{ $this->displayRole($user->role) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-100 sm:grid-cols-2 lg:grid-cols-4 dark:bg-slate-800">
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Hôpital</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                    {{ $user->hopital?->name ?? current_hopital_nom() ?? '—' }}
                </p>
            </div>
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Département</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                    {{ $user->departement?->name ?? '—' }}
                </p>
            </div>
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Matricule</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                    {{ $user->matricule ?: '—' }}
                </p>
            </div>
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Dernière activité</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                    {{ $user->last_seen_at?->diffForHumans() ?? '—' }}
                </p>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:p-6">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                <div class="flex size-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                    <flux:icon.identification class="size-5" />
                </div>
                <h2 class="text-base font-black text-slate-900 dark:text-white">Identité</h2>
            </div>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-patient.fiche-field label="Nom" :value="ucfirst($user->name)" />
                <x-patient.fiche-field label="Post-nom" :value="$user->post_nom ?: '—'" />
                <x-patient.fiche-field label="Prénom" :value="ucfirst($user->prenom ?: '—')" />
                <x-patient.fiche-field label="Genre" :value="$this->displayGenre($user->genre)" />
                <x-patient.fiche-field label="Date de naissance" :value="$user->date_naissance ?: '—'" />
                <x-patient.fiche-field label="Nationalité" :value="$user->nationalite ?: '—'" />
            </div>
        </section>

        <section
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:p-6">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                <div class="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                    <flux:icon.envelope class="size-5" />
                </div>
                <h2 class="text-base font-black text-slate-900 dark:text-white">Contact & compte</h2>
            </div>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-patient.fiche-field label="E-mail" :value="$user->email" />
                <x-patient.fiche-field label="Téléphone" :value="$user->phone ?: '—'" />
                <x-patient.fiche-field label="Grade" :value="ucfirst($user->grade ?: '—')" />
                <x-patient.fiche-field label="Rôle système" :value="$this->displayRole($user->role)" />
                <x-patient.fiche-field label="E-mail vérifié"
                    :value="$user->email_verified_at ? 'Oui · ' . $user->email_verified_at->format('d/m/Y') : 'Non'" />
                <x-patient.fiche-field label="Identifiant" :value="'#' . $user->id" />
            </div>
        </section>

        <section
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:p-6 lg:col-span-2">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    <flux:icon.academic-cap class="size-5" />
                </div>
                <h2 class="text-base font-black text-slate-900 dark:text-white">Références professionnelles</h2>
            </div>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <x-patient.fiche-field label="CNOM" :value="$user->cnom ?: '—'" />
                <x-patient.fiche-field label="ONIC" :value="$user->onic ?: '—'" />
                <x-patient.fiche-field label="Hôpital d'affectation" :value="$user->hopital?->name ?? '—'" />
                <x-patient.fiche-field label="Département" :value="$user->departement?->name ?? '—'" />
            </div>
            <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                Compte créé et géré par l'administration. Les accès et affectations sont modifiables depuis le support technique.
            </p>
        </section>
    </div>
</section>
