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
        Schema::table('users', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('users', 'domains_of_interest')) {
                $table->text('domains_of_interest')->nullable()->after('mobile_role'); // JSON array des domaines d'intérêt
            }
            
            if (!Schema::hasColumn('users', 'knowledge_level')) {
                $table->string('knowledge_level')->default('debutant')->after('domains_of_interest'); // debutant / intermediaire / expert
            }
            
            if (!Schema::hasColumn('users', 'interaction_stats')) {
                $table->json('interaction_stats')->nullable()->after('knowledge_level'); // Stats d'interaction pour analyse
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only drop columns if they exist
            if (Schema::hasColumn('users', 'domains_of_interest')) {
                $table->dropColumn('domains_of_interest');
            }
            
            if (Schema::hasColumn('users', 'knowledge_level')) {
                $table->dropColumn('knowledge_level');
            }
            
            if (Schema::hasColumn('users', 'interaction_stats')) {
                $table->dropColumn('interaction_stats');
            }
        });
    }
};
