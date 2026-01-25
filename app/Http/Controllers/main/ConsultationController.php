<?php

namespace App\Http\Controllers\main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function create()
    {
        return view("main.consultations.create");
    }

    public function prelevement()
    {
        return view("main.prelevement.create");
    }
}
