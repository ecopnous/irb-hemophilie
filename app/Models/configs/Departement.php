<?php

namespace App\Models\Configs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    protected $fillable = [
        'name',
        'description',
        'color',
    ];

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function actes()
    {
        return $this->hasMany(Acte::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
