<?php

namespace App\Models\Configs;

use Illuminate\Database\Eloquent\Model;

class Projet extends Model
{
    protected $fillable = [
        'name',
        'reference',
        'description',
    ];
}
