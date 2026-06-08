<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConsultationImportExamplesSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Donnees';
    }

    public function headings(): array
    {
        return [
            'nin',
            'ins',
            'type',
            'type_fichier',
            'departement',
            'service',
            'actes',
            'medecin',
            'equipe',
            'assurance',
            'projet',
            'date_consultation',
            'poids',
            'temperature',
            'taille',
            'systolite',
            'diastolique',
            'frequence_cardiaque',
            'frequence_respiratoire',
            'saturation_oxygene',
            'glycemie',
            'prelevement_effectue',
            'symptomes',
            'examen_clinique',
            'diagnostic_presomption',
            'complement_anamnese',
            'plan_traitement_conduite',
        ];
    }

    public function array(): array
    {
        return [
            [
                'NIN-26M-00001',
                '',
                'consultation',
                'standard',
                'pdt',
                '',
                'Visite medicale',
                '',
                '',
                '',
                '',
                '2026-06-05 09:30',
                '72',
                '37',
                '175',
                '12',
                '8',
                '78',
                '18',
                '98',
                '',
                '1',
                'Fatigue, douleurs articulaires',
                'Examen general sans particularite',
                'Suspicion hemophilie',
                '',
                'Surveillance et bilan complementaire',
            ],
            [
                '',
                'INS12345',
                'consultation',
                'hemophile',
                'pediatrie',
                '',
                'Visite medicale; Pansement simple',
                '',
                '',
                '',
                '',
                '05/06/2026 14:00',
                '28',
                '36',
                '120',
                '',
                '',
                '',
                '',
                '',
                '',
                '0',
                '',
                '',
                '',
                '',
                '',
            ],
            [
                'NIN-26F-00002',
                '',
                'depistage',
                'standard',
                'labo',
                'Biochimi sanguine',
                'Glycose',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
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
