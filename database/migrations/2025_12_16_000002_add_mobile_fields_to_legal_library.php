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
        // Add mobile visibility fields to legal_documents table
        if (Schema::hasTable('legal_documents')) {
            Schema::table('legal_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('legal_documents', 'is_mobile_visible')) {
                    $table->boolean('is_mobile_visible')->default(true)->after('file_path');
                }
                if (!Schema::hasColumn('legal_documents', 'mobile_order')) {
                    $table->integer('mobile_order')->default(0)->after('is_mobile_visible');
                }
            });
        }

        // Add mobile visibility fields to legal_categories table
        if (Schema::hasTable('legal_categories')) {
            Schema::table('legal_categories', function (Blueprint $table) {
                if (!Schema::hasColumn('legal_categories', 'is_mobile_visible')) {
                    $table->boolean('is_mobile_visible')->default(true)->after('description');
                }
            });
        }

        // Create mobile_legal_sync_logs table
        if (!Schema::hasTable('mobile_legal_sync_logs')) {
            Schema::create('mobile_legal_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->string('action', 100);
                $table->text('details')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
                
                $table->index('action');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('legal_documents')) {
            Schema::table('legal_documents', function (Blueprint $table) {
                if (Schema::hasColumn('legal_documents', 'is_mobile_visible')) {
                    $table->dropColumn('is_mobile_visible');
                }
                if (Schema::hasColumn('legal_documents', 'mobile_order')) {
                    $table->dropColumn('mobile_order');
                }
            });
        }

        if (Schema::hasTable('legal_categories')) {
            Schema::table('legal_categories', function (Blueprint $table) {
                if (Schema::hasColumn('legal_categories', 'is_mobile_visible')) {
                    $table->dropColumn('is_mobile_visible');
                }
            });
        }

        Schema::dropIfExists('mobile_legal_sync_logs');
    }
};
