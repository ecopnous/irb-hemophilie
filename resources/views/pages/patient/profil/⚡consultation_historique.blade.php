<?php
use App\Models\DossierPatient;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts::app.other.profil_medical')] class extends Component {
    public $patient;

    public function mount($id)
    {
        $this->patient = DossierPatient::findOrFail($id);
    }
};
?>

<div>
    <x-patient.patient-profil-header :nav="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Dossiers patients', 'link' => 'patient.index', 'icon' => 'folder'],
        ['label' => $patient->nin, 'icon' => 'identification'],
    ]" :patient="$patient" :current_patient="$patient->id">
        <x-slot name="title">Historique des consultations</x-slot>
        <x-slot name="subtitle">{{ ucfirst($patient->nom) }} {{ ucfirst($patient->postnom) }}
            {{ ucfirst($patient->prenom) }}</x-slot>
    </x-patient.patient-profil-header>

    <livewire:historique-consult :dossierPatientId="$patient->id" />
</div>
