<?php

namespace App\Services;

use App\Models\ReceptionBaseSupply;
use App\Models\ReceptionSupply;
use Illuminate\Support\Facades\Auth;

class ReceptionCatalogService
{
    /**
     * Catalogue papeterie (bureau uniquement).
     *
     * @return array<int, array{designation: string, unit: string, category: string, planned_stock?: int}>
     */
    public function defaultPapeterieSupplies(): array
    {
        return [
            ['designation' => 'Accompagne plastifieuse', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 5],
            ['designation' => 'Agraphe', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 50],
            ['designation' => 'Agrapheuse', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 3],
            ['designation' => 'Attaches papiers', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 20],
            ['designation' => 'Anneau grand', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 20],
            ['designation' => 'Anneau moyen', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 20],
            ['designation' => 'Anneau petit', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 20],
            ['designation' => 'Baguette grande', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 15],
            ['designation' => 'Baguette moyenne', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 15],
            ['designation' => 'Baguette petite', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 15],
            ['designation' => 'Baton de colle', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 10],
            ['designation' => 'Cahiers 24 pages', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 30],
            ['designation' => 'Cahiers 48 pages', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 30],
            ['designation' => 'Cahiers 96 pages', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 20],
            ['designation' => 'Cahiers cartonnes', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 15],
            ['designation' => 'Carnet A4', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 20],
            ['designation' => 'Carnet A5', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 20],
            ['designation' => 'CD R', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 25],
            ['designation' => 'Ciseau', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 5],
            ['designation' => 'Classeur A4', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 20],
            ['designation' => 'Classeur A5', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 15],
            ['designation' => 'Correcteur', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 10],
            ['designation' => 'Crayon', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 50],
            ['designation' => 'Desagraffeuse', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 3],
            ['designation' => 'DVD R', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 25],
            ['designation' => 'Elastique', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 20],
            ['designation' => 'Enveloppe A4 blanche', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 100],
            ['designation' => 'Enveloppe A4 Kaki', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 100],
            ['designation' => 'Enveloppe A5 blanche', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 100],
            ['designation' => 'Enveloppe A5 Kaki', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 100],
            ['designation' => 'Enveloppe C6 blanche', 'unit' => 'pce', 'category' => 'papeterie', 'planned_stock' => 100],
        ];
    }

