<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Code de parrainage unique pour chaque utilisateur
            $table->string('referral_code', 20)->unique()->nullable()->after('email');
            
            // Compteur de parrainages réussis
            $table->integer('successful_referrals_count')->default(0)->after('referral_code');
            
            // FCM Token pour notifications push mobile
            $table->text('fcm_token')->nullable()->after('successful_referrals_count');
            
            // Préférences notifications
            $table->boolean('push_notifications_enabled')->default(true)->after('fcm_token');
            $table->boolean('email_notifications_enabled')->default(true)->after('push_notifications_enabled');
            
            // Date première installation app mobile
            $table->timestamp('mobile_app_installed_at')->nullable()->after('email_notifications_enabled');
            
            // Dernière activité sur l'app
            $table->timestamp('last_mobile_activity_at')->nullable()->after('mobile_app_installed_at');
            
            // Type d'appareil principal
            $table->enum('primary_device', ['ios', 'android', 'web'])->nullable()->after('last_mobile_activity_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'referral_code',
                'successful_referrals_count',
                'fcm_token',
                'push_notifications_enabled',
                'email_notifications_enabled',
                'mobile_app_installed_at',
                'last_mobile_activity_at',
                'primary_device',
            ]);
        });
    }
};
