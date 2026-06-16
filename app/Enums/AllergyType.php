<?php

namespace App\Enums;

enum AllergyType: string
{
    case Medicament = 'medicament';
    case Alimentaire = 'alimentaire';
    case Environnementale = 'environnementale';
    case Animaux = 'animaux';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Medicament => 'Médicament',
            self::Alimentaire => 'Alimentaire',
            self::Environnementale => 'Environnementale',
            self::Animaux => 'Animaux',
            self::Autre => 'Autre',
        };
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $type) => ['label' => $type->label(), 'value' => $type->value])
            ->all();
    }
}
