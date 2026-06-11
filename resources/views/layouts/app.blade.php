<x-layouts::app.sidebar :title="$title ?? null">
    <x-slot:navigation>
        <flux:sidebar.nav>
            <flux:sidebar.item icon="clipboard-plus" href="{{ route('patient.create') }}" wire:navigate>Nouvelle fiche
                médical</flux:sidebar.item>
        </flux:sidebar.nav>
        <flux:sidebar.nav>
            <flux:sidebar.item icon="airplay" href="{{ route('dashboard') }}" wire:navigate>Réception
            </flux:sidebar.item>
            {{-- <flux:sidebar.item icon="clipboard-document-list" href="{{ route('reception.papeterie') }}" wire:navigate>Papeterie
            </flux:sidebar.item>
            <flux:sidebar.item icon="briefcase" href="{{ route('reception.services') }}" wire:navigate>Services de base
            </flux:sidebar.item> --}}
            <flux:sidebar.item icon="inbox" href="{{ route('consultation.triage') }}" wire:navigate>Triage
            </flux:sidebar.item>
            <flux:sidebar.item icon="stethoscope" href="{{ route('consultation.index') }}" wire:navigate>Consultations
            </flux:sidebar.item>
            <flux:sidebar.item icon="banknotes" href="{{ route('facturation.index') }}" wire:navigate>Comptabilité
            </flux:sidebar.item>
            <flux:sidebar.item icon="beaker" href="{{ route('laboratoire.index') }}" wire:navigate>Laboratoire
            </flux:sidebar.item>
            <flux:sidebar.item icon="photo" href="{{ route('imagerie.index') }}" wire:navigate>Imagerie
            </flux:sidebar.item>
            <flux:sidebar.item icon="pill" href="{{ route('pharmacie.dashboard') }}" wire:navigate>Pharmacie
            </flux:sidebar.item>
            <flux:sidebar.item icon="home-modern" href="{{ route('hospital.index') }}" wire:navigate>
                Hospitalisation
            </flux:sidebar.item>
            <flux:sidebar.item icon="library-big" href="{{ route('patient.index') }}" wire:navigate>Dossiers médicaux
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </x-slot>
    <flux:main class="p-0 bg-[#f3f4f6] dark:bg-gray-950">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
