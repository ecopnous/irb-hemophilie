<?php

namespace App\Models\Configs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'departement_id',
    ];

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }
}
