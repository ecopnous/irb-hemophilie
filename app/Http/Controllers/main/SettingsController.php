<?php

namespace App\Http\Controllers\main;

use App\Http\Controllers\Controller;
use App\Models\globals\Assurance;
use App\Models\globals\Departement;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function general()
    {
        session(['settings_nav' => 'general']);
        $departements = Departement::orderBy('name', 'asc')->get();
        $assurances = Assurance::orderBy('name', 'asc')->get();
        return view("main.settings.setting-general", compact('departements', 'assurances'));
    }

    public function departement(){
        session(['settings_nav' => 'departement']);
        $departements = Departement::orderBy('name', 'asc')->get();
        return view("main.settings.depatement.setting-departement", compact('departements'));
    }
}
