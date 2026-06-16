<?php

namespace App\Models\Configs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Departement extends Model
{
    protected $fillable = [
        'name',
        'description',
        'color',
        'user_id',
    ];

    public function chef(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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
