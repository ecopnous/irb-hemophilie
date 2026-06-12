<x-layouts::app.sidebar :title="$title ?? null" :back="true">
    <x-slot:navigation>
        <flux:sidebar.nav>
            <flux:sidebar.item icon="scan-text" href="{{ route('laboratoire.index') }}" wire:navigate>Analyse de bons
            </flux:sidebar.item>
            <flux:sidebar.item icon="square-dashed-text" href="{{ route('laboratoire.rapport') }}" wire:navigate>Rapports
            </flux:sidebar.item>
            <flux:sidebar.item icon="archive-box" href="{{ route('laboratoire.stock') }}" wire:navigate>Stock laboratoire</flux:sidebar.item>
            <flux:sidebar.item icon="arrows-right-left" href="{{ route('laboratoire.stock_movements') }}" wire:navigate>Mouvements stock</flux:sidebar.item>
            <flux:sidebar.item icon="tags" href="{{ route('laboratoire.valeurs_exactes') }}" wire:navigate>
                Valeurs exacts
            </flux:sidebar.item>
            <flux:sidebar.item icon="panels-left-bottom" href="{{ route('laboratoire.groupes.index') }}" wire:navigate>
                Groupes d'examens
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </x-slot>
    <flux:main class="p-0 bg-[#f3f4f6] dark:bg-gray-950">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
