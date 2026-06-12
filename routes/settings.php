<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified', 'grade.access'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('security.edit');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::prefix('hopital')->name('hopital.')->group(function () {
            Route::livewire('/', 'pages::settings.hopitaux.index')->name('index');
            Route::livewire('/create', 'pages::settings.hopitaux.create')->name('create');
            Route::livewire('/show/{id}', 'pages::settings.hopitaux.show')->name('show');
        });

        Route::prefix('assurance')->name('assurance.')->group(function () {
            Route::livewire('/', 'pages::settings.assurance.index')->name('index');
            Route::livewire('/create', 'pages::settings.assurance.create')->name('create');
            Route::livewire('/show/{id}', 'pages::settings.assurance.show')->name('show');
        });

        Route::prefix('user')->name('user.')->group(function () {
            Route::livewire('/', 'pages::settings.user.index')->name('index');
            Route::livewire('/create', 'pages::settings.user.create')->name('create');
            Route::livewire('/show/{id}', 'pages::settings.user.show')->name('show');
        });

        Route::prefix('departement')->name('departement.')->group(function () {
            Route::livewire('/', 'pages::settings.departement.index')->name('index');
            Route::livewire('/show/{id}', 'pages::settings.departement.show')->name('show');
            Route::livewire('/{id}/new-acte', 'pages::settings.departement.new_acte')->name('newActe');
        });

        Route::prefix('categorisation')->name('categorisation.')->group(function () {
            Route::livewire('/', 'pages::settings.categorisation.index')->name('index');
            Route::livewire('/create', 'pages::settings.categorisation.create')->name('create');
            Route::livewire('/show/{id}', 'pages::settings.categorisation.show')->name('show');
        });

        Route::prefix('paquet')->name('paquet.')->group(function () {
            Route::livewire('/', 'pages::settings.pacquet_soins.index')->name('index');
            Route::livewire('/create', 'pages::settings.pacquet_soins.create')->name('create');
            Route::livewire('/show/{id}', 'pages::settings.pacquet_soins.show')->name('show');
        });

        Route::prefix('projet')->name('projet.')->group(function () {
            Route::livewire('/', 'pages::settings.projets.index')->name('index');
            Route::livewire('/create', 'pages::settings.projets.create')->name('create');
            Route::livewire('/show/{id}', 'pages::settings.projets.show')->name('show');
        });

        Route::prefix('vaccin')->name('vaccin.')->group(function () {
            Route::livewire('/', 'pages::settings.vaccins.index')->name('index');
            Route::livewire('/create', 'pages::settings.vaccins.create')->name('create');
            Route::livewire('/show/{id}', 'pages::settings.vaccins.show')->name('show');
        });

        Route::prefix('hospitalisation')->name('hospitalisation.')->group(function () {
            Route::livewire('/', 'pages::settings.hospitalisation.index')->name('index');
            Route::livewire('/show/{id}', 'pages::settings.hospitalisation.show')->name('show');
        });
    });

    Route::prefix('groupe-hopital')->name('groupe_hopital.')->group(function () {
        Route::livewire('/', 'pages::settings.hopitaux.index')->name('index');
        Route::livewire('/create', 'pages::settings.hopitaux.create')->name('create');
        Route::livewire('/show/{id}', 'pages::settings.hopitaux.show')->name('show');
    });
});
