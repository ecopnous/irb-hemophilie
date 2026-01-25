<?php

namespace App\Models\globals;

use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    protected $fillable = [
        'name',
        'description',
        'color',
    ];
}
