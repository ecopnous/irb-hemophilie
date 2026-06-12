<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Assurance;
use App\Models\Configs\Categorisation;
use App\Models\Configs\Departement;
use App\Models\Configs\PacquetSoin;
use App\Models\Configs\Projet;
use App\Models\Configs\Service;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\prescription\Prescription;
use App\Models\facturation\FinanceClient;
use App\Models\prescription\Medicament;
use App\Models\prescription\Pharmacie;
use App\Models\Localisations\Country;
use App\Models\Localisations\Province;
use App\Models\other\Symptome;
use App\Models\other\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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
                // Le problème est que Storage::disk('public') n'a pas de méthode url en contexte API.
                // Utilisez plutôt asset('storage/' . $p->photo) pour générer l'URL publique correcte.
                'image' => $p->photo ? asset('storage/' . $p->photo) : 'https://ui-avatars.com/api/?background=6366f1&color=fff&name=' . $p->prenom . '+' . $p->nom,
                'name' => trim(strtoupper((string) $p->nom) . ' ' . strtoupper((string) $p->postnom) . ' ' . ucfirst((string) $p->prenom)),
                'description' => $p->nin
            ]);
    })->name('patient');

    Route::get('/clients', function (Request $request) {
        $term = $request->search;

        if (! $term || strlen($term) < 2) {
            return response()->json([]);
        }

        return FinanceClient::query()
            ->where('hopital_id', current_hopital_id())
            ->where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('tax_id', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'phone', 'email', 'type'])
            ->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->name,
                'description' => collect([$client->phone, $client->email, $client->type === 'institution' ? 'Institution' : 'Particulier'])
                    ->filter()
                    ->implode(' · '),
            ]);
    })->name('clients');

    Route::get('/medicaments', function (Request $request) {
        $hopitalId = current_hopital_id();
        $term = $request->search;

        return Medicament::query()
            ->where('is_active', true)
            ->whereHas('pharmacies', fn ($q) => $q->where('hopital_id', $hopitalId))
            ->when($term, function ($q) use ($term) {
                $like = '%' . $term . '%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('reference', 'like', $like)
                        ->orWhere('dci', 'like', $like);
                });
            })
            ->with(['pharmacies' => fn ($q) => $q->where('hopital_id', $hopitalId)])
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(function (Medicament $medicament) {
                $stock = (int) $medicament->pharmacies->sum(fn ($pharmacy) => (int) $pharmacy->pivot->quantiter);
                $price = (float) ($medicament->pharmacies
                    ->first(fn ($pharmacy) => (float) $pharmacy->pivot->montant > 0)?->pivot->montant ?? 0);

                return [
                    'id' => $medicament->id,
                    'name' => $medicament->name,
                    'description' => collect([
                        $medicament->reference,
                        'Stock: ' . $stock,
                        $price > 0 ? number_format($price, 2, ',', ' ') . ' USD' : null,
                    ])->filter()->implode(' · '),
                ];
            });
    })->name('medicaments');

    Route::get('/consultations', function (Request $request) {
        $hopitalId = (int) ($request->hopital_id ?: current_hopital_id());

        if ($hopitalId <= 0) {
            return response()->json([]);
        }

        $term = $request->search;

        return Consultation::query()
            ->with('dossierPatient')
            ->where('hopital_id', $hopitalId)
            ->when($term, function ($q) use ($term) {
                $like = '%' . $term . '%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('reference', 'like', $like)
                        ->orWhereHas('dossierPatient', function ($patient) use ($like) {
                            $patient->where('nom', 'like', $like)
                                ->orWhere('postnom', 'like', $like)
                                ->orWhere('prenom', 'like', $like)
                                ->orWhere('nin', 'like', $like);
                        });
                });
            })
            ->latest('created_at')
            ->limit(40)
            ->get()
            ->map(fn (Consultation $consultation) => [
                'id' => $consultation->id,
                'name' => $consultation->reference . ' — ' . ($consultation->dossierPatient?->full_name ?? 'Patient'),
                'description' => optional($consultation->created_at)->format('d/m/Y H:i'),
            ])
            ->values();
    })->name('consultations');

    Route::get('/prescriptions-pending', function (Request $request) {
        $hopitalId = (int) ($request->hopital_id ?: current_hopital_id());

        if ($hopitalId <= 0) {
            return response()->json([]);
        }

        $term = $request->search;

        return Prescription::query()
            ->with(['dossierPatient', 'medicaments'])
            ->where('hopital_id', $hopitalId)
            ->whereIn('status', ['draft', 'partial'])
            ->when($term, function ($q) use ($term) {
                $like = '%' . $term . '%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('reference', 'like', $like)
                        ->orWhereHas('dossierPatient', function ($patient) use ($like) {
                            $patient->where('nom', 'like', $like)
                                ->orWhere('postnom', 'like', $like)
                                ->orWhere('prenom', 'like', $like);
                        });
                });
            })
            ->latest('created_at')
            ->limit(40)
            ->get()
            ->map(function (Prescription $prescription) {
                $remaining = $prescription->medicaments->sum(
                    fn ($medicament) => max(0, (int) $medicament->pivot->nbr - (int) $medicament->pivot->qte_servie)
                );

                return [
                    'id' => $prescription->id,
                    'name' => ($prescription->reference ?: 'PRES-' . $prescription->id)
                        . ' — ' . ($prescription->dossierPatient?->full_name ?? 'Patient'),
                    'description' => $remaining . ' unite(s) a servir · ' . optional($prescription->created_at)->format('d/m/Y H:i'),
                ];
            })
            ->values();
    })->name('prescriptions-pending');

    Route::get('/pharmacies', function (Request $request) {
        $hopitalId = (int) ($request->hopital_id ?: current_hopital_id());

        if ($hopitalId <= 0) {
            return response()->json([]);
        }

        $term = $request->search;

        return Pharmacie::query()
            ->where('hopital_id', $hopitalId)
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->when($term, fn ($q) => $q->where('nom', 'like', '%' . $term . '%'))
            ->orderBy('nom')
            ->limit(50)
            ->get(['id', 'nom'])
            ->map(fn (Pharmacie $pharmacie) => [
                'id' => $pharmacie->id,
                'name' => $pharmacie->nom,
            ])
            ->values();
    })->name('pharmacies');

    Route::get('/pharmacy-medicaments', function (Request $request) {
        $hopitalId = (int) ($request->hopital_id ?: current_hopital_id());
        $pharmacieId = (int) $request->input('pharmacie_id');

        if ($hopitalId <= 0 || $pharmacieId <= 0) {
            return response()->json([]);
        }

        $pharmacie = Pharmacie::query()
            ->where('hopital_id', $hopitalId)
            ->find($pharmacieId);

        if (! $pharmacie) {
            return response()->json([]);
        }

        $term = $request->search;

        return $pharmacie->medicaments()
            ->when($term, function ($q) use ($term) {
                $like = '%' . $term . '%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('medicaments.name', 'like', $like)
                        ->orWhere('medicaments.reference', 'like', $like)
                        ->orWhere('medicaments.dci', 'like', $like);
                });
            })
            ->orderBy('medicaments.name')
            ->limit(50)
            ->get()
            ->map(function (Medicament $medicament) {
                $stock = (int) $medicament->pivot->quantiter;

                return [
                    'id' => $medicament->id,
                    'name' => $medicament->name,
                    'description' => collect([
                        $medicament->reference,
                        'Stock: ' . $stock,
                    ])->filter()->implode(' · '),
                    'stock' => $stock,
                ];
            })
            ->values();
    })->name('pharmacy-medicaments');

    Route::prefix('configs')->group(function () {
        // retourne les utilisateurs (corps medical) de l'hopital concerner
        Route::get('/user-in', function (Request $request) {
            return User::query()
                ->when($request->hopital_id, fn($q) => $q->where('hopital_id', $request->hopital_id))
                ->get(['id', 'name']);
        })->name('usersIn');

        Route::get('/user-connected', function (Request $request) {
            $term = $request->search;
            $hopitalId = (int) ($request->hopital_id ?: current_hopital_id());

            if (! $term || strlen($term) < 2) {
                return response()->json([]);
            }

            if ($hopitalId <= 0) {
                return response()->json([]);
            }

            $departementId = (int) $request->departement_id;

            return User::query()
                ->where('hopital_id', $hopitalId)
                ->where('grade', 'medecin')
                ->where(function ($query) use ($term) {
                    $like = '%' . $term . '%';
                    $query->where('name', 'like', $like)
                        ->orWhere('prenom', 'like', $like)
                        ->orWhere('post_nom', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('matricule', 'like', $like)
                        ->orWhere('cnom', 'like', $like);
                })
                ->with('departement:id,name')
                ->when(
                    $departementId > 0,
                    fn ($query) => $query->orderByRaw('CASE WHEN departement_id = ? THEN 0 ELSE 1 END', [$departementId])
                )
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name', 'prenom', 'post_nom', 'email', 'matricule', 'departement_id', 'cnom'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => trim(collect([$user->name, $user->prenom])->filter()->implode(' ')),
                    'description' => collect([
                        $user->departement?->name,
                        $user->matricule,
                        $user->cnom ? 'CNOM ' . $user->cnom : null,
                    ])->filter()->implode(' · '),
                    'image' => 'https://ui-avatars.com/api/?background=0ea5e9&color=fff&name=' . urlencode($user->name ?? 'DR'),
                ]);
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

        Route::get('/groupe-examens', function (Request $request) {
            return \App\Models\Configs\GroupeExamen::query()
                ->active()
                ->has('actes')
                ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->orderBy('name')
                ->get(['id', 'name']);
        })->name('groupeExamens');

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
                ->where(function ($q) {
                    $q->where('is_active', true)->orWhereNull('is_active');
                })
                ->orderBy('name')
                ->limit(50)
                ->get(['id', 'name', 'base_price', 'montant', 'code'])
                ->map(fn ($acte) => [
                    'id' => $acte->id,
                    'name' => $acte->name,
                    'description' => collect([
                        $acte->code,
                        number_format((float) ($acte->base_price ?? $acte->montant ?? 0), 2, ',', ' ') . ' USD',
                    ])->filter()->implode(' · '),
                ]);
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
