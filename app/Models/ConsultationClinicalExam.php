<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationClinicalExam extends Model
{
    protected $fillable = [
        'consultation_id',
        'examined_at',
        'synthesis',
        'filled_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'examined_at' => 'date',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function filledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filled_by_user_id');
    }
}
