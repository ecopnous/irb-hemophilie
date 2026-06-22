<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RendezVous extends Model
{
    protected $table = 'rendez_vous';

    protected $fillable = [
        'doctor_id',
        'dossier_patient_id',
        'patient_name',
        'date_rendez_vous',
        'rappel_48h_envoye',
        'rappel_patient_48h_envoye',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_rendez_vous' => 'datetime',
            'rappel_48h_envoye' => 'boolean',
            'rappel_patient_48h_envoye' => 'boolean',
        ];
    }

    public function dossierPatient(): BelongsTo
    {
        return $this->belongsTo(DossierPatient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
