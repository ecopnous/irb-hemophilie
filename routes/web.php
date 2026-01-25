<?php

use App\Http\Controllers\main\ConsultationController;
use App\Http\Controllers\main\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\main\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Routes CRUD pour les patients
    Route::get("/patients", [PatientController::class, 'all'])->name("patient.all");
    Route::get("/patients-{id}", [PatientController::class, 'show'])->name("patient.show");
    Route::get("/create-patient", [PatientController::class, 'create'])->name("patient.create");
    Route::post("/store-patient", [PatientController::class, 'store'])->name("patient.store");

    Route::get("consultation-initial", [ConsultationController::class, "create"])->name("consultation.create");

    Route::get("/setting-general", [SettingsController::class, 'general'])->name("settings.general");
    Route::get("/setting-departement", [SettingsController::class, 'departement'])->name("settings.departement");
});

require __DIR__.'/auth.php';
