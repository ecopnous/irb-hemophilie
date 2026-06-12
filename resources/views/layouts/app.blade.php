<x-layouts::app.sidebar :title="$title ?? null">
    <x-slot:navigation>
        <flux:sidebar.nav>
            <x-nav.sidebar-items group="actions" />
        </flux:sidebar.nav>
        <flux:sidebar.nav>
            <x-nav.sidebar-items group="main" />
        </flux:sidebar.nav>
    </x-slot>
    <flux:main class="p-0 bg-[#f3f4f6] dark:bg-gray-950">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
