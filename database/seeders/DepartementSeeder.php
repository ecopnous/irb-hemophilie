<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $departements = [
            ['id' => 1, 'ref' => 'img', 'name' => 'imagerie', 'description' => 'Service d\'imagerie médicale'],
            ['id' => 2, 'ref' => 'labo', 'name' => 'laboratoire', 'description' => 'Service de laboratoire'],
            ['id' => 3, 'ref' => 'mdi', 'name' => 'médecine interne', 'description' => 'Service de médecine interne'],
            ['id' => 4, 'ref' => 'onc', 'name' => "oncologie", "description" => 'Service d\'oncologie'],
            ['id' => 5, 'ref' => 'pdt', 'name' => 'pédiatrie', 'description' => 'Service de pédiatrie'],
            ['id' => 6, 'ref' => 'phm', 'name' => 'pharmacie', 'description' => 'Service de pharmacie'],
        ];
        $services = [
            ['id' => 1, 'name' => 'Biochimi sanguine', 'departement_id' => 2],
            ['id' => 2, 'name' => 'Serologie', 'departement_id' => 2],
            ['id' => 3, 'name' => 'Parasitologie', 'departement_id' => 2],
            ['id' => 4, 'name' => 'Parasitologie', 'departement_id' => 2],
            ['id' => 5, 'name' => 'Hématologie', 'departement_id' => 2],
            ['id' => 6, 'name' => 'Microbiologie', 'departement_id' => 2],
        ];


        $actes = [
            // --- MÉDECINE INTERNE ---
            ['id' => 1, 'name' => 'Visite médicale', 'departement_id' => 3, 'montant' => 30, 'service_id' => null],
            ['id' => 2, 'name' => 'Visite rendez-vous', 'departement_id' => 3, 'montant' => 35, 'service_id' => null],
            ['id' => 3, 'name' => 'Visite dimanche', 'departement_id' => 3, 'montant' => 40, 'service_id' => null],
            ['id' => 4, 'name' => 'Frais ambulance (km)', 'departement_id' => 3, 'montant' => 5, 'service_id' => null],
            ['id' => 5, 'name' => 'Injection IM', 'departement_id' => 3, 'montant' => 5, 'service_id' => null],
            ['id' => 6, 'name' => 'Injection SC', 'departement_id' => 3, 'montant' => 5, 'service_id' => null],
            ['id' => 7, 'name' => 'Injection IV', 'departement_id' => 3, 'montant' => 7, 'service_id' => null],
            ['id' => 8, 'name' => 'Lavement oreille', 'departement_id' => 3, 'montant' => 12, 'service_id' => null],
            ['id' => 9, 'name' => 'Perfusion', 'departement_id' => 3, 'montant' => 25, 'service_id' => null],
            ['id' => 10, 'name' => 'Pansement simple', 'departement_id' => 3, 'montant' => 7, 'service_id' => null],
            ['id' => 11, 'name' => 'Pansement (Gros)', 'departement_id' => 3, 'montant' => 12, 'service_id' => null],
            ['id' => 12, 'name' => 'Circoncision', 'departement_id' => 3, 'montant' => 80, 'service_id' => null],
            ['id' => 13, 'name' => 'Incision abcès', 'departement_id' => 3, 'montant' => 40, 'service_id' => null],
            ['id' => 14, 'name' => 'Ablation de l\'ongle', 'departement_id' => 3, 'montant' => 70, 'service_id' => null],
            ['id' => 15, 'name' => 'Aspiration', 'departement_id' => 3, 'montant' => 30, 'service_id' => null],
            ['id' => 16, 'name' => 'Infiltration', 'departement_id' => 3, 'montant' => 30, 'service_id' => null],
            ['id' => 17, 'name' => 'IDR', 'departement_id' => 3, 'montant' => 10, 'service_id' => null],
            ['id' => 18, 'name' => 'TBC', 'departement_id' => 3, 'montant' => 10, 'service_id' => null],
            ['id' => 19, 'name' => 'Aérosol', 'departement_id' => 3, 'montant' => 20, 'service_id' => null],
            ['id' => 20, 'name' => 'Kystectomie', 'departement_id' => 3, 'montant' => 60, 'service_id' => null],
            ['id' => 21, 'name' => 'Vaccin calendrier (PEV)', 'departement_id' => 3, 'montant' => 5, 'service_id' => null],
            ['id' => 22, 'name' => 'Pesée', 'departement_id' => 3, 'montant' => 5, 'service_id' => null],
            ['id' => 23, 'name' => 'Cautérisation', 'departement_id' => 3, 'montant' => 25, 'service_id' => null],
            ['id' => 24, 'name' => 'Spirométrie', 'departement_id' => 3, 'montant' => 150, 'service_id' => null],
            ['id' => 25, 'name' => 'Suture DR', 'departement_id' => 3, 'montant' => 40, 'service_id' => null],
            ['id' => 26, 'name' => 'Ablation fils', 'departement_id' => 3, 'montant' => 10, 'service_id' => null],
            ['id' => 27, 'name' => 'Ablation plâtre', 'departement_id' => 3, 'montant' => 30, 'service_id' => null],
            ['id' => 28, 'name' => 'Plâtre (Petit)', 'departement_id' => 3, 'montant' => 50, 'service_id' => null],
            ['id' => 29, 'name' => 'Plâtre (Grand)', 'departement_id' => 3, 'montant' => 75, 'service_id' => null],
            ['id' => 30, 'name' => 'Myélogramme', 'departement_id' => 3, 'montant' => 100, 'service_id' => null],
            ['id' => 31, 'name' => 'Insufflation', 'departement_id' => 3, 'montant' => 30, 'service_id' => null],

            // ACTES ET SOINS PEDIATRIE
            ['id' => 32, 'name' => 'Visite médicale', 'departement_id' => 5, 'montant' => 30, 'service_id' => null],
            ['id' => 33, 'name' => 'Visite rendez-vous', 'departement_id' => 5, 'montant' => 35, 'service_id' => null],
            ['id' => 34, 'name' => 'Visite dimanche', 'departement_id' => 5, 'montant' => 40, 'service_id' => null],
            ['id' => 35, 'name' => 'Frais ambulance (km)', 'departement_id' => 5, 'montant' => 5, 'service_id' => null],
            ['id' => 36, 'name' => 'Injection IM', 'departement_id' => 5, 'montant' => 5, 'service_id' => null],
            ['id' => 37, 'name' => 'Injection SC', 'departement_id' => 5, 'montant' => 5, 'service_id' => null],
            ['id' => 38, 'name' => 'Injection IV', 'departement_id' => 5, 'montant' => 7, 'service_id' => null],
            ['id' => 39, 'name' => 'Lavement oreille', 'departement_id' => 5, 'montant' => 12, 'service_id' => null],
            ['id' => 40, 'name' => 'Perfusion', 'departement_id' => 5, 'montant' => 25, 'service_id' => null],
            ['id' => 41, 'name' => 'Pansement simple', 'departement_id' => 5, 'montant' => 7, 'service_id' => null],
            ['id' => 42, 'name' => 'Pansement (Gros)', 'departement_id' => 5, 'montant' => 12, 'service_id' => null],
            ['id' => 43, 'name' => 'Circoncision', 'departement_id' => 5, 'montant' => 80, 'service_id' => null],
            ['id' => 44, 'name' => 'Incision abcès', 'departement_id' => 5, 'montant' => 40, 'service_id' => null],
            ['id' => 45, 'name' => 'Ablation de l\'ongle', 'departement_id' => 5, 'montant' => 70, 'service_id' => null],
            ['id' => 46, 'name' => 'Aspiration', 'departement_id' => 5, 'montant' => 30, 'service_id' => null],
            ['id' => 47, 'name' => 'Infiltration', 'departement_id' => 5, 'montant' => 30, 'service_id' => null],
            ['id' => 48, 'name' => 'IDR', 'departement_id' => 5, 'montant' => 10, 'service_id' => null],
            ['id' => 49, 'name' => 'TBC', 'departement_id' => 5, 'montant' => 10, 'service_id' => null],
            ['id' => 50, 'name' => 'Aérosol', 'departement_id' => 5, 'montant' => 20, 'service_id' => null],
            ['id' => 51, 'name' => 'Kystectomie', 'departement_id' => 5, 'montant' => 60, 'service_id' => null],
            ['id' => 52, 'name' => 'Vaccin calendrier (PEV)', 'departement_id' => 5, 'montant' => 5, 'service_id' => null],
            ['id' => 53, 'name' => 'Pesée', 'departement_id' => 5, 'montant' => 5, 'service_id' => null],
            ['id' => 54, 'name' => 'Cautérisation', 'departement_id' => 5, 'montant' => 25, 'service_id' => null],
            ['id' => 55, 'name' => 'Spirométrie', 'departement_id' => 5, 'montant' => 150, 'service_id' => null],
            ['id' => 56, 'name' => 'Suture DR', 'departement_id' => 5, 'montant' => 40, 'service_id' => null],
            ['id' => 57, 'name' => 'Ablation fils', 'departement_id' => 5, 'montant' => 10, 'service_id' => null],
            ['id' => 58, 'name' => 'Ablation plâtre', 'departement_id' => 5, 'montant' => 30, 'service_id' => null],
            ['id' => 59, 'name' => 'Plâtre (Petit)', 'departement_id' => 5, 'montant' => 50, 'service_id' => null],
            ['id' => 60, 'name' => 'Plâtre (Grand)', 'departement_id' => 5, 'montant' => 75, 'service_id' => null],
            ['id' => 61, 'name' => 'Myélogramme', 'departement_id' => 5, 'montant' => 100, 'service_id' => null],
            ['id' => 62, 'name' => 'Insufflation', 'departement_id' => 5, 'montant' => 30, 'service_id' => null],

            // --- LABORATOIRE : HEMATOLOGIE ---
            // ['id' => 63, 'name' => 'HEMOGRAMME COMPLET (NFS)', 'departement_id' => 2, 'montant' => 25, 'service_id' => 5],
            ['id' => 64, 'name' => 'LEUCOCYTES(GB)', 'departement_id' => 2, 'montant' => 8, 'service_id' => 5],
            ['id' => 65, 'name' => 'PLAQUETTES', 'departement_id' => 2, 'montant' => 5, 'service_id' => 5],
            ['id' => 66, 'name' => 'RETICULOCYTES', 'departement_id' => 2, 'montant' => 8, 'service_id' => 5],
            ['id' => 67, 'name' => 'TEST DE SOLUBILITE(ITANO)', 'departement_id' => 2, 'montant' => 8, 'service_id' => 5],
            ['id' => 68, 'name' => 'VITESSE DE SEDIMENTATION (VS)', 'departement_id' => 2, 'montant' => 5, 'service_id' => 5],
            ['id' => 69, 'name' => 'TEMPS DE SAIGNEMENT (TS)', 'departement_id' => 2, 'montant' => 10, 'service_id' => 5],
            ['id' => 70, 'name' => 'TEMPS DE COAGULATION (TC)', 'departement_id' => 2, 'montant' => 10, 'service_id' => 5],
            ['id' => 71, 'name' => 'FIBRINOGENE', 'departement_id' => 2, 'montant' => 58, 'service_id' => 5],
            ['id' => 72, 'name' => 'FER SERIQUE', 'departement_id' => 2, 'montant' => 10, 'service_id' => 5],
            ['id' => 73, 'name' => 'CAPACITE DE FIXATION DE FER', 'departement_id' => 2, 'montant' => 10, 'service_id' => 5],
            ['id' => 74, 'name' => 'TRANSFERINE', 'departement_id' => 2, 'montant' => 33, 'service_id' => 5],
            ['id' => 75, 'name' => 'INR', 'departement_id' => 2, 'montant' => 30, 'service_id' => 5],
            ['id' => 76, 'name' => 'TP', 'departement_id' => 2, 'montant' => 30, 'service_id' => 5],
            ['id' => 77, 'name' => 'TEMPS DE CEPHALINE ACTIVEE(TCA)', 'departement_id' => 2, 'montant' => 30, 'service_id' => 5],
            ['id' => 78, 'name' => 'GROUPAGE SANGUIN', 'departement_id' => 2, 'montant' => 12, 'service_id' => 5],
            ['id' => 79, 'name' => 'ELECTROPHORESE DE L’HEMOGLOBINE', 'departement_id' => 2, 'montant' => 25, 'service_id' => 5],
            ['id' => 80, 'name' => 'Ferritine', 'departement_id' => 2, 'montant' => 40, 'service_id' => 5],

            // --- LABORATOIRE : BIOCHIMIE ---
            ['id' => 81, 'name' => 'HEMMOGLOBINE GLYQUEE', 'departement_id' => 2, 'montant' => 33, 'service_id' => 1],
            ['id' => 82, 'name' => 'UREE', 'departement_id' => 2, 'montant' => 15, 'service_id' => 1],
            ['id' => 83, 'name' => 'CREATININE', 'departement_id' => 2, 'montant' => 12, 'service_id' => 1],
            ['id' => 84, 'name' => 'ACIDE URIQUE', 'departement_id' => 2, 'montant' => 12, 'service_id' => 1],
            ['id' => 85, 'name' => 'SGOT', 'departement_id' => 2, 'montant' => 15, 'service_id' => 1],
            ['id' => 86, 'name' => 'SGPT', 'departement_id' => 2, 'montant' => 15, 'service_id' => 1],
            ['id' => 87, 'name' => 'GAMMA GT', 'departement_id' => 2, 'montant' => 20, 'service_id' => 1],
            ['id' => 88, 'name' => 'LACTATE DESHYDROGENASE(LDH)', 'departement_id' => 2, 'montant' => 15, 'service_id' => 1],
            ['id' => 89, 'name' => 'PHOSPHATASE ALCALINE (PAL)', 'departement_id' => 2, 'montant' => 20, 'service_id' => 1],
            ['id' => 90, 'name' => 'CHOLESTEROL TOTAL', 'departement_id' => 2, 'montant' => 15, 'service_id' => 1],
            ['id' => 91, 'name' => 'CHOLESTEROL HDL', 'departement_id' => 2, 'montant' => 15, 'service_id' => 1],
            ['id' => 92, 'name' => 'CHOLESTEROL LDL', 'departement_id' => 2, 'montant' => 15, 'service_id' => 1],
            ['id' => 93, 'name' => 'TRIGLYCERIDES', 'departement_id' => 2, 'montant' => 15, 'service_id' => 1],
            ['id' => 94, 'name' => 'PROTEINE TOTALE', 'departement_id' => 2, 'montant' => 15, 'service_id' => 1],
            ['id' => 95, 'name' => 'VIT D', 'departement_id' => 2, 'montant' => 70, 'service_id' => 1],
            ['id' => 96, 'name' => 'ALPHA FOETO PROTEINE', 'departement_id' => 2, 'montant' => 30, 'service_id' => 1],
            ['id' => 97, 'name' => 'RIVALTA', 'departement_id' => 2, 'montant' => 10, 'service_id' => 1],
            ['id' => 98, 'name' => 'TESTOSTERONE', 'departement_id' => 2, 'montant' => 40, 'service_id' => 1],
            ['id' => 99, 'name' => '2FSH', 'departement_id' => 2, 'montant' => 40, 'service_id' => 1],
            ['id' => 100, 'name' => 'ACE', 'departement_id' => 2, 'montant' => 50, 'service_id' => 1],
            ['id' => 101, 'name' => 'ACE 125', 'departement_id' => 2, 'montant' => 50, 'service_id' => 1],
            ['id' => 102, 'name' => 'CA 19-9', 'departement_id' => 2, 'montant' => 50, 'service_id' => 1],
            ['id' => 103, 'name' => 'CA15-31', 'departement_id' => 2, 'montant' => 50, 'service_id' => 1],
            ['id' => 104, 'name' => 'EEA', 'departement_id' => 2, 'montant' => 50, 'service_id' => 1],
            ['id' => 105, 'name' => 'ELECTROPHORESE DES PROTEINES', 'departement_id' => 2, 'montant' => 40, 'service_id' => 1],
            ['id' => 106, 'name' => 'IMMUNO electrophoreses sanguine', 'departement_id' => 2, 'montant' => 55, 'service_id' => 1],
            ['id' => 107, 'name' => 'AC AN', 'departement_id' => 2, 'montant' => 140, 'service_id' => 1],
            ['id' => 108, 'name' => 'BILIRUBINE TOTALE', 'departement_id' => 2, 'montant' => 15, 'service_id' => 1],
            ['id' => 109, 'name' => 'BILIRUBINE DIRECTE', 'departement_id' => 2, 'montant' => 15, 'service_id' => 1],
            ['id' => 110, 'name' => 'CREATININE PHOSPHOKINASE(CPK)', 'departement_id' => 2, 'montant' => 15, 'service_id' => 1],
            ['id' => 111, 'name' => 'CRP (PROTEINE C REACTIVE)', 'departement_id' => 2, 'montant' => 13, 'service_id' => 1],
            ['id' => 112, 'name' => 'IONOGRAMME (K+, Na, Ca++, Cl-)', 'departement_id' => 2, 'montant' => 80, 'service_id' => 1],
            ['id' => 113, 'name' => 'D-DIMERS', 'departement_id' => 2, 'montant' => 46, 'service_id' => 1],
            ['id' => 114, 'name' => 'Glucose', 'departement_id' => 2, 'montant' => 10, 'service_id' => 1],
            ['id' => 115, 'name' => 'Amylase', 'departement_id' => 2, 'montant' => 22, 'service_id' => 1],

            // --- LABORATOIRE : SEROLOGIE ---
            ['id' => 116, 'name' => 'HIV DETERMINE', 'departement_id' => 2, 'montant' => 20, 'service_id' => 2],
            ['id' => 117, 'name' => 'CHARGE VIRALE VIH', 'departement_id' => 2, 'montant' => 120, 'service_id' => 2],
            ['id' => 118, 'name' => 'CD4', 'departement_id' => 2, 'montant' => 15, 'service_id' => 2],
            ['id' => 119, 'name' => 'TDRHEPATITES (VHB antigène)', 'departement_id' => 2, 'montant' => 30, 'service_id' => 2],
            ['id' => 120, 'name' => 'TDRHEPATITES (VHC anticorps)', 'departement_id' => 2, 'montant' => 33, 'service_id' => 2],
            ['id' => 121, 'name' => 'ASLO', 'departement_id' => 2, 'montant' => 17, 'service_id' => 2],
            ['id' => 122, 'name' => 'FACTEUR RHUMATOIDE', 'departement_id' => 2, 'montant' => 15, 'service_id' => 2],
            ['id' => 123, 'name' => 'RPR', 'departement_id' => 2, 'montant' => 10, 'service_id' => 2],
            ['id' => 124, 'name' => 'TEST DE WIDAL', 'departement_id' => 2, 'montant' => 20, 'service_id' => 2],
            ['id' => 125, 'name' => 'H.PYLORI antigène', 'departement_id' => 2, 'montant' => 16, 'service_id' => 2],
            ['id' => 126, 'name' => 'H.PYLORI anticorps', 'departement_id' => 2, 'montant' => 14, 'service_id' => 2],
            ['id' => 127, 'name' => 'RECHERCHE SANG OCCULTE(FOB)', 'departement_id' => 2, 'montant' => 25, 'service_id' => 2],
            ['id' => 128, 'name' => 'PSA TOTALE', 'departement_id' => 2, 'montant' => 50, 'service_id' => 2],
            ['id' => 129, 'name' => 'PSA LIBRE', 'departement_id' => 2, 'montant' => 40, 'service_id' => 2],
            ['id' => 130, 'name' => 'BILAN THYROIDIEN (T3, T4, TSH)', 'departement_id' => 2, 'montant' => 120, 'service_id' => 2],
            ['id' => 131, 'name' => 'TOXOPLASMOSE', 'departement_id' => 2, 'montant' => 80, 'service_id' => 2],
            ['id' => 132, 'name' => 'RUBEOLE', 'departement_id' => 2, 'montant' => 40, 'service_id' => 2],
            ['id' => 133, 'name' => 'IGRA', 'departement_id' => 2, 'montant' => 90, 'service_id' => 2],
            ['id' => 134, 'name' => 'Ag Covid-19', 'departement_id' => 2, 'montant' => 35, 'service_id' => 2],

            // --- LABORATOIRE : PARASITOLOGIE & MICROBIOLOGIE ---
            ['id' => 135, 'name' => 'GOUTTE EPAISSE INTERNE', 'departement_id' => 2, 'montant' => 5, 'service_id' => 3],
            ['id' => 136, 'name' => 'GOUTTE EPAISSE EXTERNE', 'departement_id' => 2, 'montant' => 10, 'service_id' => 3],
            ['id' => 137, 'name' => 'TDR.Palu', 'departement_id' => 2, 'montant' => 5, 'service_id' => 3],
            ['id' => 138, 'name' => 'GOUTTE FRAICHE (microfilaires)', 'departement_id' => 2, 'montant' => 10, 'service_id' => 3],
            ['id' => 139, 'name' => 'SELLES EXAMEN DIRECT', 'departement_id' => 2, 'montant' => 10, 'service_id' => 3],
            ['id' => 140, 'name' => 'URINES COMPLETES (SU+ BU)', 'departement_id' => 2, 'montant' => 15, 'service_id' => 3],
            ['id' => 141, 'name' => 'TEST DE GROSSESSE', 'departement_id' => 2, 'montant' => 15, 'service_id' => 3],
            ['id' => 142, 'name' => 'FROTTIS VAGINAL', 'departement_id' => 2, 'montant' => 15, 'service_id' => 3],
            ['id' => 143, 'name' => 'URETHRAL', 'departement_id' => 2, 'montant' => 15, 'service_id' => 3],
            ['id' => 144, 'name' => 'ECBU(UROCULTURE)', 'departement_id' => 2, 'montant' => 40, 'service_id' => 3],
            ['id' => 145, 'name' => 'COPROCULTURE', 'departement_id' => 2, 'montant' => 40, 'service_id' => 3],
            ['id' => 146, 'name' => 'COLORATION DE GRAM', 'departement_id' => 2, 'montant' => 10, 'service_id' => 3],
            ['id' => 147, 'name' => 'COLORATION DE ZIEHL', 'departement_id' => 2, 'montant' => 30, 'service_id' => 3],

            // --- LABORATOIRE : BIOLOGIE MOLOCULAIRE ---
            ['id' => 148, 'name' => 'CHLAMYDIA.T ET N.GONORRHOEAE(CTNG)', 'departement_id' => 2, 'montant' => 90, 'service_id' => 6],
            ['id' => 149, 'name' => 'BCR-ABL OU CHROMOSOME PHYLADELPHIE', 'departement_id' => 2, 'montant' => 220, 'service_id' => 6],
            ['id' => 150, 'name' => 'HEPATITE C', 'departement_id' => 2, 'montant' => 120, 'service_id' => 6],
            ['id' => 151, 'name' => 'HEPATITE C GENEXPERT', 'departement_id' => 2, 'montant' => 120, 'service_id' => 6],

            // --- IMAGERIE MEDICALE ---
            ['id' => 152, 'name' => 'RX PETIT MODELE', 'departement_id' => 1, 'montant' => 20, 'service_id' => null],
            ['id' => 153, 'name' => 'RX GRAND MODELE', 'departement_id' => 1, 'montant' => 30, 'service_id' => null],
            ['id' => 154, 'name' => 'MAMMOGRAPHIE', 'departement_id' => 1, 'montant' => 60, 'service_id' => null],
            ['id' => 155, 'name' => 'MICRO BIOPSIE ECHO-GUIDEE', 'departement_id' => 1, 'montant' => 200, 'service_id' => null],
            ['id' => 156, 'name' => 'LAVEMENT BARYTE', 'departement_id' => 1, 'montant' => 160, 'service_id' => null],
            ['id' => 157, 'name' => 'ECG', 'departement_id' => 1, 'montant' => 30, 'service_id' => null],
            ['id' => 158, 'name' => 'ECHOGRAPHIE OBSTETRICALE', 'departement_id' => 1, 'montant' => 45, 'service_id' => null],
            ['id' => 159, 'name' => 'ECHOGRAPHIE PELVIENNE', 'departement_id' => 1, 'montant' => 45, 'service_id' => null],
            ['id' => 160, 'name' => 'ECHOGRAPHIE ABDOMINALE', 'departement_id' => 1, 'montant' => 50, 'service_id' => null],
            ['id' => 161, 'name' => 'ECHOGRAPHIE ABDOMINO-PELVIENNE', 'departement_id' => 1, 'montant' => 60, 'service_id' => null],
            ['id' => 162, 'name' => 'ECHOGRAPHIE PROSTATIQUE', 'departement_id' => 1, 'montant' => 60, 'service_id' => null],
            ['id' => 163, 'name' => 'ECHOGRAPHIE THYROIDIENNE', 'departement_id' => 1, 'montant' => 60, 'service_id' => null],
            ['id' => 164, 'name' => 'ECHOGRAPHIE TESTICULAIRE', 'departement_id' => 1, 'montant' => 60, 'service_id' => null],
            ['id' => 165, 'name' => 'ECHOGRAPHIE MAMMAIRE', 'departement_id' => 1, 'montant' => 60, 'service_id' => null],
            ['id' => 166, 'name' => 'ECHOGRAPHIE OCULAIRE', 'departement_id' => 1, 'montant' => 50, 'service_id' => null],
            ['id' => 167, 'name' => 'ECHOGRAPHIE DES GLANDES SALIVAIRES', 'departement_id' => 1, 'montant' => 50, 'service_id' => null],
            ['id' => 168, 'name' => 'ECHOGRAPHIE DES TISSUS MOUS', 'departement_id' => 1, 'montant' => 50, 'service_id' => null],
            ['id' => 169, 'name' => 'ECHOGRAPHIE MUSCULO-SQUELETTIQUE', 'departement_id' => 1, 'montant' => 60, 'service_id' => null],
            ['id' => 170, 'name' => 'ECHOGRAPHIE DOPPLER DES TSA', 'departement_id' => 1, 'montant' => 70, 'service_id' => null],
            ['id' => 171, 'name' => 'ECHOGRAPHIE DOPPLER D’UN MI', 'departement_id' => 1, 'montant' => 70, 'service_id' => null],
            ['id' => 172, 'name' => 'ECHOGRAPHIE DOPPLER DE 2 MI', 'departement_id' => 1, 'montant' => 100, 'service_id' => null],
            ['id' => 173, 'name' => 'ECHOGRAPHIE DOPPLER TRANSCRANIEN', 'departement_id' => 1, 'montant' => 60, 'service_id' => null],
            ['id' => 174, 'name' => 'Prict test', 'departement_id' => 1, 'montant' => 150, 'service_id' => null],
            ['id' => 175, 'name' => 'Spirometrie', 'departement_id' => 1, 'montant' => 150, 'service_id' => null],
        ];



        // 2. On insère sans passer par un Modèle ou une Factory
        DB::table('departements')->insert($departements);
        DB::table('services')->insert($services);
        DB::table('actes')->insert($actes);
    }
}
