<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Ajouter limite quotidienne messages et mettre à jour tokens/models
     */
    public function up(): void
    {
        // 1. Ajouter colonne messages_per_day_limit dans mobile_app_plans
        Schema::table('mobile_app_plans', function (Blueprint $table) {
            $table->integer('messages_per_day_limit')->default(50)->after('pdf_downloads_limit');
            $table->integer('fair_use_monthly_cap')->nullable()->after('ai_analyses_limit');
        });

        // 2. Ajouter colonnes tracking messages dans mobile_app_subscriptions
        Schema::table('mobile_app_subscriptions', function (Blueprint $table) {
            $table->integer('messages_sent_today')->default(0)->after('pdf_downloads_used');
            $table->date('messages_last_reset_date')->nullable()->after('messages_sent_today');
        });

        // 3. Mettre à jour les plans existants avec valeurs recommandées
        DB::table('mobile_app_plans')->where('name', 'free')->update([
            'messages_per_day_limit' => 10,
            'max_tokens' => 1000,
            'ai_model' => 'gpt-3.5-turbo',
        ]);

        DB::table('mobile_app_plans')->where('name', 'student')->update([
            'messages_per_day_limit' => 100,
            'max_tokens' => 4000,
            'ai_model' => 'gpt-3.5-turbo',
        ]);

        DB::table('mobile_app_plans')->where('name', 'pro')->update([
            'messages_per_day_limit' => 500,
            'max_tokens' => 8000,
            'ai_model' => 'gpt-3.5-turbo', // IMPORTANT: Pas GPT-4 pour rentabilité
        ]);

        DB::table('mobile_app_plans')->where('name', 'cabinet')->update([
            'messages_per_day_limit' => -1, // Illimité
            'ai_analyses_limit' => -1, // Reste -1 pour afficher "illimité"
            'fair_use_monthly_cap' => 300, // Cap technique backend-only
            'max_tokens' => 16000,
            'ai_model' => 'gpt-4-turbo',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobile_app_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['messages_sent_today', 'messages_last_reset_date']);
        });

        Schema::table('mobile_app_plans', function (Blueprint $table) {
            $table->dropColumn(['messages_per_day_limit', 'fair_use_monthly_cap']);
        });
    }
};
