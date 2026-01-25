<div class="tab-pane fade show active p-4" id="overviewTab" role="tabpanel">
    <div class="about-section mb-5">
        <div class="mb-4">
            <h5 class="fw-bold mb-0">Note:</h5>
        </div>
        <p>{{ $patient->note ? $patient->note : 'Aucun note' }}</p>
    </div>
    <div class="profile-details mb-5">
        <div class="mb-4 d-flex">
            <h5 class="fw-bold mb-0">Détails Profil:</h5>
        </div>
        <x-pages.patient.row-detail title="Nom" :value="$patient->nom" />
        <x-pages.patient.row-detail title="Postnom" :value="$patient->postnom" />
        <x-pages.patient.row-detail title="Prenom" :value="$patient->prenom" />
        <x-pages.patient.row-detail title="Date de naissance" :value="$patient->formatted_birthdate" />
        <x-pages.patient.row-detail title="Age" :value="$patient->age" />
        <x-pages.patient.row-detail title="Prise en charge" :value="$patient->prise_en_charge" />
        <x-pages.patient.row-detail title="Nationnalité" :value="$patient->nationalite" />
    </div>
    <div class="profile-details mb-5">
        <div class="mb-4 d-flex">
            <h5 class="fw-bold mb-0">Informations complementaire:</h5>
        </div>
        <x-pages.patient.row-detail title="Grade" :value="$patient->grade" />
        <x-pages.patient.row-detail title="Unité" :value="$patient->unite" />
        <x-pages.patient.row-detail title="Matricule" :value="$patient->matricule" />
    </div>
    <div class="profile-details mb-5">
        <div class="mb-4 d-flex">
            <h5 class="fw-bold mb-0">Etat sérologique:</h5>
        </div>
        <x-pages.patient.row-detail title="Groupe sanguin" :value="$patient->grade" />
        <x-pages.patient.row-detail title="Électrophorèse" :value="$patient->unite" />
    </div>
</div>
