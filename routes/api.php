<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Assurance;
use App\Models\Configs\Categorisation;
use App\Models\Configs\Departement;
use App\Models\Configs\PacquetSoin;
use App\Models\Configs\Projet;
use App\Models\Configs\Service;
use App\Models\DossierPatient;
use App\Models\Localisations\Country;
use App\Models\Localisations\Province;
use App\Models\other\Symptome;
use App\Models\other\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::name('api.')->group(function () {

    Route::get('/categorisations', function (Request $request) {
        return Categorisation::all(['id', 'name']);
    })->name('categorisations');




    // Route::prefix('')->group(function(){});
    Route::get('/patient', function (Request $request) {
        $term = $request->search;

        // Si aucun terme n'est saisi, on renvoie une collection vide
        if (!$term || strlen($term) < 3) {
            return response()->json([]);
        }

        return DossierPatient::query()
            ->when($request->hopital_id, fn($q) => $q->where('hopital_id', $request->hopital_id))
            ->where(function ($q) use ($term) {
                $q->where('nin', 'like', "%{$term}%")
                    ->orWhere('ins', 'like', "%{$term}%")
                    ->orWhere('nom', 'like', "%{$term}%")
                    ->orWhere('postnom', 'like', "%{$term}%")
                    ->orWhere('prenom', 'like', "%{$term}%");
            })
            ->orderBy('nom')
            ->orderBy('postnom')
            ->orderBy('prenom')
            ->limit(10)
            ->get(['id', 'nom', 'postnom', 'prenom', 'nin', 'photo'])
            ->map(fn($p) => [
                'id' => $p->id,
                'image' => $p->photo ? Storage::disk('public')->url($p->photo) : 'https://ui-avatars.com/api/?background=6366f1&color=fff&name=' . $p->prenom . '+' . $p->nom,
                'name' => trim(strtoupper((string) $p->nom) . ' ' . strtoupper((string) $p->postnom) . ' ' . ucfirst((string) $p->prenom)),
                'description' => $p->nin
            ]);
    })->name('patient');

    Route::prefix('configs')->group(function () {
        // retourne les utilisateurs (corps medical) de l'hopital concerner
        Route::get('/user-in', function (Request $request) {
            return User::query()
                ->when($request->hopital_id, fn($q) => $q->where('hopital_id', $request->hopital_id))
                ->get(['id', 'name']);
        })->name('usersIn');

        Route::get('/user-connected', function (Request $request) {
            return User::query()
                ->when($request->hopital_id, fn($q) => $q->where('hopital_id', $request->hopital_id))
                ->get(['id', 'name']);
        })->name('usersConnected');

        Route::get('/assurances', function (Request $request) {
            return Assurance::all(['id', 'name']);
        })->name('assurances');

        Route::get('/projets', function (Request $request) {
            return Projet::all(['id', 'name']);
        })->name('projets');

        Route::get('/pacquet-soins', function (Request $request) {
            return PacquetSoin::query()
                ->has('actes')
                ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->orderBy('name')
                ->get(['id', 'name']);
        })->name('pacquetSoins');

        Route::get('/departements', function (Request $request) {
            return Departement::all(['id', 'name']);
        })->name('departements');

        Route::get('/services', function (Request $request) {
            return Service::query()
                ->when($request->departement, fn($q) => $q->where('departement_id', $request->departement))
                ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->get(['id', 'name']);
        })->name('services');

        Route::get('/actes', function (Request $request) {
            return Acte::query()
                ->when($request->departement, fn($q) => $q->where('departement_id', $request->departement))
                ->when($request->service, fn($q) => $q->where('service_id', $request->service))
                ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->get(['id', 'name']);
        })->name('actes');
    });

    Route::prefix('localisations')->group(function () {
        Route::get('/countries', function (Request $request) {
            return Country::all(['id', 'name']);
        })->name('countries');

        Route::get('/provinces', function (Request $request) {
            return Province::all(['id', 'name']);
        })->name('provinces');

        Route::get('/villes', function (Request $request) {
            return \App\Models\Localisations\Ville::query()
                ->when($request->province, fn($q) => $q->where('province_id', $request->province))
                ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->get(['id', 'name']);
        })->name('villes');

        Route::get('/communes', function (Request $request) {
            return \App\Models\Localisations\Commune::query()
                ->when($request->ville, fn($q) => $q->where('ville_id', $request->ville))
                ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->get(['id', 'name']);
        })->name('communes');
    });




    // retourne les tags des santés
    Route::get('/tags', function (Request $request) {
        return Tag::all(['id', 'name']);
    })->name('tags');
    Route::get('/symptomes', function (Request $request) {
        return Symptome::all(['id', 'name']);
    })->name('symptomes');
});
