<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_chat_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Quota mensuel basé sur le plan Dossy Pro
            // Gratuit: 10, Solo: 100, Basic: 200, Pro: 400
            $table->integer('monthly_quota')->default(10);
            $table->integer('requests_used')->default(0);
            $table->integer('requests_remaining')->default(10);
            
            // Période de quota
            $table->date('quota_month'); // Format: 2024-11-01
            $table->timestamp('quota_reset_at');
            
            // Statistiques
            $table->integer('total_tokens_used')->default(0);
            $table->timestamp('last_request_at')->nullable();
            
            // Alertes envoyées
            $table->boolean('alert_80_percent_sent')->default(false);
            $table->boolean('alert_100_percent_sent')->default(false);
            
            $table->timestamps();
            
            // Index
            $table->index('user_id');
            $table->index(['user_id', 'quota_month']);
            $table->unique(['user_id', 'quota_month']); // Un seul enregistrement par utilisateur par mois
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_chat_usage');
    }
};
