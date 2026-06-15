<?php

namespace App\Models;

use App\Models\Configs\Assurance;
use App\Models\Localisations\Commune;
use App\Models\Localisations\Province;
use App\Models\Concerns\ScopesByHopital;
use App\Models\hospitalisation\Hospitalisation;
use App\Models\Localisations\Ville;
use App\Models\other\Tag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DossierPatient extends Model
{
    use HasFactory;
    use ScopesByHopital;

    protected $fillable = [
        'nin',
        'photo',
        'nom',
        'postnom',
        'prenom',
        'email',
        'telephone',
        'ins',
        'genre',
        'etat_civil',
        'date_naissance',

        'poids_naissance',
        'note',

        'province_id',
        'ville_id',
        'commune_id',
        'country_id',
        'quartier',
        'avenue',
        'num_habitation',
        'adresses_supplementaires',

        'nom_pere',
        'nom_mere',
        'province_pere',
        'tribut_pere',
        'profession_pere',
        'province_mere',
        'tribut_mere',
        'profession_mere',

        'type_famille',
        'rang_fratrie',
        'nb_freres',
        'nb_soeurs',
        'deces_freres',
        'deces_soeurs',
        'histoire_famille_supplementaire',

        'age_gestationnel',
        'allaitement_maternel',
        'med_traditionnel',
        'moringa_oleifera',
        'indications',
        'duree_prise',
        'vaccins',
        'histoire_perso_supplementaire',

        'syndrome_mains_pieds',
        'fievre',
        'itere',
        'cvo',
        'transfusion',
        'nbr_transfusion',
        'episodes_epistaxis',
        'nbr_cvo_an',
        'premier_signes_supplementaires',

        'antecedents_medicales',
        'antecedents_chirurgicaux',
        'antecedents_familiaux',
        'antecedents_obstetricaux',
        'antecedents_gynocola',
        'antecedents_neurologiques',
        'antecedents_cardiovasculaires',
        'antecedents_digestifs',
        'antecedents_endocrinologiques',
        'antecedents_hematologiques',
        'antecedents_supplementaires',

        'user_id',
        'assurance_id',
        'categorisation_id',
        'hopital_id',
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

    public function getPhotoUrlAttribute(): string
    {
        if (filled($this->photo)) {
            if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
                return $this->photo;
            }

            return asset('storage/' . ltrim($this->photo, '/'));
        }

        $name = urlencode(trim(($this->prenom ?? 'P') . '+' . ($this->nom ?? 'X')));

        return 'https://ui-avatars.com/api/?background=6366f1&color=fff&name=' . $name;
    }

    protected static function booted()
    {
        static::creating(function (self $patient) {
            if (empty($patient->nin)) {
                $lockKey = 'dossier-patient-nin:' . ($patient->hopital_id ?? 'global');

                Cache::lock($lockKey, 10)->block(5, function () use ($patient) {
                    $number = (int) DB::table('dossier_patients')->max('id') + 1;

                    $patient->nin = sprintf(
                        'NIN-%s%s-%05d',
                        date('y'),
                        $patient->genre,
                        $number
                    );
                });
            }

            if (empty($patient->user_id)) {
                $patient->user_id = Auth::id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assurance(): BelongsTo
    {
        return $this->belongsTo(Assurance::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'dossier_patient_id');
    }

    public function hospitalisations(): HasMany
    {
        return $this->hasMany(Hospitalisation::class, 'dossier_patient_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function allergies()
    {
        return $this->belongsToMany(Allergy::class);
    }
}
