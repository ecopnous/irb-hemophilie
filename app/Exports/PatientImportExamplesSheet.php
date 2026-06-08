<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PatientImportExamplesSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Donnees';
    }

    public function headings(): array
    {
        return [
            'prenom',
            'nom',
            'postnom',
            'genre',
            'etat_civil',
            'date_naissance',
            'telephone',
            'email',
            'ins',
            'assurance',
            'quartier',
            'avenue',
            'num_habitation',
            'note',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Jean',
                'KABONGO',
                'MULUMBA',
                'M',
                'Célibataire',
                '1990-05-15',
                '0812345678',
                'jean.kabongo@example.com',
                '',
                '',
                'Gombe',
                'Av. Liberation',
                '12',
                'Patient importe via Excel',
            ],
            [
                'Marie',
                'TSHILOMBO',
                'KASONGO',
                'F',
                'Marié',
                '15/03/1985',
                '0998765432',
                '',
                'INS12345',
                '',
                'Limete',
                'Av. Kasa-Vubu',
                '45B',
                '',
            ],
            [
                'Paul',
                'MUKENDI',
                '',
                'M',
                'Divorcé',
                '01-06-1978',
                '0971122334',
                'paul.mukendi@example.com',
                '',
                '',
                'Bandalungwa',
                'Av. Boma',
                '7',
                'Exemple avec date au format JJ-MM-AAAA',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
