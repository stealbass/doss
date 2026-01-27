<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Type de récompense
            $table->enum('reward_type', ['free_month', 'discount', 'bonus_quota'])->default('free_month');
            
            // Détails récompense
            $table->integer('value')->default(0); // 1 pour 1 mois gratuit, ou montant discount en FCFA
            $table->text('description');
            
            // Nombre de parrainages nécessaires (10 pour 1 mois gratuit)
            $table->integer('referrals_required')->default(10);
            $table->integer('referrals_completed')->default(0);
            
            // Statut
            $table->enum('status', ['pending', 'earned', 'redeemed', 'expired'])->default('pending');
            
            // Dates
            $table->timestamp('earned_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Récompense expire après 12 mois
            
            // Lien avec abonnement si appliqué
            $table->foreignId('mobile_app_subscription_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
            
            // Index
            $table->index('user_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
    }
};
