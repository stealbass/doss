<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_app_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mobile_app_plan_id')->constrained()->onDelete('cascade');
            
            // Type d'abonnement
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('pending');
            
            // Dates de facturation
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            
            // Quota d'utilisation mensuel
            $table->integer('searches_used')->default(0);
            $table->integer('ai_analyses_used')->default(0);
            $table->integer('pdf_downloads_used')->default(0);
            $table->timestamp('quota_reset_at')->nullable();
            
            // Référence paiement
            $table->string('payment_reference')->nullable();
            $table->integer('amount_paid')->default(0); // En FCFA
            
            // Renouvellement automatique
            $table->boolean('auto_renew')->default(false);
            
            $table->timestamps();
            
            // Index pour performances
            $table->index('user_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_app_subscriptions');
    }
};
