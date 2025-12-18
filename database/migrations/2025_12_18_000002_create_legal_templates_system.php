<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Système de templates pour les modèles d'actes et contrats
     * - Statuts de sociétés OHADA
     * - Contrats commerciaux
     * - Contrats de travail
     * - PV d'Assemblée
     * - Formulaires administratifs
     */
    public function up(): void
    {
        // Templates categories
        Schema::create('template_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('is_active');
        });

        // Document templates
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('template_categories')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Country and language
            $table->string('country', 100)->nullable(); // BJ, SN, CM, etc.
            $table->string('language', 10)->default('fr');
            
            // File information
            $table->string('file_path'); // Storage path
            $table->string('file_name');
            $table->string('file_type', 50); // docx, pdf, xlsx
            $table->bigInteger('file_size')->default(0);
            
            // Template metadata
            $table->enum('template_type', [
                'contract', 'act', 'form', 'letter', 'calculator', 'checklist'
            ])->default('contract');
            
            // Access control
            $table->json('allowed_plans')->nullable(); // ['Professionnel', 'Cabinet/Entreprise']
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_mobile_visible')->default(true);
            
            // Usage tracking
            $table->integer('downloads_count')->default(0);
            $table->integer('views_count')->default(0);
            
            // Template variables (for dynamic generation)
            $table->json('variables')->nullable(); // {company_name, date, amount, etc.}
            $table->text('ai_context')->nullable(); // Context for AI generation
            
            $table->integer('created_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('country');
            $table->index('template_type');
            $table->index('is_premium');
            $table->index('is_mobile_visible');
        });

        // Template tags (for search and organization)
        Schema::create('template_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Pivot table
        Schema::create('document_template_tag', function (Blueprint $table) {
            $table->foreignId('document_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_tag_id')->constrained()->onDelete('cascade');
            
            $table->primary(['document_template_id', 'template_tag_id'], 'template_tag_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_template_tag');
        Schema::dropIfExists('template_tags');
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('template_categories');
    }
};
