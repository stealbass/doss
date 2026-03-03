<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('mobile_app_payment_id')->nullable()->constrained('mobile_app_payments')->onDelete('set null');
            $table->decimal('payment_amount', 12, 2);
            $table->decimal('commission_rate', 5, 2)->default(20.00);
            $table->decimal('commission_amount', 12, 2);
            $table->string('currency', 10)->default('XAF');
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['referrer_user_id', 'status']);
            $table->index(['referred_user_id', 'status']);
            $table->index('mobile_app_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
    }
};