    /**
     * Catalogue service de base = equipements menagers et produits d entretien.
     *
     * @return array<int, array{designation: string, unit: string, category: string, planned_stock?: int}>
     */
    public function defaultBaseSupplies(): array
    {
        return [
            ['designation' => 'Detergent sol', 'unit' => 'litre', 'category' => 'nettoyage', 'planned_stock' => 10],
            ['designation' => 'Desodorisant mural', 'unit' => 'flacon', 'category' => 'entretien', 'planned_stock' => 6],
            ['designation' => 'Desodorisant spray', 'unit' => 'flacon', 'category' => 'entretien', 'planned_stock' => 6],
            ['designation' => 'Savon en poudre', 'unit' => 'pce', 'category' => 'hygiene', 'planned_stock' => 10],
            ['designation' => 'Essuie-tout', 'unit' => 'pce', 'category' => 'consommable', 'planned_stock' => 20],
            ['designation' => 'Sac poubelle 50l', 'unit' => 'pce', 'category' => 'consommable', 'planned_stock' => 50],
            ['designation' => 'Savon main', 'unit' => 'flacon 350 ml', 'category' => 'hygiene', 'planned_stock' => 12],
            ['designation' => 'Desinfectant mains (litre)', 'unit' => 'litre', 'category' => 'hygiene', 'planned_stock' => 5],
            ['designation' => 'Desinfectant mains 100 ml', 'unit' => 'flacon 100 ml', 'category' => 'hygiene', 'planned_stock' => 12],
            ['designation' => 'Desinfectant mains 600 ml', 'unit' => 'flacon 600 ml', 'category' => 'hygiene', 'planned_stock' => 12],
            ['designation' => 'Insecticide', 'unit' => 'flacon', 'category' => 'entretien', 'planned_stock' => 6],
            ['designation' => 'Detergent wc', 'unit' => 'litre', 'category' => 'nettoyage', 'planned_stock' => 8],
            ['designation' => 'Eau de javel', 'unit' => 'litre', 'category' => 'nettoyage', 'planned_stock' => 10],
            ['designation' => 'Eponge', 'unit' => 'pce', 'category' => 'consommable', 'planned_stock' => 15],
            ['designation' => 'Boule de neftali', 'unit' => 'paquet 25 pces', 'category' => 'entretien', 'planned_stock' => 4],
            ['designation' => 'Bloc wc javel', 'unit' => 'pce', 'category' => 'nettoyage', 'planned_stock' => 10],
            ['designation' => 'Cif (Detartrant)', 'unit' => 'litre', 'category' => 'nettoyage', 'planned_stock' => 5],
            ['designation' => 'Bloc wc 2 pces', 'unit' => 'paquet 2 pces', 'category' => 'nettoyage', 'planned_stock' => 8],
            ['designation' => 'Bloc wc 3 pces', 'unit' => 'paquet 3 pces', 'category' => 'nettoyage', 'planned_stock' => 8],
            ['designation' => 'PH blanc 6', 'unit' => 'paquet 6 pces', 'category' => 'hygiene', 'planned_stock' => 10],
            ['designation' => 'PH blanc 10', 'unit' => 'paquet 10 pces', 'category' => 'hygiene', 'planned_stock' => 10],
            ['designation' => 'PH rose', 'unit' => 'paquet 30 pces', 'category' => 'hygiene', 'planned_stock' => 6],
            ['designation' => 'Savon liquide main', 'unit' => 'litre', 'category' => 'hygiene', 'planned_stock' => 8],
            ['designation' => 'Savon mongang', 'unit' => 'pce', 'category' => 'hygiene', 'planned_stock' => 10],
            ['designation' => 'Poudre a recurer', 'unit' => 'flacon 250 g', 'category' => 'nettoyage', 'planned_stock' => 8],
            ['designation' => 'Sac poubelle 25l', 'unit' => 'pce', 'category' => 'consommable', 'planned_stock' => 50],
        ];
    }

    public function seedPapeterieForHopital(int $hopitalId): int
    {
        return $this->seedSupplies(
            ReceptionSupply::class,
            $this->defaultPapeterieSupplies(),
            $hopitalId,
            'PAP-'
        );
    }

    public function seedBaseSuppliesForHopital(int $hopitalId): int
    {
        return $this->seedSupplies(
            ReceptionBaseSupply::class,
            $this->defaultBaseSupplies(),
            $hopitalId,
            'MEN-'
        );
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<int, array{designation: string, unit: string, category: string, planned_stock?: int}>  $items
     */
    protected function seedSupplies(string $modelClass, array $items, int $hopitalId, string $referencePrefix): int
    {
        $created = 0;
        $userId = Auth::id();

        foreach ($items as $index => $item) {
            $exists = $modelClass::query()
                ->where('hopital_id', $hopitalId)
                ->where('designation', $item['designation'])
                ->exists();

            if ($exists) {
                continue;
            }

            $modelClass::query()->create([
                'hopital_id' => $hopitalId,
                'reference' => $referencePrefix . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'designation' => $item['designation'],
                'category' => $item['category'],
                'unit' => $item['unit'],
                'planned_stock' => $item['planned_stock'] ?? 0,
                'current_stock' => 0,
                'stock_min' => max(1, (int) floor(($item['planned_stock'] ?? 0) * 0.2)),
                'is_active' => true,
                'updated_by' => $userId,
            ]);

            $created++;
        }

        return $created;
    }

    public function papeterieCategoryLabels(): array
    {
        return [
            'papeterie' => 'Papeterie bureau',
            'consommable' => 'Consommable bureau',
            'autre' => 'Autre',
        ];
    }

    public function baseSupplyCategoryLabels(): array
    {
        return [
            'nettoyage' => 'Nettoyage',
            'hygiene' => 'Hygiene',
            'entretien' => 'Entretien',
            'consommable' => 'Consommable menager',
            'autre' => 'Autre',
        ];
    }
}
