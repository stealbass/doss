<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Système de ressources fiscales et sociales
     * - Code Général des Impôts (CGI)
     * - Loi de Finances
     * - Codes du Travail
     * - Conventions Collectives
     * - Grilles de salaires
     * - Notes circulaires DGI
     */
    public function up(): void
    {
        // Resource categories
        Schema::create('resource_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['fiscal', 'social', 'administrative', 'legal'])->default('fiscal');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('type');
            $table->index('is_active');
        });

        // Fiscal & Social Resources
        Schema::create('fiscal_social_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('resource_categories')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Country and year
            $table->string('country', 100); // MANDATORY - BJ, SN, CM, etc.
            $table->integer('year'); // Ex: 2025 - CRUCIAL for fiscal resources
            $table->string('language', 10)->default('fr');
            
            // Resource type
            $table->enum('resource_type', [
                'cgi', // Code Général des Impôts
                'finance_law', // Loi de Finances
                'tax_procedure', // Livre des Procédures Fiscales
                'circular', // Notes Circulaires DGI
                'doctrine', // Doctrines Administratives
                'convention', // Conventions Fiscales Internationales
                'labor_code', // Code du Travail
                'social_code', // Code de Prévoyance Sociale
                'collective_agreement', // Conventions Collectives
                'salary_grid', // Grilles de salaires
                'administrative_form', // Formulaires administratifs
                'other',
            ])->default('other');
            
            // File information
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type', 50); // pdf, docx, xlsx
            $table->bigInteger('file_size')->default(0);
            
            // Metadata
            $table->string('issuing_authority')->nullable(); // Ex: "Direction Générale des Impôts du Cameroun"
            $table->date('publication_date')->nullable();
            $table->date('effective_date')->nullable(); // Date d'entrée en vigueur
            $table->date('expiry_date')->nullable(); // Important for versioning
            
            // Version control (CRUCIAL for fiscal resources)
            $table->string('version', 50)->nullable(); // Ex: "2025.1"
            $table->boolean('is_latest_version')->default(true);
            $table->foreignId('supersedes_id')->nullable()->constrained('fiscal_social_resources')->onDelete('set null');
            
            // Access control
            $table->boolean('is_mobile_visible')->default(true);
            $table->json('allowed_plans')->nullable(); // ['Professionnel', 'Cabinet/Entreprise']
            $table->boolean('is_premium')->default(false);
            
            // AI integration
            $table->text('ai_context')->nullable(); // Context pour le RAG (Retrieval-Augmented Generation)
            $table->json('key_points')->nullable(); // Points clés extraits pour l'IA
            
            // Usage tracking
            $table->integer('downloads_count')->default(0);
            $table->integer('views_count')->default(0);
            
            $table->integer('created_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('country');
            $table->index('year');
            $table->index('resource_type');
            $table->index('is_latest_version');
            $table->index('is_mobile_visible');
            $table->index(['country', 'year', 'resource_type']);
        });

        // Salary grids (separate table for structured data)
        Schema::create('salary_grids', function (Blueprint $table) {
            $table->id();
            $table->string('country', 100);
            $table->integer('year');
            $table->string('sector')->nullable(); // Ex: "BTP", "Commerce", "Banque"
            $table->string('collective_agreement')->nullable(); // Convention Collective reference
            
            // Grid data
            $table->string('position_title'); // Ex: "Ouvrier qualifié", "Cadre supérieur"
            $table->string('category')->nullable(); // Ex: "A1", "B2", "C3"
            $table->decimal('minimum_salary', 15, 2); // Salaire minimum
            $table->decimal('maximum_salary', 15, 2)->nullable(); // Salaire maximum
            $table->string('currency', 10)->default('XAF'); // XAF, XOF, MAD, etc.
            
            // Additional benefits
            $table->json('allowances')->nullable(); // Indemnités: {transport: 25000, housing: 50000}
            $table->text('notes')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->integer('created_by')->default(0);
            $table->timestamps();
            
            $table->index('country');
            $table->index('year');
            $table->index('sector');
            $table->index('is_active');
        });

        // Tax rates and thresholds (for calculators)
        Schema::create('tax_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('country', 100);
            $table->integer('year');
            $table->enum('tax_type', [
                'income_tax', // IRPP/IS
                'vat', // TVA
                'social_contributions', // Charges sociales
                'payroll_tax', // Taxe sur salaires
                'property_tax', // Taxes foncières
                'other',
            ])->default('income_tax');
            
            $table->string('parameter_name'); // Ex: "IRPP Tranche 1"
            $table->decimal('min_value', 15, 2)->nullable(); // Ex: 0 pour la tranche 1
            $table->decimal('max_value', 15, 2)->nullable(); // Ex: 2000000 pour la tranche 1
            $table->decimal('rate', 8, 4); // Ex: 10.00 pour 10%
            $table->decimal('flat_amount', 15, 2)->nullable(); // Montant forfaitaire
            $table->string('currency', 10)->default('XAF');
            
            $table->text('description')->nullable();
            $table->json('calculation_formula')->nullable(); // Formule de calcul structurée
            
            $table->boolean('is_active')->default(true);
            $table->integer('created_by')->default(0);
            $table->timestamps();
            
            $table->index('country');
            $table->index('year');
            $table->index('tax_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_parameters');
        Schema::dropIfExists('salary_grids');
        Schema::dropIfExists('fiscal_social_resources');
        Schema::dropIfExists('resource_categories');
    }
};
