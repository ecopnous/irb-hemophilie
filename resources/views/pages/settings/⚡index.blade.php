<?php

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Features;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Parametres'), Layout('layouts::app')] class extends Component {
    #[Computed]
    public function user()
    {
        return Auth::user()->loadMissing(['hopital', 'departement']);
    }

    #[Computed]
    public function canManageTwoFactor(): bool
    {
        return Features::canManageTwoFactorAuthentication();
    }

    public function isOnline(): bool
    {
        return $this->user->last_seen_at?->greaterThanOrEqualTo(now()->subMinutes(6)) ?? false;
    }

    /**
     * @return array<int, array{title: string, description: string, route: string, icon: string, tone: string}>
     */
    public function sections(): array
    {
        $sections = [
            [
                'title' => 'Mon profil',
                'description' => 'Fiche personnelle, identite et references professionnelles.',
                'route' => 'profil',
                'icon' => 'user-circle',
                'tone' => 'indigo',
            ],
            [
                'title' => 'Informations du compte',
                'description' => 'Modifier votre nom et votre adresse email.',
                'route' => 'profile.edit',
                'icon' => 'identification',
                'tone' => 'sky',
            ],
            [
                'title' => 'Securite',
                'description' => 'Mot de passe, authentification a deux facteurs.',
                'route' => 'security.edit',
                'icon' => 'shield-check',
                'tone' => 'violet',
            ],
            [
                'title' => 'Apparence',
                'description' => 'Theme clair, sombre ou automatique selon le systeme.',
                'route' => 'appearance.edit',
                'icon' => 'swatch',
                'tone' => 'amber',
            ],
        ];

        return $sections;
    }
}; ?>

<div class="mx-auto max-w-7xl space-y-6">
    <x-breadcrumbs :items="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Parametres', 'icon' => 'cog-6-tooth'],
    ]" />

    <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="bg-linear-to-r from-indigo-600 via-violet-600 to-sky-500 px-6 py-7 sm:px-8">
            <div class="flex flex-wrap items-center gap-5">
                <div
                    class="flex size-16 items-center justify-center rounded-[1.25rem] border-4 border-white/30 bg-white/20 text-xl font-black text-white shadow-lg backdrop-blur-sm">
                    {{ $this->user->initials() }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-white/70">Centre de parametres</p>
                    <h1 class="mt-1 truncate text-2xl font-black text-white sm:text-3xl">
                        {{ trim(collect([$this->user->name, $this->user->prenom])->filter()->implode(' ')) }}
                    </h1>
                    <p class="mt-1 truncate text-sm text-white/85">{{ $this->user->email }}</p>
                </div>
                <span @class([
                    'rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide',
                    'bg-emerald-400/20 text-emerald-100' => $this->isOnline(),
                    'bg-white/15 text-white/80' => ! $this->isOnline(),
                ])>
                    {{ $this->isOnline() ? 'En ligne' : 'Hors ligne' }}
                </span>
            </div>
        </div>

        <div class="grid gap-px bg-slate-100 sm:grid-cols-3 dark:bg-slate-800">
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Hopital</p>
                <p class="mt-1 truncate text-sm font-bold text-slate-900 dark:text-white">
                    {{ $this->user->hopital?->name ?? current_hopital_nom() ?? '—' }}
                </p>
            </div>
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Departement</p>
                <p class="mt-1 truncate text-sm font-bold text-slate-900 dark:text-white">
                    {{ $this->user->departement?->name ?? '—' }}
                </p>
            </div>
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Grade</p>
                <p class="mt-1 truncate text-sm font-bold text-slate-900 dark:text-white">
                    {{ ucfirst($this->user->grade ?: '—') }}
                </p>
            </div>
        </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($this->sections() as $section)
            @php
                $toneClasses = match ($section['tone']) {
                    'sky' => 'border-sky-200 bg-sky-50/50 hover:border-sky-300 dark:border-sky-500/20 dark:bg-sky-500/5 dark:hover:border-sky-500/40',
                    'violet' => 'border-violet-200 bg-violet-50/50 hover:border-violet-300 dark:border-violet-500/20 dark:bg-violet-500/5 dark:hover:border-violet-500/40',
                    'amber' => 'border-amber-200 bg-amber-50/50 hover:border-amber-300 dark:border-amber-500/20 dark:bg-amber-500/5 dark:hover:border-amber-500/40',
                    default => 'border-indigo-200 bg-indigo-50/50 hover:border-indigo-300 dark:border-indigo-500/20 dark:bg-indigo-500/5 dark:hover:border-indigo-500/40',
                };
                $iconClasses = match ($section['tone']) {
                    'sky' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
                    'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
                    'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                    default => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300',
                };
            @endphp
            <a href="{{ route($section['route']) }}" wire:navigate
                class="group flex items-start gap-4 rounded-[1.5rem] border p-5 shadow-sm transition {{ $toneClasses }}">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $iconClasses }}">
                    <flux:icon :icon="$section['icon']" class="size-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">{{ $section['title'] }}</h2>
                        <flux:icon.arrow-right
                            class="size-4 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-slate-600 dark:group-hover:text-slate-200" />
                    </div>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $section['description'] }}</p>
                </div>
            </a>
        @endforeach
    </div>

    <section
        class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Raccourci apparence</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Changez le theme sans quitter cette page.</p>
            </div>
            <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="shrink-0">
                <flux:radio value="light" icon="sun">Clair</flux:radio>
                <flux:radio value="dark" icon="moon">Sombre</flux:radio>
                <flux:radio value="system" icon="computer-desktop">Systeme</flux:radio>
            </flux:radio.group>
        </div>
    </section>
</div>
