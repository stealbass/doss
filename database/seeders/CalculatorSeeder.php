<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CalculatorConfig;

class CalculatorSeeder extends Seeder
{
    public function run()
    {
        $countries = ['BJ', 'BF', 'CM', 'CI', 'CD', 'GA', 'GW', 'MG', 'ML', 'MA', 'NE', 'SN', 'TG', 'TN'];
        
        $calculators = [
            [
                'name' => 'Calculateur de Salaire Net',
                'name_en' => 'Net Salary Calculator',
                'description' => 'Calcule le salaire net à partir du salaire brut',
                'description_en' => 'Calculate net salary from gross salary',
                'type' => 'salary',
                'inputs' => [
                    ['name' => 'salaire_brut', 'label' => 'Salaire Brut (FCFA)', 'type' => 'number', 'required' => true, 'hint' => 'Montant brut mensuel'],
                    ['name' => 'situation_famille', 'label' => 'Situation Familiale', 'type' => 'select', 'required' => true, 'options' => ['celibataire' => 'Célibataire', 'marie' => 'Marié(e)']],
                    ['name' => 'enfants', 'label' => 'Nombre d\'enfants', 'type' => 'number', 'required' => false, 'hint' => 'Maximum 5 pour abattement'],
                ],
                'formula' => [
                    'cnps_salarie' => 'salaire_brut * 0.036',
                    'assurance_maladie' => 'salaire_brut * 0.05',
                    'total_cotisations' => 'cnps_salarie + assurance_maladie',
                    'salaire_imposable' => 'salaire_brut - total_cotisations',
                    'abattement_famille' => 'situation_famille == "marie" ? 10000 : 0',
                    'abattement_enfants' => 'min(enfants, 5) * 5000',
                    'base_imposable' => 'max(salaire_imposable - abattement_famille - abattement_enfants, 0)',
                    'impot' => 'calculate_progressive_tax(base_imposable)',
                    'salaire_net' => 'salaire_brut - total_cotisations - impot',
                ],
                'instructions' => 'Saisissez votre salaire brut mensuel et votre situation familiale pour obtenir le montant net.',
                'instructions_en' => 'Enter your monthly gross salary and family situation to get the net amount.',
                'required_plan' => 'free',
            ],
            [
                'name' => 'Calculateur d\'Impôt sur le Revenu',
                'name_en' => 'Income Tax Calculator',
                'description' => 'Calcule l\'impôt sur le revenu annuel',
                'description_en' => 'Calculate annual income tax',
                'type' => 'tax',
                'inputs' => [
                    ['name' => 'revenu_annuel', 'label' => 'Revenu Annuel (FCFA)', 'type' => 'number', 'required' => true],
                    ['name' => 'parts_fiscales', 'label' => 'Nombre de parts fiscales', 'type' => 'number', 'required' => true, 'hint' => '1 célibataire, 2 marié, +0.5 par enfant'],
                ],
                'formula' => [
                    'quotient_familial' => 'revenu_annuel / parts_fiscales',
                    'impot_par_part' => 'calculate_progressive_tax(quotient_familial)',
                    'impot_total' => 'impot_par_part * parts_fiscales',
                ],
                'instructions' => 'Calculez votre impôt annuel selon le barème progressif en vigueur.',
                'instructions_en' => 'Calculate your annual tax according to current progressive scale.',
                'required_plan' => 'student',
            ],
            [
                'name' => 'Calculateur de Congés',
                'name_en' => 'Leave Calculator',
                'description' => 'Calcule les droits à congés payés',
                'description_en' => 'Calculate paid leave entitlements',
                'type' => 'leave',
                'inputs' => [
                    ['name' => 'mois_travailles', 'label' => 'Mois travaillés', 'type' => 'number', 'required' => true, 'hint' => 'Période de référence'],
                    ['name' => 'anciennete', 'label' => 'Ancienneté (années)', 'type' => 'number', 'required' => true],
                ],
                'formula' => [
                    'conges_base' => 'mois_travailles * 2.16',
                    'conges_anciennete' => 'anciennete >= 15 ? (anciennete >= 20 ? (anciennete >= 25 ? 6 : 4) : 2) : 0',
                    'total_conges' => 'round(conges_base + conges_anciennete)',
                ],
                'instructions' => 'Calculez vos droits à congés selon votre ancienneté.',
                'instructions_en' => 'Calculate your leave entitlement based on seniority.',
                'required_plan' => 'professional',
            ],
            [
                'name' => 'Calculateur d\'Indemnité de Licenciement',
                'name_en' => 'Severance Pay Calculator',
                'description' => 'Calcule l\'indemnité de licenciement',
                'description_en' => 'Calculate severance pay',
                'type' => 'severance',
                'inputs' => [
                    ['name' => 'salaire_brut', 'label' => 'Dernier Salaire Brut (FCFA)', 'type' => 'number', 'required' => true],
                    ['name' => 'anciennete', 'label' => 'Ancienneté (mois)', 'type' => 'number', 'required' => true],
                    ['name' => 'motif', 'label' => 'Motif', 'type' => 'select', 'required' => true, 'options' => ['economique' => 'Économique', 'faute_legere' => 'Faute légère']],
                ],
                'formula' => [
                    'taux_1_5_ans' => 'min(anciennete, 60) / 12 * 0.25',
                    'taux_5_10_ans' => 'max(min(anciennete, 120) - 60, 0) / 12 * 0.30',
                    'taux_plus_10_ans' => 'max(anciennete - 120, 0) / 12 * 0.35',
                    'taux_total' => 'taux_1_5_ans + taux_5_10_ans + taux_plus_10_ans',
                    'indemnite_base' => 'salaire_brut * taux_total',
                    'indemnite_finale' => 'motif == "economique" ? indemnite_base * 1.15 : indemnite_base',
                ],
                'instructions' => 'Calcul selon le code du travail. Montant minimum garanti.',
                'instructions_en' => 'Calculation per labor code. Minimum amount guaranteed.',
                'required_plan' => 'professional',
            ],
        ];

        foreach ($calculators as $calcData) {
            foreach ($countries as $country) {
                CalculatorConfig::create([
                    'name' => $calcData['name'],
                    'name_en' => $calcData['name_en'],
                    'description' => $calcData['description'],
                    'description_en' => $calcData['description_en'],
                    'type' => $calcData['type'],
                    'country' => $country,
                    'inputs' => json_encode($calcData['inputs']),
                    'formula' => json_encode($calcData['formula']),
                    'instructions' => $calcData['instructions'],
                    'instructions_en' => $calcData['instructions_en'],
                    'required_plan' => $calcData['required_plan'],
                    'is_mobile_visible' => true,
                    'is_active' => true,
                    'usage_count' => rand(20, 300),
                ]);
            }
        }

        $this->command->info('Calculators seeded successfully!');
    }
}
