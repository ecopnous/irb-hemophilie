<?php

namespace Database\Seeders;

use App\Enums\ClinicalExamFieldType;
use App\Models\ClinicalExamFieldDefinition;
use Illuminate\Database\Seeder;

class ClinicalExamFieldDefinitionSeeder extends Seeder
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            // Poumons & Cœur
            ['section_key' => 'poumons_coeur', 'section_label' => 'Poumons & Cœur', 'key' => 'poumons_mv', 'label' => 'Mv (murmure vésiculaire)', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 10],
            ['section_key' => 'poumons_coeur', 'section_label' => 'Poumons & Cœur', 'key' => 'poumons_souffle_card', 'label' => 'Souffle cardiaque', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 20],
            ['section_key' => 'poumons_coeur', 'section_label' => 'Poumons & Cœur', 'key' => 'poumons_dysrythmie', 'label' => 'Dysrythmie', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 30],
            ['section_key' => 'poumons_coeur', 'section_label' => 'Poumons & Cœur', 'key' => 'poumons_autre', 'label' => 'Autre (poumons / cœur)', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 40],

            // Abdomen
            ['section_key' => 'abdomen', 'section_label' => 'Abdomen', 'key' => 'abdomen_foie_palpable', 'label' => 'Foie palpable', 'field_type' => ClinicalExamFieldType::BooleanWithNote, 'value_label' => 'cm', 'sort_order' => 50],
            ['section_key' => 'abdomen', 'section_label' => 'Abdomen', 'key' => 'abdomen_rate_palpable', 'label' => 'Rate palpable', 'field_type' => ClinicalExamFieldType::BooleanWithNote, 'value_label' => 'cm', 'sort_order' => 60],
            ['section_key' => 'abdomen', 'section_label' => 'Abdomen', 'key' => 'abdomen_autres', 'label' => 'Autres (abdomen)', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 70],

            // Urogénital & maturation
            ['section_key' => 'urogenital_maturation', 'section_label' => 'Urogénital & maturation sexuelle', 'key' => 'tanner_organe_genital', 'label' => 'Stade Tanner — Organe génital', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 80],
            ['section_key' => 'urogenital_maturation', 'section_label' => 'Urogénital & maturation sexuelle', 'key' => 'tanner_pilosite_pubienne', 'label' => 'Stade Tanner — Pilosité pubienne', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 90],
            ['section_key' => 'urogenital_maturation', 'section_label' => 'Urogénital & maturation sexuelle', 'key' => 'tanner_poitrine', 'label' => 'Stade Tanner — Poitrine (seins)', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 100],
            ['section_key' => 'urogenital_maturation', 'section_label' => 'Urogénital & maturation sexuelle', 'key' => 'volume_testicules', 'label' => 'Volume testicules', 'field_type' => ClinicalExamFieldType::Number, 'value_label' => 'ml', 'sort_order' => 110],
            ['section_key' => 'urogenital_maturation', 'section_label' => 'Urogénital & maturation sexuelle', 'key' => 'menstruations_douloureuses', 'label' => 'Menstruations douloureuses', 'field_type' => ClinicalExamFieldType::Boolean, 'sort_order' => 120],
            ['section_key' => 'urogenital_maturation', 'section_label' => 'Urogénital & maturation sexuelle', 'key' => 'menstruations_irregulieres', 'label' => 'Menstruations irrégulières', 'field_type' => ClinicalExamFieldType::Boolean, 'sort_order' => 130],
            ['section_key' => 'urogenital_maturation', 'section_label' => 'Urogénital & maturation sexuelle', 'key' => 'date_menarche', 'label' => 'Date ménarche', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 140],

            // ORL & dentition
            ['section_key' => 'orl_dentition', 'section_label' => 'ORL & dentition', 'key' => 'orl_tonsilles', 'label' => 'Tonsilles hypertrophiques', 'field_type' => ClinicalExamFieldType::Boolean, 'sort_order' => 150],
            ['section_key' => 'orl_dentition', 'section_label' => 'ORL & dentition', 'key' => 'orl_vegetations', 'label' => 'Végétations adénoïdiennes', 'field_type' => ClinicalExamFieldType::Boolean, 'sort_order' => 160],
            ['section_key' => 'orl_dentition', 'section_label' => 'ORL & dentition', 'key' => 'orl_bouchons_auriculaires', 'label' => 'Bouchons auriculaires (g/d)', 'field_type' => ClinicalExamFieldType::Boolean, 'sort_order' => 170],
            ['section_key' => 'orl_dentition', 'section_label' => 'ORL & dentition', 'key' => 'orl_caries_dentaires', 'label' => 'Caries dentaires', 'field_type' => ClinicalExamFieldType::Number, 'value_label' => 'nombre', 'sort_order' => 180],
            ['section_key' => 'orl_dentition', 'section_label' => 'ORL & dentition', 'key' => 'orl_avulsion_dentaire', 'label' => 'Avulsion dentaire', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 190],

            // Ophtalmologie
            ['section_key' => 'ophtalmologie', 'section_label' => 'Examen ophtalmologique', 'key' => 'ophtalmo_trouble_vue', 'label' => 'Trouble de la vue (myopie, astigmatisme…)', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 200],
            ['section_key' => 'ophtalmologie', 'section_label' => 'Examen ophtalmologique', 'key' => 'ophtalmo_lunettes', 'label' => 'Port de lunettes', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 210],
            ['section_key' => 'ophtalmologie', 'section_label' => 'Examen ophtalmologique', 'key' => 'ophtalmo_autres', 'label' => 'Autres (ophtalmologie)', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 220],

            // Peau & phanères
            ['section_key' => 'peau_phaneres', 'section_label' => 'Peau & phanères', 'key' => 'peau_ulcere_chronique', 'label' => 'Ulcère chronique', 'field_type' => ClinicalExamFieldType::Boolean, 'sort_order' => 230],
            ['section_key' => 'peau_phaneres', 'section_label' => 'Peau & phanères', 'key' => 'peau_mycoses', 'label' => 'Mycoses', 'field_type' => ClinicalExamFieldType::Boolean, 'sort_order' => 240],
            ['section_key' => 'peau_phaneres', 'section_label' => 'Peau & phanères', 'key' => 'peau_teigne', 'label' => 'Teigne', 'field_type' => ClinicalExamFieldType::Boolean, 'sort_order' => 250],
            ['section_key' => 'peau_phaneres', 'section_label' => 'Peau & phanères', 'key' => 'peau_abces', 'label' => 'Abcès', 'field_type' => ClinicalExamFieldType::Boolean, 'sort_order' => 260],
            ['section_key' => 'peau_phaneres', 'section_label' => 'Peau & phanères', 'key' => 'peau_dermatite_pustules', 'label' => 'Dermatite / pustules', 'field_type' => ClinicalExamFieldType::Boolean, 'sort_order' => 270],
            ['section_key' => 'peau_phaneres', 'section_label' => 'Peau & phanères', 'key' => 'peau_fistules', 'label' => 'Fistules', 'field_type' => ClinicalExamFieldType::Boolean, 'sort_order' => 280],

            // Colonne & membres
            ['section_key' => 'colonne_membres', 'section_label' => 'Colonne & membres (sup. et inf.)', 'key' => 'colonne_deviation', 'label' => 'Déviation (scoliose, gibbosité, cyphose…)', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 290],
            ['section_key' => 'colonne_membres', 'section_label' => 'Colonne & membres (sup. et inf.)', 'key' => 'colonne_osteomyelite', 'label' => 'Ostéomyélite', 'field_type' => ClinicalExamFieldType::Boolean, 'sort_order' => 300],
            ['section_key' => 'colonne_membres', 'section_label' => 'Colonne & membres (sup. et inf.)', 'key' => 'colonne_genou_varum_valgum', 'label' => 'Genou varum / valgum', 'field_type' => ClinicalExamFieldType::Text, 'sort_order' => 310],
        ];
    }

    public function run(): void
    {
        foreach (self::definitions() as $definition) {
            ClinicalExamFieldDefinition::query()->updateOrCreate(
                ['key' => $definition['key']],
                array_merge($definition, ['is_active' => true]),
            );
        }
    }
}
