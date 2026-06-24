@php
$patientProfileId = $patient->id ?? request()->route('id');
@endphp

<x-layouts::app.sidebar :title="$title ?? null" :back="true">
    <x-slot:navigation>
        <flux:sidebar.nav>
            <flux:sidebar.item icon="user-circle" href="{{ route('patient.show', $patientProfileId) }}" wire:navigate>
                Profil medical
            </flux:sidebar.item>
            <flux:sidebar.item icon="folder" href="{{ route('patient.fiche_medicale', $patientProfileId) }}"
                wire:navigate>
                Fiche medicale
            </flux:sidebar.item>
            <flux:sidebar.item icon="chart-column-big" href="{{ route('patient.evolution', $patientProfileId) }}"
                :current="request()->routeIs('patient.evolution')" wire:navigate>
                Evolution du patient
            </flux:sidebar.item>
            <flux:sidebar.item icon="envelope" href="{{ route('patient.inbox', $patientProfileId) }}" wire:navigate>
                Messagerie clinique
            </flux:sidebar.item>
            <flux:sidebar.item icon="newspaper" href="{{ route('consultation.historique', $patientProfileId) }}"
                wire:navigate>
                Historique des consultations
            </flux:sidebar.item>
            <flux:sidebar.item icon="wallet" href="{{ route('consultation.facture', $patientProfileId) }}"
                wire:navigate>
                Historique des facturations
            </flux:sidebar.item>
            <flux:sidebar.item icon="archive-box-arrow-down"
                href="{{ route('patient.archivages', $patientProfileId) }}"
                :current="request()->routeIs('patient.archivages')" wire:navigate>
                Archivages
            </flux:sidebar.item>
        </flux:sidebar.nav>
        <flux:sidebar.spacer />
    </x-slot>

    <flux:main class="bg-[#f3f4f6] p-0 dark:bg-gray-950">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
