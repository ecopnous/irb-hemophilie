<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item :href="route('settings.hopital.index')" wire:navigate>{{ __('Hôpitaux') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('settings.assurance.index')" wire:navigate>{{ __('Assurances') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('settings.user.index')" wire:navigate>{{ __('Corps médicals') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('settings.departement.index')" wire:navigate>{{ __('Départements') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('settings.categorisation.index')" wire:navigate>{{ __('Categorisations') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('settings.paquet.index')" wire:navigate>{{ __('Paquets de soins') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('settings.projet.index')" wire:navigate>{{ __('Projets et campagnes') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('settings.vaccin.index')" wire:navigate>{{ __('Carnet des vaccins') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Apparence') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <div class="flex flex-row justify-between">
            <div>
                <h1 class="text-lg font-extrabold text-gray-900 dark:text-white tracking-tight">
                    {{ $heading ?? '' }}
                </h1>
                {{-- <flux:heading size="lg" level="1"></flux:heading> --}}
                <flux:subheading size="md" class="mb-6">{{ $subheading ?? '' }}</flux:subheading>
            </div>
            @isset($actions)
                <div>
                    {{ $actions }}
                </div>
            @endisset
        </div>
        <flux:separator variant="subtle" />

        <div class="mt-5 w-full max-w-none">
            {{ $slot }}
        </div>
    </div>
</div>
