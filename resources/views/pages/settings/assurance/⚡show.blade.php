<?php

use App\Models\Configs\Assurance;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fiche assurance')] class extends Component {
    public Assurance $assurance;

    public function mount(int $id): void
    {
        $this->assurance = Assurance::query()->with('categorisation')->findOrFail($id);
    }
};
?>

<section class="w-full">
    <flux:heading class="sr-only">{{ __('Gestions des assurances') }}</flux:heading>
    <x-header_default :title="__('Assurances')" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
        ['label' => 'Assurance', 'icon' => 'building-office'],
    ]">
        <x-slot:actions>
            <x-button href="{{ route('settings.assurance.index') }}" wire:navigate>Retour a la liste</x-button>
        </x-slot>
    </x-header_default>

    {{-- <x-pages::settings.layout :heading="__('Fiche assurance')" :subheading="__('Details de l\'assurance enregistree')">
        <div class="mb-4">
           
        </div>

        <div class="grid gap-4 md:grid-cols-[220px_1fr]">
            <div class="rounded-xl border p-5 shadow-sm">
                <flux:heading size="lg">Logo</flux:heading>
                <div class="mt-4 flex justify-center">
                    @if ($assurance->logo)
                        <img src="{{ Storage::disk('public')->url($assurance->logo) }}" alt="Logo assurance" class="h-32 w-32 rounded-2xl object-cover ring-1 ring-zinc-200" />
                    @else
                        <div class="flex h-32 w-32 items-center justify-center rounded-2xl bg-zinc-100 text-xs text-zinc-500 ring-1 ring-zinc-200">
                            Aucun logo
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <flux:heading size="lg">Informations generales</flux:heading>
                <div class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                    <div><strong>Nom :</strong> {{ $assurance->name }}</div>
                    <div><strong>Type :</strong> {{ $assurance->type }}</div>
                    <div><strong>Email :</strong> {{ $assurance->email ?: '-' }}</div>
                    <div><strong>Telephone :</strong> {{ $assurance->phone ?: '-' }}</div>
                    <div><strong>Categorisation :</strong> {{ $assurance->categorisation?->name ?: '-' }}</div>
                    <div><strong>ID :</strong> {{ $assurance->id }}</div>
                </div>

                <div class="mt-6">
                    <flux:heading size="lg">Description</flux:heading>
                    <p class="mt-3 text-sm text-zinc-700">{{ $assurance->description ?: 'Aucune description renseignee.' }}</p>
                </div>
            </div>
        </div>
    </x-pages::settings.layout> --}}
</section>
