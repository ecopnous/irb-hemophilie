<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seuils des tranches d'âge (réception)
    |--------------------------------------------------------------------------
    |
    | Liste croissante des limites hautes (exclusives) en années.
    | Exemple [9, 12, 18, 30] produit :
    |   - moins de 9 ans
    |   - 9 - 12 ans
    |   - 12 - 18 ans
    |   - 30 ans et plus
    |
    */

    'age_bracket_thresholds' => [9, 12, 15, 18, 21, 24, 27, 30],

    /*
    |--------------------------------------------------------------------------
    | Limites « moins de X ans » (cumulatives)
    |--------------------------------------------------------------------------
    |
    | Filtres globaux du type « - de 30 » (tous les patients de moins de 30 ans).
    |
    */

    'age_under_limits' => [30],

];
