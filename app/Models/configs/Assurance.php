<?php

namespace App\Models\Configs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assurance extends Model
{
    protected $fillable = [
        'reference',
        'name',
        'description',
        'email',
        'type',
        'logo',
        'categorisation_id',
    ];

    public function categorisation(): BelongsTo
    {
        return $this->belongsTo(Categorisation::class);
    }
}
