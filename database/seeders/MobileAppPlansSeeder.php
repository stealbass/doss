<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MobileAppPlan;

class MobileAppPlansSeeder extends Seeder
{
    /**
     * Seed les plans mobiles Dossy IA
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'free',
                'name_fr' => 'Plan Gratuit',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'searches_limit' => 5,
                'ai_analyses_limit' => 2,
                'pdf_downloads_limit' => 3,
                'has_full_history' => false,
                'has_advanced_ai' => false,
                'ai_model' => 'gpt-3.5-turbo',
                'max_tokens' => 1000,
                'is_active' => true,
            ],
            [
                'name' => 'student',
                'name_fr' => 'Plan Étudiant',
                'price_monthly' => 2000,
                'price_yearly' => 22000, // 11 mois (1 mois offert)
                'searches_limit' => 30,
                'ai_analyses_limit' => 10,
                'pdf_downloads_limit' => 10,
                'has_full_history' => true,
                'has_advanced_ai' => false,
                'ai_model' => 'gpt-3.5-turbo',
                'max_tokens' => 2000,
                'is_active' => true,
            ],
            [
                'name' => 'pro',
                'name_fr' => 'Plan Pro',
                'price_monthly' => 5000,
                'price_yearly' => 55000, // 11 mois (1 mois offert)
                'searches_limit' => 100,
                'ai_analyses_limit' => 50,
                'pdf_downloads_limit' => -1, // Illimité
                'has_full_history' => true,
                'has_advanced_ai' => true,
                'ai_model' => 'gpt-4',
                'max_tokens' => 4000,
                'is_active' => true,
            ],
            [
                'name' => 'cabinet',
                'name_fr' => 'Plan Cabinet',
                'price_monthly' => 15000,
                'price_yearly' => 165000, // 11 mois (1 mois offert)
                'searches_limit' => -1, // Illimité
                'ai_analyses_limit' => -1, // Illimité
                'pdf_downloads_limit' => -1, // Illimité
                'has_full_history' => true,
                'has_advanced_ai' => true,
                'ai_model' => 'gpt-4-turbo',
                'max_tokens' => 8000,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            MobileAppPlan::updateOrCreate(
                ['name' => $planData['name']],
                $planData
            );
        }

        $this->command->info('✅ Plans mobiles Dossy IA créés avec succès!');
        $this->command->info('');
        $this->command->table(
            ['Plan', 'Prix Mensuel', 'Prix Annuel', 'Recherches', 'Analyses IA', 'PDFs', 'Modèle IA'],
            [
                ['Gratuit', '0 FCFA', '0 FCFA', '5', '2', '3', 'GPT-3.5'],
                ['Étudiant', '2,000 FCFA', '22,000 FCFA', '30', '10', '10', 'GPT-3.5'],
                ['Pro', '5,000 FCFA', '55,000 FCFA', '100', '50', 'Illimité', 'GPT-4'],
                ['Cabinet', '15,000 FCFA', '165,000 FCFA', 'Illimité', 'Illimité', 'Illimité', 'GPT-4 Turbo'],
            ]
        );
    }
}
