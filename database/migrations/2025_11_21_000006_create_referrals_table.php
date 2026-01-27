<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            
            // Parrain (celui qui invite)
            $table->foreignId('referrer_user_id')->constrained('users')->onDelete('cascade');
            
            // Filleul (celui qui est invité)
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // Code de parrainage unique
            $table->string('referral_code', 20)->unique();
            
            // Email du filleul (avant inscription)
            $table->string('referred_email')->nullable();
            
            // Statut
            $table->enum('status', ['pending', 'registered', 'completed', 'expired'])->default('pending');
            // pending = lien envoyé, registered = compte créé, completed = abonnement payant activé
            
            // Dates importantes
            $table->timestamp('registered_at')->nullable(); // Quand le filleul s'inscrit
            $table->timestamp('completed_at')->nullable(); // Quand le filleul prend un abonnement payant
            $table->timestamp('expires_at')->nullable(); // Expiration du lien (30 jours)
            
            // Métadonnées
            $table->string('source', 50)->default('mobile_app'); // mobile_app, web_chat
            $table->string('campaign')->nullable(); // Pour tracker les campagnes
            
            $table->timestamps();
            
            // Index
            $table->index('referrer_user_id');
            $table->index('referred_user_id');
            $table->index('referral_code');
            $table->index('status');
            $table->index(['referrer_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
