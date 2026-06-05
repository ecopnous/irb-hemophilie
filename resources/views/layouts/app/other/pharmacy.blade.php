<x-layouts::app.sidebar :title="$title ?? null" :back="true">
    <x-slot:navigation>
        <flux:sidebar.nav>
            <flux:sidebar.item icon="squares-2x2" href="{{ route('pharmacie.dashboard') }}" wire:navigate>Tableau de bord
            </flux:sidebar.item>
            <flux:sidebar.item icon="building-storefront" href="{{ route('pharmacie.pharmacies') }}" wire:navigate>Pharmacies</flux:sidebar.item>
            <flux:sidebar.item icon="plus-circle" href="{{ route('pharmacie.medicaments') }}" wire:navigate>Medicaments</flux:sidebar.item>
            <flux:sidebar.item icon="archive-box" href="{{ route('pharmacie.stock') }}" wire:navigate>Stock medicaments</flux:sidebar.item>
            <flux:sidebar.item icon="arrows-right-left" href="{{ route('pharmacie.movements') }}" wire:navigate>Mouvements stock</flux:sidebar.item>
            <flux:sidebar.item icon="document-text" href="{{ route('pharmacie.prescriptions') }}" wire:navigate>Prescriptions
            </flux:sidebar.item>
            <flux:sidebar.item icon="archive-box-x-mark" href="{{ route('pharmacie.depreciations') }}" wire:navigate>Deprecies
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </x-slot>
    <flux:main class="p-0 bg-[#f3f4f6] dark:bg-gray-950">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
