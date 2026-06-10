<x-layouts::app.sidebar :title="$title ?? null" :back="true">
    <x-slot:navigation>
        <flux:sidebar.nav>
            <flux:sidebar.item icon="receipt" href="{{ route('facturation.index') }}" wire:navigate>Factures
                clinique
            </flux:sidebar.item>
            <flux:sidebar.item icon="receipt-text" href="{{ route('facturation.documents') }}" wire:navigate>Factures et devis
            </flux:sidebar.item>
            <flux:sidebar.item icon="wallet" href="{{ route('facturation.payments') }}" wire:navigate>Historique des
                paiements</flux:sidebar.item>
            <flux:sidebar.item icon="badge-dollar-sign" href="{{ route('facturation.tariffs') }}" wire:navigate>Grilles de
                tarification
            </flux:sidebar.item>
            <flux:sidebar.item icon="book-marked" href="{{ route('facturation.cash') }}" wire:navigate>Journaux de caisse
            </flux:sidebar.item>
            <flux:sidebar.item icon="building-office-2" href="{{ route('facturation.inventory') }}" wire:navigate>Inventaire
                des equipements
            </flux:sidebar.item>
            <flux:sidebar.item icon="user-group" href="{{ route('facturation.clients') }}" wire:navigate>Clients
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </x-slot>
    <flux:main class="p-0 bg-[#f3f4f6] dark:bg-gray-950">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
