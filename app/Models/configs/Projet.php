<?php

namespace App\Models\Configs;

use App\Models\Consultation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Projet extends Model
{
    protected $fillable = [
        'name',
        'reference',
        'description',
        'assurance_id',
    ];

    public function assurance(): BelongsTo
    {
        return $this->belongsTo(Assurance::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }
}
