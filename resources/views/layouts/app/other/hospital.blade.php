<x-layouts::app.sidebar :title="$title ?? null" :back="true">
    <x-slot:navigation>
        <flux:sidebar.nav>
            <flux:sidebar.item icon="airplay" href="{{ route('hospital.index') }}" wire:navigate>Réception
            </flux:sidebar.item>
            <flux:sidebar.item icon="wallet" href="{{ route('hospital.facturation') }}" wire:navigate>Facturation
            </flux:sidebar.item>
            <flux:sidebar.item icon="cog-8-tooth" href="{{ route('hospital.configuration') }}" wire:navigate>
                Configuration
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </x-slot>
    <flux:main class="p-0 bg-[#f3f4f6] dark:bg-gray-950">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
