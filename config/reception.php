<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tranches d'âge (réception)
    |--------------------------------------------------------------------------
    |
    | Chaque tranche :
    |   - min : âge minimum inclus (null = pas de borne basse)
    |   - max : âge maximum exclus (null = pas de borne haute)
    |
    | Exemple : min 6, max 13 → de 6 à 12 ans inclus.
    |
    */

    'age_brackets' => [
        [
            'id' => '0_5',
            'name' => '0 à 5 ans',
            'min' => 0,
            'max' => 6,
        ],
        [
            'id' => '6_12',
            'name' => '6 à 12 ans',
            'min' => 6,
            'max' => 13,
        ],
        [
            'id' => '13_18',
            'name' => '13 - 18 ans',
            'min' => 13,
            'max' => 18,
        ],
        [
            'id' => '18_plus',
            'name' => '18 ans et +',
            'min' => 18,
            'max' => null,
        ],
    ],

];
