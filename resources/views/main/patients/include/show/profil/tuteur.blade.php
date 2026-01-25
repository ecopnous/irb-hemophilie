<div class="tab-pane fade p-4" id="billingTab" role="tabpanel">
    <div class="profile-details mb-5">
        <div class="mb-3 pb-2 d-flex text-aligns-center justify-content-between">
            <h5 class="fw-bold">A propos</h5>
            <a href="javascript:void(0);" class="btn btn-sm btn-light-brand">Modifier les informations</a>
        </div>
        <x-pages.patient.row-detail title="Pere" :value="$patient->nom" />
        <x-pages.patient.row-detail title="Mere" :value="$patient->postnom" />
        <x-pages.patient.row-detail title="Epoux/Epouse" :value="$patient->prenom" />
        <x-pages.patient.row-detail title="Parent (Tuteur)" :value="$patient->formatted_birthdate" />
        <x-pages.patient.row-detail title="Personne de Contact" :value="$patient->age" />
    </div>
    <div class="profile-details mb-5">
        <div class="mb-3 mt-3 pb-2">
            <h5 class="fw-bold">Origines géographiques:</h5>
        </div>

        <x-pages.patient.row-detail title="Ethnie" :value="$patient->nom" />
        <x-pages.patient.row-detail title="Province" :value="$patient->postnom" />
        <x-pages.patient.row-detail title="Pays" :value="$patient->postnom" />
        <x-pages.patient.row-detail title="Race" :value="$patient->postnom" />
    </div>
</div>
