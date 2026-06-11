<x-layouts::app.sidebar :title="$title ?? null" :back="true">
    <x-slot:navigation>
        <flux:sidebar.nav>
            <flux:sidebar.item icon="airplay" href="{{ route('dashboard') }}" wire:navigate>Tableau de bord
            </flux:sidebar.item>
            <flux:sidebar.item icon="clipboard-document-list" href="{{ route('reception.papeterie') }}" wire:navigate>Papeterie
            </flux:sidebar.item>
            <flux:sidebar.item icon="briefcase" href="{{ route('reception.services') }}" wire:navigate>Service de base
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </x-slot>
    <flux:main class="p-0 bg-[#f3f4f6] dark:bg-gray-950">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
