@extends('main.settings.includes.settings.nav-setting')

@php
    $n = 1;
@endphp

@section('settings-content')
    <div class="content-area bg-white" data-scrollbar-target="#psScrollbarInit">
        <div class="content-area-header bg-white sticky-top">
            <div class="page-header-right ms-auto">
                <a href="javascript:void(0);" class="btn btn-primary successAlertMessage">
                    <i class="feather-add me-2"></i>
                    <span>Nouvelle departement</span>
                </a>
            </div>
        </div>
        <div class="content-area-body p-0">
            <div class="table">
                <table class="table table-hover table-striped" id="proposalList">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Date de creation</th>
                            <th>Date de modification</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($departements as $departement)
                            <tr class="single-item">
                                <td>{{ $n }}</td>
                                <td>{{ $departement->name }}</td>
                                <td>{{ $departement->description }}</td>
                                <td>{{ $departement->created_at }}</td>
                                <td>{{ $departement->updated_at }}</td>
                            </tr>
                            @php
                                $n++;
                            @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
