<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Table des plans d'abonnement mobile avec gestion CRUD
     * Permet à l'admin d'ajouter/modifier/supprimer des plans
     */
    public function up(): void
    {
        Schema::create('mobile_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Gratuit", "Étudiant", "Professionnel", "Cabinet/Entreprise"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Pricing
            $table->decimal('price_monthly', 10, 2)->default(0); // Prix mensuel en FCFA
            $table->decimal('price_yearly', 10, 2)->nullable(); // Prix annuel (si applicable)
            $table->string('currency', 10)->default('XAF'); // XAF, XOF, etc.
            
            // Limits and Features
            $table->integer('max_searches')->default(-1); // -1 = illimité
            $table->integer('max_analyses')->default(-1);
            $table->integer('max_downloads')->default(0);
            
            // Feature flags
            $table->boolean('audio_transcription')->default(false);
            $table->boolean('anonymization')->default(false);
            $table->boolean('multi_accounts')->default(false);
            $table->integer('max_sub_accounts')->default(0); // Nombre de sous-comptes autorisés
            $table->boolean('legal_alerts')->default(false);
            $table->boolean('word_export')->default(false);
            $table->boolean('priority_support')->default(false);
            
            // Enterprise features
            $table->boolean('access_templates')->default(false);
            $table->boolean('access_fiscal_resources')->default(false);
            $table->boolean('access_calculators')->default(false);
            $table->boolean('access_premium_templates')->default(false);
            
            // AI Features
            $table->integer('ai_messages_per_month')->default(0); // Messages AI par mois
            $table->boolean('advanced_ai')->default(false); // GPT-4 vs GPT-3.5
            
            // Plan metadata
            $table->string('badge_color', 20)->nullable(); // Ex: "primary", "success", "warning"
            $table->string('icon')->nullable(); // Emoji ou nom d'icône
            $table->integer('sort_order')->default(0);
            $table->boolean('is_popular')->default(false); // Badge "Populaire"
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true); // Visible dans l'app mobile
            
            // Trial
            $table->boolean('has_trial')->default(false);
            $table->integer('trial_days')->default(0);
            
            $table->timestamps();
            
            $table->index('slug');
            $table->index('is_active');
            $table->index('is_visible');
            $table->index('sort_order');
        });

        // Insert default plans avec les prix fournis
        DB::table('mobile_subscription_plans')->insert([
            [
                'name' => 'Gratuit',
                'slug' => 'gratuit',
                'description' => 'Plan gratuit avec fonctionnalités limitées',
                'price_monthly' => 0,
                'max_searches' => 5,
                'max_analyses' => 2,
                'max_downloads' => 0,
                'audio_transcription' => false,
                'anonymization' => false,
                'multi_accounts' => false,
                'legal_alerts' => false,
                'word_export' => false,
                'access_templates' => false,
                'access_fiscal_resources' => false,
                'access_calculators' => false,
                'ai_messages_per_month' => 10,
                'advanced_ai' => false,
                'badge_color' => 'secondary',
                'icon' => '🆓',
                'sort_order' => 1,
                'is_active' => true,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Étudiant',
                'slug' => 'etudiant',
                'description' => 'Pour les étudiants en droit avec outils pédagogiques',
                'price_monthly' => 2500,
                'max_searches' => 50,
                'max_analyses' => 20,
                'max_downloads' => 10,
                'audio_transcription' => true,
                'anonymization' => false,
                'multi_accounts' => false,
                'legal_alerts' => false,
                'word_export' => false,
                'access_templates' => false,
                'access_fiscal_resources' => false,
                'access_calculators' => false,
                'ai_messages_per_month' => 100,
                'advanced_ai' => false,
                'badge_color' => 'info',
                'icon' => '🎓',
                'sort_order' => 2,
                'is_popular' => true,
                'is_active' => true,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Professionnel',
                'slug' => 'professionnel',
                'description' => 'Pour avocats et juristes avec accès complet',
                'price_monthly' => 5000,
                'max_searches' => 200,
                'max_analyses' => 100,
                'max_downloads' => 50,
                'audio_transcription' => true,
                'anonymization' => true,
                'multi_accounts' => false,
                'legal_alerts' => true,
                'word_export' => true,
                'access_templates' => true,
                'access_fiscal_resources' => true,
                'access_calculators' => true,
                'access_premium_templates' => false,
                'ai_messages_per_month' => 500,
                'advanced_ai' => true,
                'badge_color' => 'primary',
                'icon' => '⚖️',
                'sort_order' => 3,
                'is_active' => true,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cabinet/Entreprise',
                'slug' => 'cabinet-entreprise',
                'description' => 'Solution complète pour cabinets et entreprises',
                'price_monthly' => 15000,
                'max_searches' => -1, // illimité
                'max_analyses' => -1,
                'max_downloads' => -1,
                'audio_transcription' => true,
                'anonymization' => true,
                'multi_accounts' => true,
                'max_sub_accounts' => 10,
                'legal_alerts' => true,
                'word_export' => true,
                'priority_support' => true,
                'access_templates' => true,
                'access_fiscal_resources' => true,
                'access_calculators' => true,
                'access_premium_templates' => true,
                'ai_messages_per_month' => -1, // illimité
                'advanced_ai' => true,
                'badge_color' => 'warning',
                'icon' => '🏢',
                'sort_order' => 4,
                'is_active' => true,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_subscription_plans');
    }
};
