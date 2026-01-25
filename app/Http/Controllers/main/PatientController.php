<?php

namespace App\Http\Controllers\main;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function all()
    {
        $patients = Patient::all();
        return view("main.patients.all", compact('patients'));
    }

    public function create()
    {
        return view("main.patients.create");
    }

    public function show($id)
    {
        $patient = Patient::findOrFail($id);
        return view("main.patients.show", compact("patient"));
    }

    /**
     * Stocker un nouveau patient dans la base de données
     */
    public function store(Request $request)
    {
        // Valider les données
        // $validated = $request->validate([
        //     'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        //     'nom' => 'required|string|max:255',
        //     'postnom' => 'nullable|string|max:255',
        //     'prenom' => 'required|string|max:255',
        //     'genre' => 'required|in:M,F',
        //     'etat_civil' => 'required|in:Célibataire,Marié,Divorcé',
        //     'telephone' => 'nullable|string|max:20',
        //     'email' => 'nullable|email|unique:patients,email|max:255',
        //     'date_naissance' => 'required|date|before:today',
        //     'nationalite' => 'required|string|max:255',
        //     'province' => 'required|string|max:255',
        //     'territoire' => 'required|string|max:255',
        //     'commune' => 'required|string|max:255',
        //     'quartier' => 'nullable|string|max:255',
        //     'numero_habitation' => 'nullable|string|max:255',
        //     'langues' => 'nullable|array',
        //     'note' => 'nullable|string|max:1000',
        // ], [
        //     'nom.required' => 'Le nom est obligatoire.',
        //     'prenom.required' => 'Le prénom est obligatoire.',
        //     'genre.required' => 'Le genre est obligatoire.',
        //     'etat_civil.required' => "L'état civil est obligatoire.",
        //     'date_naissance.required' => 'La date de naissance est obligatoire.',
        //     'date_naissance.before' => 'La date de naissance doit être dans le passé.',
        //     'email.unique' => 'Cet email est déjà utilisé.',
        //     'photo.image' => 'Le fichier doit être une image.',
        //     'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
        // ]);

        // // Traiter le fichier photo
        // if ($request->hasFile('photo')) {
        //     $photoPath = $request->file('photo')->store('patients/photos', 'public');
        //     $validated['photo'] = $photoPath;
        // }

        // // Créer le patient
        // $patient = Patient::create($validated);

        // return redirect()
        //     ->route('patients.show', $patient)
        //     ->with('success', 'Dossier médical créé avec succès!');
    }
}
