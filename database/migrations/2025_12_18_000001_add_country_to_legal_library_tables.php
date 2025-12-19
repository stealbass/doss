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
     * Ajoute la catégorisation par pays pour:
     * - Bénin, Burkina Faso, Côte d'Ivoire, Guinée-Bissau, Mali, Niger, Sénégal, Togo
     * - Cameroun, RD Congo, Gabon, Madagascar, Maroc, Tunisie
     */
    public function up(): void
    {
        // Ajouter country à legal_categories (vérifier si la colonne n'existe pas déjà)
        Schema::table('legal_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_categories', 'country')) {
                $table->string('country', 100)->nullable()->after('slug')->index();
            }
            if (!Schema::hasColumn('legal_categories', 'is_mobile_visible')) {
                $table->boolean('is_mobile_visible')->default(true)->after('slug')->index();
            }
            if (!Schema::hasColumn('legal_categories', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('slug');
            }
        });

        // Ajouter country à legal_documents (vérifier si la colonne n'existe pas déjà)
        Schema::table('legal_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_documents', 'country')) {
                $table->string('country', 100)->nullable()->after('category_id')->index();
            }
            if (!Schema::hasColumn('legal_documents', 'is_mobile_visible')) {
                $table->boolean('is_mobile_visible')->default(true)->index();
            }
            if (!Schema::hasColumn('legal_documents', 'language')) {
                $table->string('language', 10)->default('fr')->index(); // fr, en
            }
            if (!Schema::hasColumn('legal_documents', 'ai_context')) {
                $table->text('ai_context')->nullable(); // Contexte pour le prompt AI
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('legal_categories', function (Blueprint $table) {
            if (Schema::hasColumn('legal_categories', 'country')) {
                $table->dropColumn(['country', 'is_mobile_visible', 'sort_order']);
            }
        });

        Schema::table('legal_documents', function (Blueprint $table) {
            if (Schema::hasColumn('legal_documents', 'country')) {
                $table->dropColumn(['country', 'is_mobile_visible', 'language', 'ai_context']);
            }
        });
    }
};
