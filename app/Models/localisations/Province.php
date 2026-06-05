<?php
namespace App\Models\Localisations;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $fillable = ['name', 'code', 'country_id'];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function communes()
    {
        return $this->hasMany(Commune::class);
    }
}
