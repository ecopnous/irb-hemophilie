@extends('layouts.app')

{{-- @push('styles-vendor')
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2-theme.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/datepicker.min.css">
@endpush --}}

@section('main')
    <x-layouts.main-header title="Patients" :routes="[$patient->nin]">
        <a class="btn btn-primary" href="{{ route('consultation.create') }}">
            <i class="feather-plus me-2"></i>
            <span>Nouvelle Consultation</span>
        </a>
    </x-layouts.main-header>
    <div class="bg-white py-3 border-bottom rounded-0 p-md-0 mb-0">
        <div class="d-md-none d-flex align-items-center justify-content-between">
            <a href="javascript:void(0)" class="page-content-left-open-toggle">
                <i class="feather-align-left fs-20"></i>
            </a>
            <a href="javascript:void(0)" class="page-content-right-open-toggle">
                <i class="feather-align-right fs-20"></i>
            </a>
        </div>
        <div class="d-flex align-items-center justify-content-between">
            <div class="nav-tabs-wrapper page-content-left-sidebar-wrapper">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-content-left-close-toggle">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                </div>
                <ul class="nav nav-tabs nav-tabs-custom-style" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#proposalTab">Profils</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tasksTab">Informations</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notesTab">Notes</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#commentTab">Message</button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-content bg-white">
        <div class="tab-content">
            @include('main.patients.include.show.profil')
            @include('main.patients.include.show.info')
            @include('main.patients.include.show.note')
            @include('main.patients.include.show.message')
        </div>
    </div>
@endsection

{{-- @push('scripts-vendors')
    <script src="assets/vendors/js/select2.min.js"></script>
    <script src="assets/vendors/js/select2-active.min.js"></script>
    <script src="assets/vendors/js/datepicker.min.js"></script>
    <script src="assets/vendors/js/lslstrength.min.js"></script>
@endpush --}}
