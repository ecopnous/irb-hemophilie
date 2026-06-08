<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConsultationImportInstructionsSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Instructions';
    }

    public function array(): array
    {
        return [
            ['Guide d\'import des consultations'],
            [''],
            ['Colonne', 'Obligatoire', 'Description', 'Exemples acceptes'],
            ['nin', 'Oui*', 'NIN du patient existant', 'NIN-26M-00001'],
            ['ins', 'Oui*', 'INS du patient (si pas de NIN)', 'INS12345'],
            ['type', 'Non', 'Type de consultation', 'consultation, depistage'],
            ['type_fichier', 'Non', 'Type de fiche medicale', 'standard, hemophile, redac'],
            ['departement', 'Oui', 'Departement (ref ou nom)', 'pdt, pediatrie, labo'],
            ['service', 'Non', 'Service medical', 'Biochimi sanguine'],
            ['actes', 'Oui', 'Actes separes par virgule ou point-virgule', 'Visite medicale; Pansement simple'],
            ['medecin', 'Non', 'Medecin traitant (nom ou email)', 'Dr Mukendi'],
            ['equipe', 'Non', 'Equipe medicale (noms/emails separes)', 'user1@example.com; Dr Kasa'],
            ['assurance', 'Non', 'Nom de l\'assurance', 'Nom exact dans le systeme'],
            ['projet', 'Non', 'Projet de suivi', 'Nom ou reference du projet'],
            ['date_consultation', 'Non', 'Date/heure de la consultation', '2026-06-05 09:30, 05/06/2026 14:00'],
            ['poids', 'Non', 'Poids en kg', '72'],
            ['temperature', 'Non', 'Temperature en °C', '37'],
            ['prelevement_effectue', 'Non', 'Prelevement realise', '1, 0, oui, non'],
            ['symptomes', 'Non', 'Symptomes rapportes', 'Texte libre'],
            ['examen_clinique', 'Non', 'Examen clinique', 'Texte libre'],
            ['diagnostic_presomption', 'Non', 'Diagnostic de presomption', 'Texte libre'],
            [''],
            ['* Au moins nin OU ins est obligatoire par ligne.'],
            ['- Importez uniquement la feuille "Donnees".'],
            ['- Le patient doit deja exister dans le systeme.'],
            ['- Les actes doivent correspondre aux noms enregistres dans l\'application.'],
            ['- Une facturation est creee automatiquement pour chaque consultation importee.'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 24,
            'B' => 14,
            'C' => 44,
            'D' => 40,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => ['font' => ['bold' => true]],
            19 => ['font' => ['bold' => true]],
        ];
    }
}
