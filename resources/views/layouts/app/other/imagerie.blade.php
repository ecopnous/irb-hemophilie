<x-layouts::app.sidebar :title="$title ?? null" :back="true">
    <x-slot:navigation>
        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" href="{{ route('hospital.index') }}" wire:navigate>Réception de bons
            </flux:sidebar.item>
            <flux:sidebar.item icon="banknotes" href="#" wire:navigate>Rapports
            </flux:sidebar.item>
            <flux:sidebar.item icon="wrench-screwdriver" href="#" wire:navigate>Valeurs exacts
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </x-slot>
    <flux:main class="p-0 bg-[#f3f4f6] dark:bg-gray-950">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
