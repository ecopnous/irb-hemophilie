@extends('layouts.app')

@push('styles-vendor')
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2-theme.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/datepicker.min.css">
@endpush

@section('main')
    <div class="nxl-content without-header nxl-full-content">
        <!-- [ Main Content ] start -->
        <div class="main-content d-flex">
            <!-- [ Content Sidebar ] start -->
            <div class="content-sidebar content-sidebar-md" data-scrollbar-target="#psScrollbarInit">
                <div class="content-sidebar-header bg-white sticky-top hstack justify-content-between">
                    <h4 class="fw-bolder mb-0">Paramettres</h4>
                    <a href="javascript:void(0);" class="app-sidebar-close-trigger d-flex">
                        <i class="feather-x"></i>
                    </a>
                </div>
                <div class="content-sidebar-body">
                    <ul class="nav flex-column nxl-content-sidebar-item">
                        <li class="nav-item">
                            <a class="nav-link {{ session('settings_nav') == 'general' ? 'active' : '' }}"
                                href="{{ route('settings.general') }}">
                                <i class="feather-sliders"></i>
                                <span>General</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ session('settings_nav') == 'corps-medical' ? 'active' : '' }}"
                                href="settings-seo.html">
                                <i class="feather-user-check"></i>
                                <span>Corps médical</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ session('settings_nav') == 'departement' ? 'active' : '' }}"
                                href="{{ route('settings.departement') }}">
                                <i class="feather-layers"></i>
                                <span>Départements</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ session('settings_nav') == 'categorie' ? 'active' : '' }}"
                                href="settings-seo.html">
                                <i class="feather-tag"></i>
                                <span>Catégories</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ session('settings_nav') == 'projet' ? 'active' : '' }}"
                                href="settings-seo.html">
                                <i class="feather-briefcase"></i>
                                <span>Projets et campagnes</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ session('settings_nav') == 'carnet-vaccin' ? 'active' : '' }}"
                                href="settings-seo.html">
                                <i class="feather-shield"></i>
                                <span>Carnet des vaccins</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ session('settings_nav') == 'assurance' ? 'active' : '' }}"
                                href="settings-seo.html">
                                <i class="feather-credit-card"></i>
                                <span>Assurances</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            @yield('settings-content')
        </div>
        <!-- [ Main Content ] end -->
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
