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
        // Ajouter country à legal_categories
        Schema::table('legal_categories', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('slug');
            $table->boolean('is_mobile_visible')->default(true)->after('country');
            $table->integer('sort_order')->default(0)->after('is_mobile_visible');
            
            // Index pour améliorer les performances
            $table->index('country');
            $table->index('is_mobile_visible');
        });

        // Ajouter country à legal_documents
        Schema::table('legal_documents', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('category_id');
            $table->boolean('is_mobile_visible')->default(true)->after('downloads_count');
            $table->string('language', 10)->default('fr')->after('is_mobile_visible'); // fr, en
            $table->text('ai_context')->nullable()->after('language'); // Contexte pour le prompt AI
            
            // Index pour améliorer les performances
            $table->index('country');
            $table->index('is_mobile_visible');
            $table->index('language');
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
