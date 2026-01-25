<div class="tab-pane fade active show" id="proposalTab">
    <div class="main-content bg-white shadow-sm sm:rounded-lg">
        <div class="row">
            <div class="col-xxl-4 col-xl-6">
                <div class="p-2">
                    <div class="mb-4 text-center">
                        <div class="wd-150 ht-150 mx-auto mb-3 position-relative">
                            <div class="avatar-image wd-150 ht-150 border border-5 border-gray-3">
                                <img src="assets/images/avatar/1.png" alt="" class="img-fluid">
                            </div>
                            <div class="wd-10 ht-10 text-success rounded-circle position-absolute translate-middle"
                                style="top: 76%; right: 10px">
                                <i class="bi bi-patch-check-fill"></i>
                            </div>
                        </div>
                        <div class="mb-4">
                            <a href="javascript:void(0);" class="fs-14 fw-bold d-block">{{ $patient->full_name }}</a>
                            <a href="javascript:void(0);"
                                class="fs-12 fw-normal text-muted d-block">{{ $patient->type_dossier }}</a>
                        </div>
                        <div class="fs-12 fw-normal text-muted text-center d-flex flex-wrap gap-3 mb-4">
                            <div
                                class="flex-fill py-3 px-4 rounded-1 d-none d-sm-block border border-dashed border-gray-5">
                                <h6 class="fs-15 fw-bolder">0</h6>
                                <p class="fs-12 text-muted mb-0">Consultations</p>
                            </div>
                            <div
                                class="flex-fill py-3 px-4 rounded-1 d-none d-sm-block border border-dashed border-gray-5">
                                <h6 class="fs-15 fw-bolder">0 PS</h6>
                                <p class="fs-12 text-muted mb-0">Point de santé</p>
                            </div>
                            <div
                                class="flex-fill py-3 px-4 rounded-1 d-none d-sm-block border border-dashed border-gray-5">
                                <h6 class="fs-15 fw-bolder">5 M</h6>
                                <p class="fs-12 text-muted mb-0">Dernier visite</p>
                            </div>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="hstack justify-content-between mb-4">
                            <span class="text-muted fw-medium hstack gap-3"><i
                                    class="feather-map-pin"></i>Location</span>
                            <a href="javascript:void(0);" class="float-end">{{ $patient->full_address }}</a>
                        </li>
                        <li class="hstack justify-content-between mb-4">
                            <span class="text-muted fw-medium hstack gap-3"><i class="feather-phone"></i>Phone</span>
                            <a href="javascript:void(0);" class="float-end">{{ $patient->telephone }}</a>
                        </li>
                        <li class="hstack justify-content-between mb-0">
                            <span class="text-muted fw-medium hstack gap-3"><i class="feather-mail"></i>Email</span>
                            <a href="javascript:void(0);" class="float-end">{{ $patient->email }}</a>
                        </li>
                    </ul>
                    <div class="d-flex gap-2 text-center pt-4">
                        <a href="javascript:void(0);" class="w-50 btn btn-light-brand">
                            <i class="feather-trash-2 me-2"></i>
                            <span>Delete</span>
                        </a>
                        <a href="javascript:void(0);" class="w-50 btn btn-primary">
                            <i class="feather-edit me-2"></i>
                            <span>Edit Profile</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xxl-8 col-xl-6">
                <div class="">
                    <ul class="nav nav-tabs flex-wrap w-100 text-center customers-nav-tabs" id="myTab"
                        role="tablist">
                        <li class="nav-item flex-fill" role="presentation">
                            <a href="javascript:void(0);" class="nav-link active" data-bs-toggle="tab"
                                data-bs-target="#overviewTab" role="tab">Informations</a>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <a href="javascript:void(0);" class="nav-link" data-bs-toggle="tab"
                                data-bs-target="#billingTab" role="tab">Contacts et Tuteurs</a>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <a href="javascript:void(0);" class="nav-link" data-bs-toggle="tab"
                                data-bs-target="#activityTab" role="tab">Alergie</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        @include('main.patients.include.show.profil.info', ['patient' => $patient])
                        @include('main.patients.include.show.profil.tuteur', ['patient' => $patient])
                        @include('main.patients.include.show.profil.allergie', ['patient' => $patient])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
