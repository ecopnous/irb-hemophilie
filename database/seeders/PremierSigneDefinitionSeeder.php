<?php

namespace Database\Seeders;

use App\Enums\PremierSigneValueType;
use App\Models\PremierSigneDefinition;
use Illuminate\Database\Seeder;

class PremierSigneDefinitionSeeder extends Seeder
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => 'syndrome_mains_pieds',
                'label' => 'Syndrome mains-pieds',
                'description' => 'Premiers signes cutanés aux extrémités.',
                'value_type' => PremierSigneValueType::Age,
                'value_label' => 'Âge',
                'sort_order' => 10,
            ],
            [
                'key' => 'syndrome_thoracique_aigue',
                'label' => 'Syndrome thoracique aigue',
                'description' => 'Premiers signes cutanés.',
                'value_type' => PremierSigneValueType::Age,
                'value_label' => 'Âge',
                'sort_order' => 20,
            ],
            [
                'key' => 'splenomegalie',
                'label' => 'Splenomegalie',
                'description' => 'Premiers signes cutanés.',
                'value_type' => PremierSigneValueType::Age,
                'value_label' => 'Âge',
                'sort_order' => 30,
            ],
            [
                'key' => 'fievre',
                'label' => 'Fièvre / infection',
                'description' => 'Épisodes fébriles ou infectieux précoces.',
                'value_type' => PremierSigneValueType::Age,
                'value_label' => 'Âge',
                'sort_order' => 40,
            ],
            [
                'key' => 'itere',
                'label' => 'Ictère',
                'description' => 'Apparition d\'un ictère néonatal ou infantile.',
                'value_type' => PremierSigneValueType::Age,
                'value_label' => 'Âge',
                'sort_order' => 50,
            ],
            [
                'key' => 'cvo',
                'label' => 'CVO',
                'description' => 'Complications vasculo-occlusives précoces.',
                'value_type' => PremierSigneValueType::Age,
                'value_label' => 'Âge',
                'sort_order' => 60,
            ],
            [
                'key' => 'transfusion',
                'label' => 'Première transfusion',
                'description' => 'Âge de la première transfusion sanguine.',
                'value_type' => PremierSigneValueType::Age,
                'value_label' => 'Âge',
                'sort_order' => 70,
            ],
            [
                'key' => 'nbr_transfusion',
                'label' => 'Nombre total de transfusions',
                'description' => 'Cumul des transfusions reçues.',
                'value_type' => PremierSigneValueType::Quantity,
                'value_label' => 'Nombre',
                'sort_order' => 80,
            ],
            [
                'key' => 'episodes_epistaxis',
                'label' => 'Épisodes d\'épistaxis',
                'description' => 'Premiers saignements nasaux rapportés.',
                'value_type' => PremierSigneValueType::Age,
                'value_label' => 'Âge',
                'sort_order' => 90,
            ],
            [
                'key' => 'nbr_cvo_an',
                'label' => 'Nombre de CVO / an',
                'description' => 'Fréquence annuelle des épisodes CVO.',
                'value_type' => PremierSigneValueType::Quantity,
                'value_label' => 'Nombre / an',
                'sort_order' => 100,
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::definitions() as $definition) {
            PremierSigneDefinition::query()->updateOrCreate(
                ['key' => $definition['key']],
                [
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'value_type' => $definition['value_type'],
                    'value_label' => $definition['value_label'],
                    'sort_order' => $definition['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
