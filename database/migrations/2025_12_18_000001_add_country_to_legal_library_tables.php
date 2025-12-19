<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajoute la catégorisation par pays pour:
     * - Bénin, Burkina Faso, Côte d'Ivoire, Guinée-Bissau, Mali, Niger, Sénégal, Togo
     * - Cameroun, RD Congo, Gabon, Madagascar, Maroc, Tunisie
     */
    public function up(): void
    {
        // Ajouter country à legal_categories (vérifier si la colonne n'existe pas déjà)
        Schema::table('legal_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_categories', 'country')) {
                $table->string('country', 100)->nullable()->after('slug');
            }
            if (!Schema::hasColumn('legal_categories', 'is_mobile_visible')) {
                $table->boolean('is_mobile_visible')->default(true)->after('slug');
            }
            if (!Schema::hasColumn('legal_categories', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('slug');
            }
        });
        
        // Ajouter les index seulement s'ils n'existent pas
        Schema::table('legal_categories', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('legal_categories');
            
            if (!array_key_exists('legal_categories_country_index', $indexesFound)) {
                $table->index('country');
            }
            if (!array_key_exists('legal_categories_is_mobile_visible_index', $indexesFound)) {
                $table->index('is_mobile_visible');
            }
        });

        // Ajouter country à legal_documents (vérifier si la colonne n'existe pas déjà)
        Schema::table('legal_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_documents', 'country')) {
                $table->string('country', 100)->nullable()->after('category_id');
            }
            if (!Schema::hasColumn('legal_documents', 'is_mobile_visible')) {
                $table->boolean('is_mobile_visible')->default(true);
            }
            if (!Schema::hasColumn('legal_documents', 'language')) {
                $table->string('language', 10)->default('fr'); // fr, en
            }
            if (!Schema::hasColumn('legal_documents', 'ai_context')) {
                $table->text('ai_context')->nullable(); // Contexte pour le prompt AI
            }
        });
        
        // Ajouter les index seulement s'ils n'existent pas
        Schema::table('legal_documents', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('legal_documents');
            
            if (!array_key_exists('legal_documents_country_index', $indexesFound)) {
                $table->index('country');
            }
            if (!array_key_exists('legal_documents_is_mobile_visible_index', $indexesFound)) {
                $table->index('is_mobile_visible');
            }
            if (!array_key_exists('legal_documents_language_index', $indexesFound)) {
                $table->index('language');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('legal_categories', function (Blueprint $table) {
            $table->dropIndex(['country']);
            $table->dropIndex(['is_mobile_visible']);
            $table->dropColumn(['country', 'is_mobile_visible', 'sort_order']);
        });

        Schema::table('legal_documents', function (Blueprint $table) {
            $table->dropIndex(['country']);
            $table->dropIndex(['is_mobile_visible']);
            $table->dropIndex(['language']);
            $table->dropColumn(['country', 'is_mobile_visible', 'language', 'ai_context']);
        });
    }
};
