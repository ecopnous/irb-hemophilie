@push('styles-vendor')
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/dataTables.bs5.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2-theme.min.css">
@endpush

@extends('layouts.app')

@section('main')
    <x-layouts.main-header title="Patients" :routes="['Tous']">
        <div class="dropdown filter-dropdown">
            <a class="btn btn-light-brand" data-bs-toggle="dropdown" data-bs-offset="0, 10" data-bs-auto-close="outside">
                <i class="feather-filter me-2"></i>
                <span>Filter</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end">
                <div class="dropdown-item">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="Role" checked="checked">
                        <label class="custom-control-label c-pointer" for="Role">Role</label>
                    </div>
                </div>
                <div class="dropdown-item">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="Team" checked="checked">
                        <label class="custom-control-label c-pointer" for="Team">Team</label>
                    </div>
                </div>
                <div class="dropdown-item">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="Email" checked="checked">
                        <label class="custom-control-label c-pointer" for="Email">Email</label>
                    </div>
                </div>
                <div class="dropdown-item">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="Member" checked="checked">
                        <label class="custom-control-label c-pointer" for="Member">Member</label>
                    </div>
                </div>
                <div class="dropdown-item">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="Recommendation" checked="checked">
                        <label class="custom-control-label c-pointer" for="Recommendation">Recommendation</label>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="javascript:void(0);" class="dropdown-item">
                    <i class="feather-plus me-3"></i>
                    <span>Create New</span>
                </a>
                <a href="javascript:void(0);" class="dropdown-item">
                    <i class="feather-filter me-3"></i>
                    <span>Manage Filter</span>
                </a>
            </div>
        </div>
        <a href="{{ route('patient.create') }}" class="btn btn-primary">
            <i class="feather-plus me-2"></i>
            <span>Nouveau dossier médical</span>
        </a>
    </x-layouts.main-header>
    <div id="collapseOne" class="accordion-collapse collapse page-header-collapse">
        <div class="accordion-body pb-2">
            <div class="row">
                <div class="col-xxl-3 col-md-6">
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <a href="javascript:void(0);" class="fw-bold d-block">
                                <span class="d-block">Not Started</span>
                                <span class="fs-24 fw-bolder d-block">04</span>
                            </a>
                            <div class="pt-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <a href="javascript:void(0);" class="fs-12 fw-medium text-muted">
                                        <span>Invoices Awaiting</span>
                                        <i class="feather-link-2 fs-10 ms-1"></i>
                                    </a>
                                    <div>
                                        <span class="fs-12 text-muted">$5,569</span>
                                        <span class="fs-11 text-muted">(56%)</span>
                                    </div>
                                </div>
                                <div class="progress mt-2 ht-3">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 56%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <a href="javascript:void(0);" class="fw-bold d-block">
                                <span class="d-block">In Progress</span>
                                <span class="fs-24 fw-bolder d-block">06</span>
                            </a>
                            <div class="pt-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <a href="javascript:void(0);" class="fs-12 fw-medium text-muted">
                                        <span>Projects In Progress</span>
                                        <i class="feather-link-2 fs-10 ms-1"></i>
                                    </a>
                                    <div>
                                        <span class="fs-12 text-muted">16 Completed</span>
                                        <span class="fs-11 text-muted">(78%)</span>
                                    </div>
                                </div>
                                <div class="progress mt-2 ht-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 78%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <a href="javascript:void(0);" class="fw-bold d-block">
                                <span class="d-block">Cancelled</span>
                                <span class="fs-24 fw-bolder d-block">02</span>
                            </a>
                            <div class="pt-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <a href="javascript:void(0);" class="fs-12 fw-medium text-muted">
                                        <span>Converted Leads</span>
                                        <i class="feather-link-2 fs-10 ms-1"></i>
                                    </a>
                                    <div>
                                        <span class="fs-12 text-muted">52 Completed</span>
                                        <span class="fs-11 text-muted">(63%)</span>
                                    </div>
                                </div>
                                <div class="progress mt-2 ht-3">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 63%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <a href="javascript:void(0);" class="fw-bold d-block">
                                <span class="d-block">Finished</span>
                                <span class="fs-24 fw-bolder d-block">25</span>
                            </a>
                            <div class="pt-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <a href="javascript:void(0);" class="fs-12 fw-medium text-muted">
                                        <span>Conversion Rate</span>
                                        <i class="feather-link-2 fs-10 ms-1"></i>
                                    </a>
                                    <div>
                                        <span class="fs-12 text-muted">$2,254</span>
                                        <span class="fs-11 text-muted">(46%)</span>
                                    </div>
                                </div>
                                <div class="progress mt-2 ht-3">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 46%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full" style="border-radius: 0px">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover" id="projectList">
                                <thead>
                                    <tr>
                                        <th>NIN</th>
                                        <th>Patient</th>
                                        <th>Telephone</th>
                                        <th>Age</th>
                                        <th>Genre</th>
                                        <th>Date de creation</th>
                                        <th>Date de Modification</th>
                                        <th>Créer par</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($patients as $patient)
                                        <tr class="single-item">
                                            <td>{{ $patient->nin }}</td>
                                            <td class="project-name-td">
                                                <div class="hstack gap-4">
                                                    <div class="avatar-image border-0">
                                                        <img src="{{ $patient->avatar ?? 'assets/images/brand/app-store.png' }}"
                                                            alt="" class="img-fluid">
                                                    </div>
                                                    <div>
                                                        <a href="{{ route('patient.show', $patient->id) }}"
                                                            class="text-truncate-1-line">{{ $patient->prenom }}
                                                            {{ $patient->nom }}</a>
                                                        <p
                                                            class="fs-12 text-muted mt-2 text-truncate-1-line project-list-desc">
                                                            {{ $patient->type ?? 'patient standard' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $patient->telephone }}</td>
                                            <td>{{ $patient->age }} ans</td>
                                            <td>{{ $patient->genre }}</td>
                                            <td>{{ $patient->created_at->format('Y-m-d') }}</td>
                                            <td>{{ $patient->updated_at->format('Y-m-d') }}</td>
                                            <td>{{ $patient->creator->name ?? 'N/A' }}</td>
                                            <td>
                                                <div class="hstack gap-2 justify-content-end">
                                                    <a href="{{ route('patient.show', $patient->id) }}"
                                                        class="avatar-text avatar-md">
                                                        <i class="feather feather-eye"></i>
                                                    </a>
                                                    <div class="dropdown">
                                                        <a href="javascript:void(0)" class="avatar-text avatar-md"
                                                            data-bs-toggle="dropdown" data-bs-offset="0,21">
                                                            <i class="feather feather-more-horizontal"></i>
                                                        </a>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <a class="dropdown-item" href="">
                                                                    <i class="feather feather-edit-3 me-3"></i>
                                                                    <span>Edit</span>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item printBTN"
                                                                    href="javascript:void(0)">
                                                                    <i class="feather feather-printer me-3"></i>
                                                                    <span>Print</span>
                                                                </a>
                                                            </li>
                                                            <li class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" style="display:inline;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="dropdown-item"
                                                                        onclick="return confirm('Êtes-vous sûr?')">
                                                                        <i class="feather feather-trash-2 me-3"></i>
                                                                        <span>Delete</span>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">Aucun patient trouvé
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts-vendors')
    <script src="{{ asset('assets/vendors/js/dataTables.min.js') }}" defer></script>
    <script src="{{ asset('assets/vendors/js/dataTables.bs5.min.js') }}" defer></script>
    <script src="{{ asset('assets/vendors/js/select2.min.js') }}" defer></script>
    <script src="{{ asset('assets/vendors/js/select2-active.min.js') }}" defer></script>
@endpush

@push('scripts-page')
    <script src="{{ asset('assets/js/projects-init.min.js') }}" defer></script>
@endpush
