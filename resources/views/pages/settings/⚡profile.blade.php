<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Informations du compte'), Layout('layouts::app')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', heading: 'Profil mis a jour', text: 'Vos informations ont ete enregistrees.');
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(text: 'Un nouveau lien de verification a ete envoye.');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<div class="mx-auto max-w-7xl space-y-6">
    <x-breadcrumbs :items="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Parametres', 'link' => 'settings.index', 'icon' => 'cog-6-tooth'],
        ['label' => 'Informations du compte', 'icon' => 'identification'],
    ]" />

    <div class="grid grid-cols-2 gap-4">
        <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-5 dark:border-slate-800 dark:bg-slate-900/80">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white">Informations du compte</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Mettez a jour votre nom et votre adresse email.
                    </p>
                </div>
                <flux:button href="{{ route('settings.index') }}" variant="ghost" size="sm" icon="arrow-left" wire:navigate>
                    Retour
                </flux:button>
            </div>
        </div>

        <form wire:submit="updateProfileInformation" class="space-y-5 p-6 sm:p-8">
            <flux:input wire:model="name" label="Nom" type="text" required autofocus autocomplete="name" />
            <flux:input wire:model="email" label="Adresse email" type="email" required autocomplete="email" />

            @if ($this->hasUnverifiedEmail)
                <div class="rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                    Votre adresse email n'est pas verifiee.
                    <flux:link class="ms-1 cursor-pointer font-semibold" wire:click.prevent="resendVerificationNotification">
                        Renvoyer le lien de verification
                    </flux:link>
                </div>
            @endif

            <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-5 dark:border-slate-800">
                <flux:button href="{{ route('settings.index') }}" variant="ghost" wire:navigate>Annuler</flux:button>
                <flux:button variant="primary" type="submit" color="indigo" icon="check" data-test="update-profile-button">
                    Enregistrer
                </flux:button>
            </div>
        </form>
    </section>

    @if ($this->showDeleteUser)
        <section
            class="overflow-hidden rounded-[1.75rem] border border-red-200 bg-red-50/30 shadow-sm dark:border-red-500/20 dark:bg-red-500/5">
            <div class="p-6 sm:p-8">
                <livewire:pages::settings.delete-user-form />
            </div>
        </section>
    @endif
    </div>
</div>
