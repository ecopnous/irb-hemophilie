<?php

namespace App\Models\liaison;

use App\Models\Laboratoire;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    protected $fillable = [
        'name',
        'path',
        'laboratoire_id',
        'acte_consultation_id',
    ];

    public function laboratoire(): BelongsTo
    {
        return $this->belongsTo(Laboratoire::class);
    }

    public function acteConsultation(): BelongsTo
    {
        return $this->belongsTo(ActeConsultation::class);
    }

    public function url(): string
    {
        return '/storage/' . ltrim(str_replace('\\', '/', $this->path), '/');
    }
}
