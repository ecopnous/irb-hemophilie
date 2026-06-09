<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white antialiased dark:bg-zinc-800">
    <x-toast />
    <x-dialog />

    <x-command-palette id="patients" :request="[
        'url' => route('api.patient'),
        'method' => 'get',
        'params' => [
            'search' => '',
            'hopital_id' => current_hopital_id(),
        ],
    ]" select="label:name|value:id|description:description"
        x-on:select="window.location.href = '{{ route('patient.show', '__PATIENT_ID__') }}'.replace('__PATIENT_ID__', $event.detail.id ?? $event.detail.value)"
        placeholder="Tapez au moins 3 caracteres..." />

    <flux:sidebar sticky collapsible class="border-r border-zinc-200 bg-zinc-50 dark:border-slate-800 dark:bg-slate-900">
        <flux:sidebar.header class="border-b border-zinc-200 pb-4 dark:border-slate-700">
            <div class="flex size-10 items-center justify-center rounded-xl bg-primary-600 text-white shadow-sm">
                <flux:icon.hospital variant="mini" />
            </div>

            <span class="font-semibold leading-none text-zinc-800 dark:text-white">
                {{ current_hopital_nom() }}
            </span>
        </flux:sidebar.header>

        <flux:button size="sm" icon="magnifying-glass" class="rounded-xl" color="indigo"
            x-on:click.prevent="$tsui.open.commandPalette('patients')">
            Rechercher un patient
        </flux:button>

        @isset($navigation)
            {{ $navigation }}
        @endisset

        <flux:sidebar.spacer />

        <flux:sidebar.nav>
            @if (isset($back) && $back)
                <flux:sidebar.item icon="arrow-turn-down-left" href="{{ route('dashboard') }}" wire:navigate>
                    Retour à la réception
                </flux:sidebar.item>
            @else
                <flux:sidebar.item icon="cog-6-tooth" href="{{ route('settings.hopital.index') }}" wire:navigate>
                    Support téchnique
                </flux:sidebar.item>
                <flux:sidebar.item icon="building-office-2" href="{{ route('groupe_hopitaux.index') }}" wire:navigate>
                    Groupe d'hopitaux
                </flux:sidebar.item>
            @endif
        </flux:sidebar.nav>
    </flux:sidebar>

    <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-slate-800 dark:bg-slate-900">
        <flux:brand href="#" logo="{{ asset('assets/logo.png') }}" class="max-lg:hidden dark:hidden" />
        <flux:brand href="#" logo="{{ asset('assets/dart-logo.png') }}" class="hidden max-lg:hidden! dark:flex" />

        <flux:navbar class="-mb-px max-lg:hidden">
            <flux:navbar.item icon="chart-column-big" href="{{ route('analytics') }}" :current="request()->routeIs('analytics')" wire:navigate>Analytics</flux:navbar.item>
            <flux:navbar.item icon="envelope" badge="12" href="#">Boite de reception</flux:navbar.item>
            <flux:navbar.item icon="document-text" href="#">Documents</flux:navbar.item>
        </flux:navbar>

        <flux:spacer />

        <flux:navbar class="me-4">
        </flux:navbar>

        <flux:dropdown position="top" align="start">
            <flux:profile avatar="https://fluxui.dev/img/demo/user.png" />

            <flux:menu>
                <flux:menu.radio.group>
                    <flux:menu.radio checked>Olivia Martin</flux:menu.radio>
                    <flux:menu.radio>Truly Delta</flux:menu.radio>
                </flux:menu.radio.group>
                <flux:menu.separator />
                <flux:menu.item icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @include('partials.heartbeat')

    @persist('toast')
        <flux:toast.group position="top end">
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
