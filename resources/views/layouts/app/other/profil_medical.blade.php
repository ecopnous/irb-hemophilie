<x-layouts::app.sidebar :title="$title ?? null" :back="true">
    <x-slot:navigation>
        <flux:sidebar.nav>
            <flux:sidebar.item icon="user-circle" href="{{ route('patient.show', $current_patient) }}" wire:navigate>
                Profil medical
            </flux:sidebar.item>
            <flux:sidebar.item icon="folder" href="{{ route('patient.fiche_medicale', $current_patient) }}"
                wire:navigate>
                Fiche medicale
            </flux:sidebar.item>
            <flux:sidebar.item icon="chart-column-big" href="#" wire:navigate>
                Evolution du patient
            </flux:sidebar.item>
            <flux:sidebar.item icon="envelope" href="{{ route('patient.inbox', $current_patient) }}" wire:navigate>
                Messagerie clinique
            </flux:sidebar.item>
            <flux:sidebar.item icon="newspaper" href="{{ route('consultation.historique', $current_patient) }}"
                wire:navigate>
                Historique des consultations
            </flux:sidebar.item>
            <flux:sidebar.item icon="wallet" href="{{ route('consultation.facture', $current_patient) }}"
                wire:navigate>
                Historique des facturations
            </flux:sidebar.item>
        </flux:sidebar.nav>
        <flux:sidebar.spacer />
    </x-slot>

    <flux:main class="bg-[#f3f4f6] p-0 dark:bg-gray-950">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
