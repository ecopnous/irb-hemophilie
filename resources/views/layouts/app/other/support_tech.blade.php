<x-layouts::app.sidebar :title="$title ?? null" :back="true">
    <x-slot:navigation>
        <flux:sidebar.nav>
            <flux:navlist.item icon="building-office" :href="route('settings.hopital.index')" wire:navigate>
                {{ __('Hôpitaux') }}
            </flux:navlist.item>
            <flux:navlist.item icon="users" :href="route('settings.user.index')" wire:navigate>
                {{ __('Corps médicals') }}
            </flux:navlist.item>
            <flux:navlist.item icon="shield-check" :href="route('settings.assurance.index')" wire:navigate>
                {{ __('Assurances') }}
            </flux:navlist.item>
            <flux:navlist.item icon="building-office-2" :href="route('settings.departement.index')" wire:navigate>
                {{ __('Départements') }}
            </flux:navlist.item>
            <flux:navlist.item icon="rectangle-group" :href="route('settings.categorisation.index')" wire:navigate>
                {{ __('Categorisations') }}
            </flux:navlist.item>
            <flux:navlist.item icon="briefcase" :href="route('settings.paquet.index')" wire:navigate>
                {{ __('Paquets de soins') }}
            </flux:navlist.item>
            <flux:navlist.item icon="light-bulb" :href="route('settings.projet.index')" wire:navigate>
                {{ __('Projets et campagnes') }}
            </flux:navlist.item>
            {{-- <flux:navlist.item icon="cog-6-tooth" :href="route('settings.vaccin.index')" wire:navigate>
                {{ __('Carnet des vaccins') }}
            </flux:navlist.item>
            <flux:navlist.item icon="home-modern" :href="route('settings.hospitalisation.index')" wire:navigate>
                {{ __('Hospitalisations') }}
            </flux:navlist.item> --}}
            <flux:navlist.item icon="cog-6-tooth" :href="route('appearance.edit')" wire:navigate>{{ __('Apparence') }}
            </flux:navlist.item>
        </flux:sidebar.nav>
        <flux:sidebar.spacer />
    </x-slot>
    <flux:main class="p-0 bg-[#f3f4f6] dark:bg-gray-950">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
