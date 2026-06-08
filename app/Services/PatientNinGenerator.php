<?php

namespace App\Services;

use App\Models\DossierPatient;
use Illuminate\Support\Facades\Cache;

class PatientNinGenerator
{
    public function create(array $attributes): DossierPatient
    {
        return Cache::lock('dossier-patient-nin', 30)->block(10, function () use ($attributes) {
            if (empty($attributes['nin'])) {
                $latestId = DossierPatient::query()->orderByDesc('id')->value('id');
                $number = ($latestId ?? 0) + 1;

                $attributes['nin'] = 'NIN-' . date('y') . $attributes['genre'] . '-' . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
            }

            return DossierPatient::withoutEvents(function () use ($attributes) {
                $patient = new DossierPatient;
                $patient->forceFill($attributes);
                $patient->save();

                return $patient;
            });
        });
    }
}
