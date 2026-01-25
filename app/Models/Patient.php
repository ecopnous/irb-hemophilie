<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'photo',
        'nom',
        'postnom',
        'prenom',
        'genre',
        'etat_civil',
        'telephone',
        'email',
        'date_naissance',
        'nationalite',
        'province',
        'territoire',
        'commune',
        'quartier',
        'numero_habitation',
        'langues',
        'type_dossier',
        'categorisation',
        'prise_en_charge',
        'ins',
        'note',
    ];

    protected $casts = [
        'langues' => 'array',
        'date_naissance' => 'date',
    ];

    /**
     * Récupère le nom complet du patient
     */
    public function getFullNameAttribute()
    {
        return trim("{$this->nom} {$this->postnom} {$this->prenom}");
    }

    /**
     * Récupère le nom complet du patient
     */
    public function getFullAddressAttribute()
    {
        return trim("N°{$this->numero_habitation}, Av. {$this->avenue} Q: {$this->quartier} C: {$this->commune}");
    }

    /**
     * Récupère l'année de naissance
     */
    public function getAgeAttribute()
    {
        if (!$this->date_naissance) {
            return null;
        }
        $diff = $this->date_naissance->diff(now());
        return "{$diff->y} ans et {$diff->m} mois";
    }

    /**
     * Récupère la date de naissance au format "20 Dec, 2023"
     */
    public function getFormattedBirthdateAttribute()
    {
        return $this->date_naissance ? $this->date_naissance->format('d M, Y') : null;
    }

    protected static function booted()
    {
        static::creating(function ($patient) {
            $latest = self::latest()->first();
            $number = $latest ? $latest->id + 1 : 1;

            $patient->reference = 'NIN-' . date('y') . "E-" . str_pad($number, 5, '0', STR_PAD_LEFT) . "";
        });
    }
}
