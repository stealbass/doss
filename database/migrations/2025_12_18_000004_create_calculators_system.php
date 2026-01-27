<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Système de calculateurs et simulateurs
     * - Simulateur coût d'embauche
     * - Calculateur indemnités de licenciement
     * - Simulateur impôts
     * - Log des calculs pour analytics
     */
    public function up(): void
    {
        // Calculator configurations
        Schema::create('calculator_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Calculator type
            $table->enum('calculator_type', [
                'hiring_cost', // Coût d'embauche
                'severance', // Indemnités de licenciement
                'income_tax', // Impôt sur le revenu
                'vat', // TVA
                'social_contributions', // Charges sociales
                'net_to_gross', // Net à brut
                'gross_to_net', // Brut à net
                'custom', // Calculateur personnalisé
            ])->default('hiring_cost');
            
            // Country specific
            $table->string('country', 100);
            $table->integer('year')->default(2025);
            
            // Configuration JSON
            $table->json('input_fields'); // Configuration des champs d'entrée
            $table->json('calculation_formula'); // Formule de calcul
            $table->json('output_fields'); // Configuration des résultats
            
            // Access control
            $table->json('allowed_plans')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_mobile_visible')->default(true);
            $table->boolean('is_active')->default(true);
            
            $table->integer('created_by')->default(0);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('calculator_type');
            $table->index('country');
            $table->index('year');
            $table->index('is_mobile_visible');
        });

        // Calculator usage logs (for analytics and ML)
        Schema::create('calculator_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculator_config_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Input data (anonymized for analytics)
            $table->json('input_data'); // Les données entrées par l'utilisateur
            $table->json('output_data'); // Les résultats calculés
            
            // Metadata
            $table->string('country', 100)->nullable();
            $table->string('user_plan')->nullable(); // Plan de l'utilisateur
            $table->string('session_id', 100)->nullable(); // Pour regrouper les calculs
            
            // Usage tracking
            $table->timestamp('calculated_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            
            $table->index('calculator_config_id');
            $table->index('user_id');
            $table->index('country');
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calculator_logs');
        Schema::dropIfExists('calculator_configs');
    }
};
