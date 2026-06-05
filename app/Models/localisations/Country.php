<?php
namespace App\Models\Localisations;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = ['name', 'code'];

    public function provinces()
    {
        return $this->hasMany(Province::class);
    }
}
