<?php

namespace App\Models;

use App\Enums\ClinicalMessageCategory;
use App\Enums\ClinicalMessagePriority;
use App\Models\Concerns\ScopesByHopital;
use App\Models\Configs\Hopital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageThread extends Model
{
    use ScopesByHopital;

    protected $fillable = [
        'hopital_id',
        'dossier_patient_id',
        'subject',
        'category',
        'priority',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => ClinicalMessageCategory::class,
            'priority' => ClinicalMessagePriority::class,
            'last_message_at' => 'datetime',
        ];
    }

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class);
    }

    public function dossierPatient(): BelongsTo
    {
        return $this->belongsTo(DossierPatient::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ClinicalMessage::class, 'thread_id');
    }
}
