<div>
    <x-patient.patient-profil-header :nav="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Dossiers patients', 'link' => 'patient.index', 'icon' => 'folder'],
        ['label' => $patient->nin, 'icon' => 'identification'],
    ]" :patient="$patient" :current_patient="$patient->id">
        <x-slot name="title">{{ ucfirst($patient->nom) }} {{ ucfirst($patient->postnom) }}
            {{ ucfirst($patient->prenom) }}</x-slot>
        <x-slot name="subtitle">ID: {{ $patient->nin }}
            {{ $patient->ins ? 'N°' . $patient->ins : '' }}</x-slot>
    </x-patient.patient-profil-header>

    {{ $slot }}
</div>
