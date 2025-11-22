<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mobile_app_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // free, student, pro, cabinet
            $table->string('name_fr', 100); // Plan Gratuit, Plan Étudiant, etc.
            $table->integer('price_monthly')->default(0); // En FCFA
            $table->integer('price_yearly')->default(0); // En FCFA
            
            // Limites fonctionnalités
            $table->integer('searches_limit')->default(5); // -1 = illimité
            $table->integer('ai_analyses_limit')->default(2); // -1 = illimité
            $table->integer('pdf_downloads_limit')->default(3); // -1 = illimité
            
            // Fonctionnalités avancées
            $table->boolean('has_full_history')->default(false);
            $table->boolean('has_advanced_ai')->default(false);
            
            // Configuration IA
            $table->string('ai_model', 50)->default('gpt-3.5-turbo'); // gpt-4, gpt-4-turbo
            $table->integer('max_tokens')->default(1000);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_app_plans');
    }
};
