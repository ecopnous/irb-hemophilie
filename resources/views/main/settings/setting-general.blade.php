@extends('main.settings.includes.settings.nav-setting')

@section('settings-content')
    <div class="content-area bg-white" data-scrollbar-target="#psScrollbarInit">
        <div class="content-area-header bg-white sticky-top">
            <div class="page-header-right ms-auto">
                <div class="d-flex align-items-center gap-3 page-header-right-items-wrapper">
                    <a href="javascript:void(0);" class="text-danger">Annuler</a>
                    <a href="javascript:void(0);" class="btn btn-primary successAlertMessage">
                        <i class="feather-save me-2"></i>
                        <span>Enregistrer les modifications</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="content-area-body">
            <label class="form-label">Nom de l'organisation</label>
            <div class="mb-4 input-group">
                <input type="text" class="form-control" placeholder="Nom complet">
                <input type="text" class="form-control" placeholder="Abbréviation">
            </div>
            <label class="form-label">Adresse electronique</label>
            <div class="mb-4 input-group">
                <input type="text" class="form-control" placeholder="Adresse email">
                <input type="number" class="form-control" placeholder="Numero telephone">
            </div>
            <div class="mb-4">
                <label class="form-label">Boite postale</label>
                <input type="text" class="form-control" placeholder="Boite postale">
            </div>
            <div class="mb-4">
                <label class="form-label">Devise monetaire</label>
                <select class="form-control" data-select2-selector="currency">
                    <option data-currency="cd">CDF - Congolese Franc - FC</option>
                    <option data-currency="us" selected>USD - US Dollar - $</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label">Catégorie de l'organisation</label>
                <select class="form-control" data-select2-selector="city">
                    <option data-city="bg-primary">Centre de diagnostic</option>
                    <option data-city="bg-success">Centre de santé</option>
                    <option data-city="bg-warning">Centre de recherche</option>
                    <option data-city="bg-danger">Centre de formation</option>
                    <option data-city="bg-info">Etablissement scolaire</option>
                    <option data-city="bg-secondary">Hopital du niveau zone de santé</option>
                    <option data-city="bg-teal" selected>Hopital général de référence</option>
                    <option data-city="bg-indigo">Hopital général provincial de référence</option>
                    <option data-city="bg-dark">Institut d'enseignement medicale</option>
                    <option data-city="bg-cyan">Laboratoire</option>
                    <option data-city="bg-orange">Pharmacie</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label">Domaine principale</label>
                <input type="url" class="form-control" placeholder="ex: https://www.example.com">
            </div>
            <hr class="my-4">
            <h4 class="mb-4">Informations Complémentaires</h4>
            <div class="mb-4">
                <label class="form-label">Partenaire et/ou Convention affiliée à l'organisation</label>
                <select class="form-select form-control max-select" data-select2-selector="tag" multiple>
                    @foreach ($assurances as $assurance)
                        <option value="{{ $assurance->id }}" data-bg="{{ $assurance->color ?? 'bg-secondary' }}">
                            {{ $assurance->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label">Départements</label>
                <select class="form-select form-control max-select" data-select2-selector="tag" multiple>
                    @foreach ($departements as $departement)
                        <option value="{{ $departement->id }}" data-bg="{{ $departement->color ?? 'bg-secondary' }}">
                            {{ $departement->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="hstack justify-content-between p-4 mb-3 border border-dashed border-gray-3 rounded-1">
                <div class="hstack me-4">
                    <div class="avatar-text">
                        <i class="feather-shield"></i>
                    </div>
                    <div class="ms-4">
                        <a href="javascript:void(0);" class="fw-bold mb-1 text-truncate-1-line">Fait parti de la Couverture
                            Santé Universelle (CSU)</a>
                        <div class="fs-12 text-muted text-truncate-1-line">Un système qui garantit l'accès aux soins de santé essentiels pour tous les citoyens, indépendamment de leur situation économique.</div>
                    </div>
                </div>
                <div class="form-check form-switch form-switch-sm">
                    <label class="form-check-label fw-500 text-dark c-pointer" for="formSwitchPassChange"></label>
                    <input class="form-check-input c-pointer" type="checkbox" id="formSwitchPassChange">
                </div>
            </div>
        </div>
    @endsection
