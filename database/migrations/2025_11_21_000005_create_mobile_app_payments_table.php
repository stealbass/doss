<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_app_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mobile_app_subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('mobile_app_plan_id')->constrained()->onDelete('cascade');
            
            // Informations Flutterwave
            $table->string('transaction_id')->unique(); // Flutterwave transaction ID
            $table->string('flutterwave_reference')->unique();
            $table->string('payment_method', 50)->nullable(); // mtn_momo, orange_money, card
            
            // Montant
            $table->integer('amount')->default(0); // En FCFA
            $table->string('currency', 3)->default('XAF');
            $table->integer('fees')->default(0); // Frais Flutterwave
            
            // Statut
            $table->enum('status', ['pending', 'successful', 'failed', 'cancelled', 'refunded'])->default('pending');
            
            // Dates
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            
            // Données brutes webhook Flutterwave
            $table->json('flutterwave_data')->nullable();
            
            // Message d'erreur si échec
            $table->text('error_message')->nullable();
            
            // IP et user agent pour sécurité
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index('user_id');
            $table->index('status');
            $table->index('transaction_id');
            $table->index('flutterwave_reference');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_app_payments');
    }
};
