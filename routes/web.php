<?php

use App\Http\Controllers\ClinicalMessageController;
use App\Http\Controllers\main\GroupeHopitauxController;
use App\Http\Controllers\FacturationPdfController;
use App\Http\Controllers\ImagerieBonPdfController;
use App\Http\Controllers\InventoryReportPdfController;
use App\Http\Controllers\LaboratoireBonPdfController;
use App\Http\Controllers\PatientDossierPdfController;
use App\Http\Controllers\AnalyticsExportController;
use App\Http\Controllers\ConsultationImportController;
use App\Http\Controllers\PatientImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('signed')->prefix('messagerie/patient')->name('messaging.patient.')->group(function () {
    Route::get('/piece/{attachment}', [ClinicalMessageController::class, 'downloadAttachment'])->name('attachment');
    Route::get('/{message}', [ClinicalMessageController::class, 'showPatientMessage'])->name('show');
});

Route::middleware(['auth', 'verified', 'grade.access'])->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('/profil', 'pages::profil')->name('profil');

    Route::prefix('reception')->name('reception.')->group(function () {
        Route::livewire('/papeterie', 'pages::reception.papeterie')->name('papeterie');
        Route::livewire('/services', 'pages::reception.services')->name('services');
    });
    Route::livewire('/analytics', 'pages::analytics')->name('analytics');
    Route::livewire('/messagerie', 'pages::messaging.inbox')->name('messaging.inbox');
    Route::get('/messagerie/piece-jointe/{attachment}', [ClinicalMessageController::class, 'downloadStaffAttachment'])
        ->name('messaging.attachment.download');
    Route::get('/analytics/export/excel', [AnalyticsExportController::class, 'excel'])->name('analytics.export.excel');
    Route::get('/analytics/export/pdf', [AnalyticsExportController::class, 'pdf'])->name('analytics.export.pdf');

    Route::get('heartbeat', function (Request $request) {
        $user = $request->user();

        abort_unless($user, 401);

        if (!$user->last_seen_at || $user->last_seen_at->lt(now()->subMinutes(4))) {
            $user->forceFill([
                'last_seen_at' => now(),
            ])->saveQuietly();
        }

        return response()->noContent();
    })->name('heartbeat');

    // Route::get('groupe-hopitaux/dashboard/{id}', [GroupeHopitauxController::class, 'dashboard'])->name('groupe-hopitaux.dashboard');
    // Route::resource('groupe-hopitaux', GroupeHopitauxController::class);
    // Route::resource('hopitaux', HopitauxController::class);


    Route::prefix('patient')->name('patient.')->group(function () {
        Route::livewire('/', 'pages::patient.index')->name('index');
        Route::livewire('/create', 'pages::patient.create')->name('create');
        Route::get('/import/template', [PatientImportController::class, 'template'])->name('import.template');
        Route::get('/import/{patientImport}/errors', [PatientImportController::class, 'errors'])->name('import.errors');
        Route::get('/profil/{id}/print', PatientDossierPdfController::class)->name('print');
        Route::livewire('/profil/{id}', 'pages::patient.profil.profil')->name('show');
        Route::livewire('/profil/{id}/new', 'pages::patient.profil.initialisation')->name('init_consult');
        Route::livewire('/profil/{id}/fiche-medicale', 'pages::patient.profil.fiche_medicale')->name('fiche_medicale');
        Route::livewire('/profil/{id}/boite-reception', 'pages::patient.profil.health_wallet')->name('inbox');
        Route::livewire('/profil/{id}/evolution', 'pages::patient.profil.evolution')->name('evolution');
        Route::livewire('/profil/{id}/archivages', 'pages::patient.profil.archivages')->name('archivages');
        Route::post('/profil/{id}/archivages/upload', [\App\Http\Controllers\PatientArchiveUploadController::class, 'store'])
            ->name('archivages.upload');
        Route::get('/profil/{id}/archivages/{document}/telecharger', [\App\Http\Controllers\PatientArchiveDocumentController::class, 'download'])
            ->name('archivages.download');
    });

    Route::prefix('consultation')->name('consultation.')->group(function () {
        Route::livewire('/', 'pages::consultation.index')->name('index');
        Route::get('/import/template', [ConsultationImportController::class, 'template'])->name('import.template');
        Route::get('/import/{consultationImport}/errors', [ConsultationImportController::class, 'errors'])->name('import.errors');
        Route::livewire('/show/{id}', 'pages::consultation.show')->name('show');
        Route::livewire('/triage', 'pages::consultation.triage')->name('triage');
        Route::livewire('/prelevement/{id}', 'pages::consultation.prelevement')->name('prelevement');
        Route::livewire('/historique/{id}', 'pages::patient.profil.consultation_historique')->name('historique');
        Route::livewire('/facture/{id}', 'pages::patient.profil.historique_facture')->name('facture');
    });

    Route::prefix('laboratoire')->name('laboratoire.')->group(function () {
        Route::livewire('/', 'pages::labo.index')->name('index');
        Route::livewire('/rapports', 'pages::labo.rapport')->name('rapport');
        Route::livewire('/valeurs-exactes', 'pages::labo.valeurs_exactes')->name('valeurs_exactes');
        Route::livewire('/stock', 'pages::labo.stock')->name('stock');
        Route::livewire('/mouvements-stock', 'pages::labo.stock_movements')->name('stock_movements');
        Route::livewire('/groupes', 'pages::labo.groupes.index')->name('groupes.index');
        Route::livewire('/groupes/create', 'pages::labo.groupes.create')->name('groupes.create');
        Route::livewire('/groupes/show/{id}', 'pages::labo.groupes.show')->name('groupes.show');
        Route::get('/show/{id}/print', LaboratoireBonPdfController::class)->name('print');
        Route::livewire('/show/{id}', 'pages::labo.show')->name('show');
    });

    Route::prefix('facturation')->name('facturation.')->group(function () {
        Route::livewire('/dashboard', 'pages::facturation.dashboard')->name('dashboard');
        Route::livewire('/', 'pages::facturation.index')->name('index');
        Route::get('/show/{id}/pdf', FacturationPdfController::class)->name('pdf');
        Route::livewire('/documents', 'pages::facturation.documents_list')->name('documents');
        Route::livewire('/documents/create/{type?}', 'pages::facturation.documents_form')->name('documents.create');
        Route::livewire('/documents/edit/{id}', 'pages::facturation.documents_form')->name('documents.edit');
        Route::livewire('/show/{id}', 'pages::facturation.show')->name('show');
        Route::livewire('/paiements', 'pages::facturation.payments')->name('payments');
        Route::livewire('/tarifs', 'pages::facturation.tariffs')->name('tariffs');
        Route::livewire('/caisse', 'pages::facturation.cash')->name('cash');
        Route::livewire('/inventaire', 'pages::facturation.inventory')->name('inventory');
        Route::livewire('/inventaire/create', 'pages::facturation.inventory')->name('inventory.create');
        Route::livewire('/clients', 'pages::facturation.clients')->name('clients');
        Route::get('/inventaire/rapport/pdf', InventoryReportPdfController::class)->name('inventory.report.pdf');

        Route::prefix('assurance')->name('assurance.')->group(function () {
            Route::livewire('/', 'pages::facturation.assurance.index')->name('index');
            Route::livewire('/{id}', 'pages::facturation.assurance.show')->name('show');
            Route::livewire('/{id}/facture', 'pages::facturation.assurance.invoice')->name('invoice');
        });
    });

    Route::prefix('imagerie')->name('imagerie.')->group(function () {
        Route::livewire('/', 'pages::imagerie.index')->name('index');
        Route::get('/show/{id}/print', ImagerieBonPdfController::class)->name('print');
        Route::livewire('/show/{id}', 'pages::imagerie.show')->name('show');
    });

    Route::prefix('pharmacie')->name('pharmacie.')->group(function () {
        Route::livewire('/', 'pages::pharmacy.dashboard')->name('dashboard');
        Route::livewire('/pharmacies', 'pages::pharmacy.pharmacies')->name('pharmacies');
        Route::livewire('/medicaments', 'pages::pharmacy.medicaments')->name('medicaments');
        Route::livewire('/stock', 'pages::pharmacy.stock')->name('stock');
        Route::livewire('/mouvements', 'pages::pharmacy.movements')->name('movements');
        Route::livewire('/prescriptions', 'pages::pharmacy.prescriptions')->name('prescriptions');
        Route::livewire('/depreciations', 'pages::pharmacy.depreciations')->name('depreciations');
    });

    Route::prefix('hospitalisation')->name('hospital.')->group(function () {
        Route::livewire('reception', 'pages::hospitalisation.reception.index')->name('index');
        Route::livewire('facturation', 'pages::hospitalisation.reception.facturation')->name('facturation');
        Route::livewire('configuration', 'pages::settings.hospitalisation.index')->name('configuration');
        Route::livewire('configuration/{id}', 'pages::settings.hospitalisation.show')->name('configuration.show');
    });

    Route::prefix('groupe')->name('groupe_hopitaux.')->group(function () {
        Route::livewire('/', 'pages::groupe_hopitaux.index')->name('index');
        Route::livewire('/show/{id}', 'pages::groupe_hopitaux.show')->name('show');
        Route::livewire('/create', 'pages::groupe_hopitaux.create')->name('create');
        // Route::livewire('dashboard/{id}', 'pages::groupe_hopitaux.index')->name('dashboard');
    });
});

Route::get('/storage/{path}', function (string $path) {
    abort_unless(\Illuminate\Support\Facades\Storage::disk('public')->exists($path), 404);

    return \Illuminate\Support\Facades\Storage::disk('public')->response($path);
})->where('path', '.*')->name('storage.public');

require __DIR__ . '/settings.php';
