@extends('layouts.app')

@push('styles-vendor')
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2-theme.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/datepicker.min.css">
@endpush

@section('main')
    <x-layouts.main-header title="Consultation" :routes="['Initalisation']">
        <button type="submit" form="patientForm" class="btn btn-primary">
            <i class="feather-save me-2"></i>
            <span>Sauvegarder la Fiche de consultation</span>
        </button>
        <button type="submit" form="patientForm" class="btn btn-danger">
            <i class="feather-save me-2"></i>
            <span>Continuer sans prelevement</span>
        </button>
    </x-layouts.main-header>
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-top-0" style="border-radius: 0px">
                    <form id="patientForm" action="{{ route('patient.store') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="tab-pane fade show active" id="profileTab" role="tabpanel">
                            @include('main.consultations.includes.create.info-initial')
                            <hr class="py-0" />
                            @include('main.consultations.includes.create.prelevement')
                        </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts-vendors')
    <script src="assets/vendors/js/select2.min.js"></script>
    <script src="assets/vendors/js/select2-active.min.js"></script>
    <script src="assets/vendors/js/datepicker.min.js"></script>
    <script src="assets/vendors/js/lslstrength.min.js"></script>
@endpush

@push('scripts-page')
    <script src="assets/js/customers-create-init.min.js"></script>
@endpush
