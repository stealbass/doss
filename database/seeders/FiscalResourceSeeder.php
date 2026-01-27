<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FiscalResource;

class FiscalResourceSeeder extends Seeder
{
    public function run()
    {
        $countries = ['BJ', 'BF', 'CM', 'CI', 'CD', 'GA', 'GW', 'MG', 'ML', 'MA', 'NE', 'SN', 'TG', 'TN'];
        $years = [2023, 2024, 2025];
        
        // Salary Grids for each country/year
        foreach ($countries as $country) {
            foreach ($years as $year) {
                FiscalResource::create([
                    'title' => "Grille Salariale Fonction Publique $year",
                    'title_en' => "Public Service Salary Grid $year",
                    'description' => "Barème des salaires de la fonction publique pour l'année $year",
                    'description_en' => "Public service salary scale for year $year",
                    'type' => 'salary_grid',
                    'country' => $country,
                    'applicable_year' => $year,
                    'version' => '1.0',
                    'content' => json_encode([
                        'categories' => [
                            'A' => ['min' => 150000, 'max' => 400000],
                            'B' => ['min' => 100000, 'max' => 250000],
                            'C' => ['min' => 60000, 'max' => 150000],
                        ],
                        'indices' => range(100, 1000, 50),
                    ]),
                    'legal_references' => json_encode([
                        "Code du Travail Article 145",
                        "Décret N°$year-001 portant grille salariale"
                    ]),
                    'tags' => json_encode(['salaires', 'fonction publique', 'barème']),
                    'effective_date' => "$year-01-01",
                    'is_active' => $year >= 2024,
                    'views_count' => rand(50, 500),
                ]);
            }
        }

        // Tax Parameters
        foreach ($countries as $country) {
            foreach ($years as $year) {
                FiscalResource::create([
                    'title' => "Paramètres Fiscaux IR $year",
                    'title_en' => "Income Tax Parameters $year",
                    'description' => "Barème de l'impôt sur le revenu des personnes physiques",
                    'description_en' => "Personal income tax scale",
                    'type' => 'tax_parameters',
                    'country' => $country,
                    'applicable_year' => $year,
                    'version' => '2.0',
                    'content' => json_encode([
                        'tranches' => [
                            ['min' => 0, 'max' => 50000, 'taux' => 0],
                            ['min' => 50001, 'max' => 150000, 'taux' => 10],
                            ['min' => 150001, 'max' => 300000, 'taux' => 15],
                            ['min' => 300001, 'max' => 500000, 'taux' => 20],
                            ['min' => 500001, 'max' => 1000000, 'taux' => 25],
                            ['min' => 1000001, 'max' => null, 'taux' => 30],
                        ],
                        'abattements' => [
                            'chef_famille' => 10000,
                            'par_enfant' => 5000,
                            'max_enfants' => 5,
                        ],
                    ]),
                    'legal_references' => json_encode([
                        "Code Général des Impôts Article 85",
                        "Loi de Finances $year"
                    ]),
                    'tags' => json_encode(['fiscalité', 'impôts', 'IR', 'revenus']),
                    'effective_date' => "$year-01-01",
                    'is_active' => $year >= 2024,
                    'views_count' => rand(100, 600),
                ]);
            }
        }

        // Social Contributions
        foreach ($countries as $country) {
            FiscalResource::create([
                'title' => "Cotisations Sociales 2024-2025",
                'title_en' => "Social Contributions 2024-2025",
                'description' => "Taux de cotisations sociales employeur et salarié",
                'description_en' => "Employer and employee social contribution rates",
                'type' => 'social_contributions',
                'country' => $country,
                'applicable_year' => 2024,
                'version' => '1.0',
                'content' => json_encode([
                    'cotisations_employeur' => [
                        'cnps' => 16.0,
                        'fne' => 2.0,
                        'accident_travail' => 2.5,
                        'total' => 20.5,
                    ],
                    'cotisations_salarie' => [
                        'cnps' => 3.6,
                        'assurance_maladie' => 5.0,
                        'total' => 8.6,
                    ],
                    'plafond_mensuel' => 1500000,
                ]),
                'legal_references' => json_encode([
                    "Code de Sécurité Sociale",
                    "CNPS Circulaire 2024-03"
                ]),
                'tags' => json_encode(['cotisations', 'CNPS', 'charges sociales']),
                'effective_date' => "2024-01-01",
                'is_active' => true,
                'views_count' => rand(80, 400),
            ]);
        }

        // Leave Rules
        foreach ($countries as $country) {
            FiscalResource::create([
                'title' => "Règles de Congés Payés",
                'title_en' => "Paid Leave Rules",
                'description' => "Modalités d'acquisition et de prise des congés payés",
                'description_en' => "Acquisition and taking of paid leave rules",
                'type' => 'leave_rules',
                'country' => $country,
                'applicable_year' => 2024,
                'version' => '1.0',
                'content' => json_encode([
                    'duree_annuelle' => 26, // jours ouvrables
                    'acquisition' => '2.16 jours par mois',
                    'anciennete' => [
                        '15_ans' => 2,
                        '20_ans' => 4,
                        '25_ans' => 6,
                        '30_ans' => 8,
                    ],
                    'conges_speciaux' => [
                        'mariage' => 3,
                        'naissance' => 3,
                        'deces_conjoint' => 5,
                        'deces_parent' => 3,
                    ],
                ]),
                'legal_references' => json_encode([
                    "Code du Travail Article 153-167",
                    "Convention Collective"
                ]),
                'tags' => json_encode(['congés', 'vacances', 'RH']),
                'effective_date' => "2024-01-01",
                'is_active' => true,
                'views_count' => rand(60, 350),
            ]);
        }

        // Legal Thresholds
        foreach ($countries as $country) {
            FiscalResource::create([
                'title' => "Seuils Légaux 2024",
                'title_en' => "Legal Thresholds 2024",
                'description' => "Seuils et montants légaux applicables",
                'description_en' => "Applicable legal thresholds and amounts",
                'type' => 'legal_thresholds',
                'country' => $country,
                'applicable_year' => 2024,
                'version' => '1.0',
                'content' => json_encode([
                    'smig_horaire' => 300,
                    'smig_mensuel' => 60000,
                    'plafond_securite_sociale' => 1500000,
                    'seuils_tva' => [
                        'regime_reel' => 30000000,
                        'franchise' => 10000000,
                    ],
                    'seuils_impots' => [
                        'micro_entreprise' => 5000000,
                        'reel_simplifie' => 50000000,
                    ],
                ]),
                'legal_references' => json_encode([
                    "Code du Travail",
                    "Code Général des Impôts",
                    "Loi de Finances 2024"
                ]),
                'tags' => json_encode(['seuils', 'SMIG', 'TVA', 'plafonds']),
                'effective_date' => "2024-01-01",
                'is_active' => true,
                'views_count' => rand(90, 450),
            ]);
        }

        $this->command->info('Fiscal resources seeded successfully!');
    }
}
