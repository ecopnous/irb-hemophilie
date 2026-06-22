<?php

namespace App\Enums;

enum ClinicalMessageCategory: string
{
    case Suivi = 'suivi';
    case Resultats = 'resultats';
    case Orientation = 'orientation';
    case Rappel = 'rappel';
    case Coordination = 'coordination';
    case AvisMedical = 'avis_medical';
    case Laboratoire = 'laboratoire';
    case Imagerie = 'imagerie';
    case Pharmacie = 'pharmacie';
    case Admin = 'admin';
    case Reunion = 'reunion';
    case Urgence = 'urgence';
    case Technique = 'technique';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Suivi => 'Suivi',
            self::Resultats => 'Résultats',
            self::Orientation => 'Orientation',
            self::Rappel => 'Rappel',
            self::Coordination => 'Coordination',
            self::AvisMedical => 'Avis médical',
            self::Laboratoire => 'Laboratoire',
            self::Imagerie => 'Imagerie',
            self::Pharmacie => 'Pharmacie',
            self::Admin => 'Administratif',
            self::Reunion => 'Réunion',
            self::Urgence => 'Urgence',
            self::Technique => 'Technique',
            self::Autre => 'Autre',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Suivi => 'emerald',
            self::Resultats => 'sky',
            self::Orientation => 'amber',
            self::Rappel => 'violet',
            self::Coordination => 'slate',
            self::AvisMedical => 'indigo',
            self::Laboratoire => 'sky',
            self::Imagerie => 'cyan',
            self::Pharmacie => 'emerald',
            self::Admin => 'rose',
            self::Reunion => 'violet',
            self::Urgence => 'red',
            self::Technique => 'zinc',
            self::Autre => 'slate',
        };
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $category) => ['label' => $category->label(), 'value' => $category->value])
            ->all();
    }
}
