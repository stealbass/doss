<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fonctionnalités Enterprise / Cabinet
     * - Multi-comptes (DG, RH, Comptable)
     * - Alertes juridiques
     * - Notifications par email/WhatsApp
     */
    public function up(): void
    {
        // Sub-accounts for enterprise plan
        Schema::create('enterprise_sub_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_account_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('sub_account_id')->constrained('users')->onDelete('cascade');
            
            // Role within the organization
            $table->enum('organization_role', [
                'director_general', // DG
                'hr_manager', // RH
                'accountant', // Comptable
                'legal_counsel', // Juriste
                'admin', // Administrateur
                'user', // Utilisateur standard
            ])->default('user');
            
            // Permissions
            $table->json('permissions')->nullable(); // Permissions granulaires
            $table->boolean('can_create_sub_accounts')->default(false);
            $table->boolean('can_manage_billing')->default(false);
            $table->boolean('can_access_analytics')->default(true);
            $table->boolean('can_download_templates')->default(true);
            $table->boolean('can_use_calculators')->default(true);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['main_account_id', 'sub_account_id']);
            $table->index('main_account_id');
            $table->index('sub_account_id');
            $table->index('organization_role');
        });

        // Legal alerts
        Schema::create('legal_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            
            // Alert metadata
            $table->enum('alert_type', [
                'new_law', // Nouvelle loi
                'amendment', // Modification
                'circular', // Circulaire
                'deadline', // Échéance importante
                'court_decision', // Décision de justice
                'fiscal_update', // Mise à jour fiscale
                'social_update', // Mise à jour sociale
                'other',
            ])->default('new_law');
            
            // Targeting
            $table->string('country', 100)->nullable(); // Si spécifique à un pays
            $table->json('affected_sectors')->nullable(); // Secteurs concernés
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Content
            $table->text('summary')->nullable(); // Résumé court
            $table->text('impact_analysis')->nullable(); // Analyse d'impact
            $table->json('action_items')->nullable(); // Actions à entreprendre
            $table->string('source_url')->nullable(); // Lien vers la source
            $table->json('attachments')->nullable(); // Pièces jointes
            
            // Publication
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_published')->default(false);
            
            // Notification channels
            $table->boolean('send_email')->default(true);
            $table->boolean('send_whatsapp')->default(false);
            $table->boolean('send_push')->default(true);
            
            $table->integer('created_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('alert_type');
            $table->index('country');
            $table->index('priority');
            $table->index('published_at');
            $table->index('is_published');
        });

        // Legal alert recipients (tracking)
        Schema::create('legal_alert_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_alert_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Delivery status
            $table->enum('email_status', ['pending', 'sent', 'failed', 'bounced'])->nullable();
            $table->enum('whatsapp_status', ['pending', 'sent', 'delivered', 'read', 'failed'])->nullable();
            $table->enum('push_status', ['pending', 'sent', 'delivered', 'failed'])->nullable();
            
            // Engagement
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['legal_alert_id', 'user_id']);
            $table->index('legal_alert_id');
            $table->index('user_id');
        });

        // Update users table to add country field
        if (!Schema::hasColumn('users', 'country')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('country', 100)->nullable()->after('email');
                $table->index('country');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'country')) {
                $table->dropIndex(['country']);
                $table->dropColumn('country');
            }
        });
        
        Schema::dropIfExists('legal_alert_recipients');
        Schema::dropIfExists('legal_alerts');
        Schema::dropIfExists('enterprise_sub_accounts');
    }
};
