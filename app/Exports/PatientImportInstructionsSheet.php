<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PatientImportInstructionsSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Instructions';
    }

    public function array(): array
    {
        return [
            ['Guide d\'import des patients'],
            [''],
            ['Colonne', 'Obligatoire', 'Description', 'Exemples acceptes'],
            ['prenom', 'Oui', 'Prenom du patient', 'Jean, Marie'],
            ['nom', 'Non', 'Nom de famille', 'KABONGO'],
            ['postnom', 'Non', 'Postnom', 'MULUMBA'],
            ['genre', 'Oui', 'Sexe du patient', 'M, F, Masculin, Feminin'],
            ['etat_civil', 'Non', 'Etat civil', 'Célibataire, Marié, Divorcé, Veu(f)ve'],
            ['date_naissance', 'Non', 'Date de naissance', '1990-05-15, 15/03/1985, 01-06-1978'],
            ['telephone', 'Non', 'Numero de telephone', '0812345678'],
            ['email', 'Non', 'Adresse email', 'patient@example.com'],
            ['ins', 'Non', 'Identifiant INS (unique)', 'INS12345'],
            ['assurance', 'Non', 'Nom exact de l\'assurance enregistree', 'Nom tel qu\'il apparait dans le systeme'],
            ['quartier', 'Non', 'Quartier de residence', 'Gombe'],
            ['avenue', 'Non', 'Avenue', 'Av. Liberation'],
            ['num_habitation', 'Non', 'Numero d\'habitation', '12, 45B'],
            ['note', 'Non', 'Commentaire libre', 'Texte court'],
            [''],
            ['Conseils'],
            ['- Importez uniquement la feuille "Donnees" (les autres feuilles sont ignorees automatiquement).'],
            ['- Conservez la ligne d\'en-tete telle quelle sur la feuille "Donnees".'],
            ['- Remplacez les lignes d\'exemple par vos donnees ou ajoutez vos lignes en dessous.'],
            ['- Pour les gros volumes, exportez en CSV depuis Excel.'],
            ['- Les lignes en erreur sont ignorees ; un rapport CSV est disponible apres l\'import.'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 14,
            'C' => 42,
            'D' => 36,
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
