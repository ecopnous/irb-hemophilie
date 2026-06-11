<?php

namespace App\Services;

use App\Models\ReceptionBaseService;
use App\Models\ReceptionSupply;
use Illuminate\Support\Facades\Auth;

class ReceptionCatalogService
{
    /**
     * @return array<int, array{designation: string, unit: string, category: string, planned_stock?: int}>
     */
    public function defaultSupplies(): array
    {
        $papeterie = [
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

        $hygiene = [
            ['designation' => 'Detergent sol', 'unit' => 'litre', 'category' => 'hygiene', 'planned_stock' => 10],
            ['designation' => 'Desodorisant mural', 'unit' => 'flacon', 'category' => 'hygiene', 'planned_stock' => 6],
            ['designation' => 'Desodorisant spray', 'unit' => 'flacon', 'category' => 'hygiene', 'planned_stock' => 6],
            ['designation' => 'Savon en poudre', 'unit' => 'pce', 'category' => 'hygiene', 'planned_stock' => 10],
            ['designation' => 'Essuie-tout', 'unit' => 'pce', 'category' => 'hygiene', 'planned_stock' => 20],
            ['designation' => 'Sac poubelle 50l', 'unit' => 'pce', 'category' => 'hygiene', 'planned_stock' => 50],
            ['designation' => 'Savon main', 'unit' => 'flacon 350 ml', 'category' => 'hygiene', 'planned_stock' => 12],
            ['designation' => 'Desinfectant mains (litre)', 'unit' => 'litre', 'category' => 'hygiene', 'planned_stock' => 5],
            ['designation' => 'Desinfectant mains 100 ml', 'unit' => 'flacon 100 ml', 'category' => 'hygiene', 'planned_stock' => 12],
            ['designation' => 'Desinfectant mains 600 ml', 'unit' => 'flacon 600 ml', 'category' => 'hygiene', 'planned_stock' => 12],
            ['designation' => 'Insecticide', 'unit' => 'flacon', 'category' => 'hygiene', 'planned_stock' => 6],
            ['designation' => 'Detergent wc', 'unit' => 'litre', 'category' => 'hygiene', 'planned_stock' => 8],
            ['designation' => 'Eau de javel', 'unit' => 'litre', 'category' => 'hygiene', 'planned_stock' => 10],
            ['designation' => 'Eponge', 'unit' => 'pce', 'category' => 'hygiene', 'planned_stock' => 15],
            ['designation' => 'Boule de neftali', 'unit' => 'paquet 25 pces', 'category' => 'hygiene', 'planned_stock' => 4],
            ['designation' => 'Bloc wc javel', 'unit' => 'pce', 'category' => 'hygiene', 'planned_stock' => 10],
            ['designation' => 'Cif (Detartrant)', 'unit' => 'litre', 'category' => 'hygiene', 'planned_stock' => 5],
            ['designation' => 'Bloc wc 2 pces', 'unit' => 'paquet 2 pces', 'category' => 'hygiene', 'planned_stock' => 8],
            ['designation' => 'Bloc wc 3 pces', 'unit' => 'paquet 3 pces', 'category' => 'hygiene', 'planned_stock' => 8],
            ['designation' => 'PH blanc 6', 'unit' => 'paquet 6 pces', 'category' => 'hygiene', 'planned_stock' => 10],
            ['designation' => 'PH blanc 10', 'unit' => 'paquet 10 pces', 'category' => 'hygiene', 'planned_stock' => 10],
            ['designation' => 'PH rose', 'unit' => 'paquet 30 pces', 'category' => 'hygiene', 'planned_stock' => 6],
            ['designation' => 'Savon liquide main', 'unit' => 'litre', 'category' => 'hygiene', 'planned_stock' => 8],
            ['designation' => 'Savon mongang', 'unit' => 'pce', 'category' => 'hygiene', 'planned_stock' => 10],
            ['designation' => 'Poudre a recurer', 'unit' => 'flacon 250 g', 'category' => 'hygiene', 'planned_stock' => 8],
            ['designation' => 'Sac poubelle 25l', 'unit' => 'pce', 'category' => 'hygiene', 'planned_stock' => 50],
        ];

        return array_merge($papeterie, $hygiene);
    }

    /**
     * @return array<int, array{name: string, code: string, category: string, price: float, description?: string}>
     */
    public function defaultBaseServices(): array
    {
        return [
            ['code' => 'SRV-001', 'name' => 'Accueil et orientation patient', 'category' => 'accueil', 'price' => 0, 'description' => 'Enregistrement initial et orientation vers le bon service.'],
            ['code' => 'SRV-002', 'name' => 'Ouverture dossier patient', 'category' => 'administratif', 'price' => 5, 'description' => 'Creation ou reactivation du dossier medical.'],
            ['code' => 'SRV-003', 'name' => 'Photocopie dossier', 'category' => 'administratif', 'price' => 2, 'description' => 'Copie de documents administratifs ou medicaux.'],
            ['code' => 'SRV-004', 'name' => 'Attestation de presence', 'category' => 'administratif', 'price' => 3, 'description' => 'Document attestant la presence du patient a la consultation.'],
            ['code' => 'SRV-005', 'name' => 'Certificat medical simple', 'category' => 'medical', 'price' => 10, 'description' => 'Delivrance d un certificat medical administratif.'],
            ['code' => 'SRV-006', 'name' => 'Legitimation assurance', 'category' => 'administratif', 'price' => 0, 'description' => 'Verification et enregistrement de la couverture assurance.'],
            ['code' => 'SRV-007', 'name' => 'Reservation rendez-vous', 'category' => 'accueil', 'price' => 0, 'description' => 'Planification d une consultation ou visite programmee.'],
            ['code' => 'SRV-008', 'name' => 'Remise resultats', 'category' => 'accueil', 'price' => 0, 'description' => 'Distribution des resultats labo ou imagerie au patient.'],
            ['code' => 'SRV-009', 'name' => 'Accompagnement visiteur', 'category' => 'accueil', 'price' => 0, 'description' => 'Assistance et orientation des accompagnants.'],
            ['code' => 'SRV-010', 'name' => 'Timbre administratif', 'category' => 'administratif', 'price' => 1, 'description' => 'Frais de timbre pour documents officiels.'],
            ['code' => 'SRV-011', 'name' => 'Tri et classement dossiers', 'category' => 'administratif', 'price' => 0, 'description' => 'Organisation documentaire du service reception.'],
            ['code' => 'SRV-012', 'name' => 'Distribution fournitures bureau', 'category' => 'accueil', 'price' => 0, 'description' => 'Remise de papeterie ou consommables au personnel.'],
        ];
    }

    public function seedSuppliesForHopital(int $hopitalId): int
    {
        $created = 0;
        $userId = Auth::id();

        foreach ($this->defaultSupplies() as $index => $item) {
            $exists = ReceptionSupply::query()
                ->where('hopital_id', $hopitalId)
                ->where('designation', $item['designation'])
                ->exists();

            if ($exists) {
                continue;
            }

            ReceptionSupply::query()->create([
                'hopital_id' => $hopitalId,
                'reference' => 'PAP-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
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

    public function seedBaseServicesForHopital(int $hopitalId): int
    {
        $created = 0;
        $userId = Auth::id();

        foreach ($this->defaultBaseServices() as $item) {
            $exists = ReceptionBaseService::query()
                ->where('hopital_id', $hopitalId)
                ->where('name', $item['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            ReceptionBaseService::query()->create([
                'hopital_id' => $hopitalId,
                'code' => $item['code'],
                'name' => $item['name'],
                'category' => $item['category'] ?? 'accueil',
                'description' => $item['description'] ?? null,
                'price' => $item['price'] ?? 0,
                'currency' => 'USD',
                'is_active' => true,
                'updated_by' => $userId,
            ]);

            $created++;
        }

        return $created;
    }

    public function categoryLabels(): array
    {
        return [
            'papeterie' => 'Papeterie',
            'hygiene' => 'Hygiene & entretien',
            'consommable' => 'Consommable',
            'autre' => 'Autre',
        ];
    }

    public function serviceCategoryLabels(): array
    {
        return [
            'accueil' => 'Accueil',
            'administratif' => 'Administratif',
            'medical' => 'Medical leger',
            'autre' => 'Autre',
        ];
    }
}
