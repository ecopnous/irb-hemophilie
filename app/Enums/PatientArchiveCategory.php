<?php

namespace App\Enums;

enum PatientArchiveCategory: string
{
    case ConsultationExterne = 'consultation_externe';
    case Imagerie = 'imagerie';
    case Laboratoire = 'laboratoire';
    case CompteRendu = 'compte_rendu';
    case Ordonnance = 'ordonnance';
    case Certificat = 'certificat';
    case DossierMedical = 'dossier_medical';
    case Transfert = 'transfert';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::ConsultationExterne => 'Consultation externe',
            self::Imagerie => 'Imagerie / Radiologie',
            self::Laboratoire => 'Analyses laboratoire',
            self::CompteRendu => 'Compte rendu',
            self::Ordonnance => 'Ordonnance',
            self::Certificat => 'Certificat médical',
            self::DossierMedical => 'Dossier médical complet',
            self::Transfert => 'Transfert / Orientation',
            self::Autre => 'Autre document',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ConsultationExterne => 'user-circle',
            self::Imagerie => 'photo',
            self::Laboratoire => 'beaker',
            self::CompteRendu => 'document-text',
            self::Ordonnance => 'clipboard-document-list',
            self::Certificat => 'shield-check',
            self::DossierMedical => 'folder',
            self::Transfert => 'arrow-right-circle',
            self::Autre => 'document',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::ConsultationExterne => 'indigo',
            self::Imagerie => 'cyan',
            self::Laboratoire => 'sky',
            self::CompteRendu => 'violet',
            self::Ordonnance => 'emerald',
            self::Certificat => 'amber',
            self::DossierMedical => 'slate',
            self::Transfert => 'rose',
            self::Autre => 'zinc',
        };
    }

    /**
     * @return array<int, array{label: string, value: string, icon: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $category) => [
                'label' => $category->label(),
                'value' => $category->value,
                'icon' => $category->icon(),
            ])
            ->all();
    }
}
