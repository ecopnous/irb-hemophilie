<?php

use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Securite du compte'), Layout('layouts::app')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', heading: 'Mot de passe mis a jour', text: 'Votre nouveau mot de passe est actif.');
    }

    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }
}; ?>

<div class="mx-auto max-w-7xl space-y-6">
    <x-breadcrumbs :items="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Parametres', 'link' => 'settings.index', 'icon' => 'cog-6-tooth'],
        ['label' => 'Mon profil', 'link' => 'profil', 'icon' => 'user-circle'],
        ['label' => 'Securite', 'icon' => 'shield-check'],
    ]" />

    <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="bg-linear-to-r from-slate-800 via-slate-700 to-slate-900 px-6 py-7 sm:px-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div
                        class="flex size-12 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-white">
                        <flux:icon.lock-closed class="size-6" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-white/70">Compte</p>
                        <h1 class="text-xl font-black text-white sm:text-2xl">Modifier le mot de passe</h1>
                        <p class="mt-1 text-sm text-white/80">
                            Utilisez un mot de passe long et unique pour proteger votre acces.
                        </p>
                    </div>
                </div>
                <flux:button href="{{ route('settings.index') }}" variant="filled" size="sm"
                    class="!bg-white/15 !text-white hover:!bg-white/25" icon="arrow-left" wire:navigate>
                    Retour aux parametres
                </flux:button>
            </div>
        </div>

        <form method="POST" wire:submit="updatePassword" class="space-y-5 p-6 sm:p-8">
            <flux:input
                wire:model="current_password"
                label="Mot de passe actuel"
                type="password"
                required
                autocomplete="current-password"
                viewable
                placeholder="Saisissez votre mot de passe actuel"
            />

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input
                    wire:model="password"
                    label="Nouveau mot de passe"
                    type="password"
                    required
                    autocomplete="new-password"
                    viewable
                    placeholder="Minimum 8 caracteres"
                />
                <flux:input
                    wire:model="password_confirmation"
                    label="Confirmer le mot de passe"
                    type="password"
                    required
                    autocomplete="new-password"
                    viewable
                    placeholder="Repeter le nouveau mot de passe"
                />
            </div>

            <div class="rounded-2xl border border-sky-100 bg-sky-50/80 px-4 py-3 text-sm text-sky-900 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-100">
                Conseil : combinez lettres, chiffres et symboles. Evitez les mots de passe deja utilises ailleurs.
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5 dark:border-slate-800">
                <flux:button href="{{ route('settings.index') }}" variant="ghost" wire:navigate>
                    Annuler
                </flux:button>
                <flux:button variant="primary" type="submit" color="indigo" icon="check" data-test="update-password-button">
                    Enregistrer le mot de passe
                </flux:button>
            </div>
        </form>
    </section>

    @if ($canManageTwoFactor)
        <section
            class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-5 dark:border-slate-800 dark:bg-slate-900/80">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                        <flux:icon.shield-check class="size-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Authentification a deux facteurs</h2>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                            Renforcez la securite de votre connexion avec un code TOTP.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-5 p-6 sm:p-8" wire:cloak>
                @if ($twoFactorEnabled)
                    <flux:text>
                        Lors de la connexion, un code securise vous sera demande via votre application d'authentification.
                    </flux:text>

                    <div class="flex flex-wrap gap-3">
                        <flux:button variant="danger" wire:click="disable">
                            Desactiver la 2FA
                        </flux:button>
                    </div>

                    <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                @else
                    <flux:text variant="subtle">
                        Activez la double authentification pour ajouter une couche de protection lors de chaque connexion.
                    </flux:text>

                    <flux:modal.trigger name="two-factor-setup-modal">
                        <flux:button variant="primary" color="indigo" wire:click="$dispatch('start-two-factor-setup')">
                            Activer la 2FA
                        </flux:button>
                    </flux:modal.trigger>

                    <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                @endif
            </div>
        </section>
    @endif
</div>
