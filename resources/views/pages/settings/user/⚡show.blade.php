<?php

use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fiche utilisateur')] class extends Component {
    public User $user;

    public function mount(int $id): void
    {
        $this->user = User::query()
            ->with(['hopital', 'departement'])
            ->where('hopital_id', current_hopital_id())
            ->findOrFail($id);
    }
};
?>

<section class="w-full" wire:poll.60s>
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Fiche utilisateur')" :subheading="__('Support technique : details du corps medical de l\'hopital actuel')">
        <div class="mb-4">
            <x-button href="{{ route('settings.user.index') }}" wire:navigate>Retour a la liste</x-button>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <flux:heading size="lg">Identite</flux:heading>
                <div class="mt-4 space-y-3 text-sm">
                    <div><strong>Nom :</strong> {{ $user->name }}</div>
                    <div><strong>Post-nom :</strong> {{ $user->post_nom ?: '-' }}</div>
                    <div><strong>Prenom :</strong> {{ $user->prenom }}</div>
                    <div><strong>Genre :</strong> {{ $user->genre }}</div>
                    <div><strong>Date de naissance :</strong> {{ $user->date_naissance }}</div>
                    <div><strong>Nationalite :</strong> {{ $user->nationalite }}</div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <flux:heading size="lg">Compte</flux:heading>
                <div class="mt-4 space-y-3 text-sm">
                    <div><strong>Email :</strong> {{ $user->email }}</div>
                    <div><strong>Telephone :</strong> {{ $user->phone ?: '-' }}</div>
                    <div><strong>Role :</strong> {{ $user->role }}</div>
                    <div><strong>Statut :</strong> {{ $user->last_seen_at && $user->last_seen_at->greaterThanOrEqualTo(now()->subMinutes(6)) ? 'En ligne' : 'Hors ligne' }}</div>
                    <div><strong>Matricule :</strong> {{ $user->matricule ?: '-' }}</div>
                    <div><strong>Grade :</strong> {{ $user->grade ?: '-' }}</div>
                    <div><strong>Derniere activite :</strong> {{ $user->last_seen_at ?: '-' }}</div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <flux:heading size="lg">Affectation</flux:heading>
                <div class="mt-4 space-y-3 text-sm">
                    <div><strong>Hopital :</strong> {{ $user->hopital?->nom ?: '-' }}</div>
                    <div><strong>Departement :</strong> {{ $user->departement?->name ?: '-' }}</div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <flux:heading size="lg">References professionnelles</flux:heading>
                <div class="mt-4 space-y-3 text-sm">
                    <div><strong>CNOM :</strong> {{ $user->cnom ?: '-' }}</div>
                    <div><strong>ONIC :</strong> {{ $user->onic ?: '-' }}</div>
                    <div><strong>ID utilisateur :</strong> {{ $user->id }}</div>
                </div>
            </div>
        </div>
    </x-pages::settings.layout>
</section>
