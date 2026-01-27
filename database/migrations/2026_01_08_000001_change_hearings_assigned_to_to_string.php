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
        // Convert 'assigned_to' from foreign bigint to string to support multiple assignees
        if (Schema::hasColumn('hearings', 'assigned_to')) {
            Schema::table('hearings', function (Blueprint $table) {
                // Drop foreign key if exists (safe on MySQL)
                try {
                    $table->dropForeign(['assigned_to']);
                } catch (\Throwable $e) {
                    // ignore if no foreign exists
                }
                // Drop the column and recreate as string
                $table->dropColumn('assigned_to');
            });
        }

        Schema::table('hearings', function (Blueprint $table) {
            $table->string('assigned_to')->nullable()->after('case_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to unsignedBigInteger with FK to users
        if (Schema::hasColumn('hearings', 'assigned_to')) {
            Schema::table('hearings', function (Blueprint $table) {
                $table->dropColumn('assigned_to');
            });
        }

        Schema::table('hearings', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_to')->nullable()->after('case_id');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
    }
};
