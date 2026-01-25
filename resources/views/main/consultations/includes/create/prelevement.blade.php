<div class="card-body additional-info">
    <div class="mb-4 d-flex align-items-center justify-content-between">
        <h5 class="fw-bold mb-0 me-4">
            <span class="d-block mb-2">Prelevement de signes vitaux:</span>
            <span class="fs-12 fw-normal text-muted text-truncate-1-line">Complétez les
                informations de localisation pour améliorer nos services.</span>
        </h5>
    </div>
    <x-forms.item-grid-form title="Signes vitaux">
        <div class="input-group">
            <span class="input-group-text">Kg</span>
            <input type="text" aria-label="postnom" placeholder="Poids" class="form-control">
            <span class="input-group-text">°C</span>
            <input type="number" min="35" max="45" aria-label="prenom" placeholder="Température"
                class="form-control">
            <input type="number" aria-label="prenom" placeholder="Systolique" class="form-control">
        </div>
    </x-forms.item-grid-form>
    <x-forms.item-grid-form title="Mesures anthropométriques">
        <div class="input-group">
            <span class="input-group-text">Cm</span>
            <input type="text" aria-label="postnom" placeholder="Taille" class="form-control">
            <span class="input-group-text">Cm</span>
            <input type="text" aria-label="prenom" placeholder="Périmètre cranien" class="form-control">
            <span class="input-group-text">Cm</span>
            <input type="text" aria-label="prenom" placeholder="Périmètre brachial " class="form-control">
        </div>
    </x-forms.item-grid-form>
    <x-forms.item-grid-form title="Fréquence">
        <div class="input-group">
            <input type="number" aria-label="postnom" placeholder="Fréquence Cardiaque" class="form-control">
            <input type="number" aria-label="prenom" placeholder="Fréquence Respiratoire" class="form-control">
            <input type="number" aria-label="prenom" placeholder="Diastolique " class="form-control">
        </div>
    </x-forms.item-grid-form>
    <x-forms.item-grid-form title="Mesures anthropométriques">
        <div class="input-group">
            <span class="input-group-text">%</span>
            <input type="number" max="100" min="0" aria-label="postnom" placeholder="Saturation en Oygène"
                class="form-control">
            <input type="number" aria-label="prenom" placeholder="Glycémie" class="form-control">
            <input type="text" aria-label="prenom" placeholder="Mois (Ex:M1) " class="form-control">
        </div>
    </x-forms.item-grid-form>

</div>
