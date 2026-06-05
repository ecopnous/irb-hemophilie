<?php

namespace App\Models\Configs;

use Illuminate\Database\Eloquent\Model;

class CarnetsVaccin extends Model
{
    protected $table = 'carnets_vaccins';

    protected $fillable = [
        'name',
        'description',
        'age_initial',
        'age_terminal',
        'age_operator',
    ];

    /**
     * Mappe l'opérateur à son symbole correspondant
     */
    private function getOperatorSymbol($operator)
    {
        $symbols = [
            'equal' => '=',
            'not_equal' => '≠',
            'greater' => '>',
            'greater_equal' => '≥',
            'less' => '<',
            'less_equal' => '≤',
            'between' => '',
        ];
        
        return $symbols[$operator] ?? $operator;
    }

    /**
     * Récupère l'âge du patient avec les symboles
     */
    public function getAgeAttribute()
    {
        $symbol = $this->getOperatorSymbol($this->age_operator);
        
        if ($this->age_operator === 'between' || !empty($this->age_terminal)) {
            return trim("{$this->age_initial} - {$this->age_terminal}");
        }
        
        return trim("{$symbol} {$this->age_initial}");
    }
}
