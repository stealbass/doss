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
        Schema::table('submitted_documents', function (Blueprint $table) {
            // Colonnes pour stocker l'anonymisation
            $table->json('anonymization_detections')->nullable()->after('extracted_text_length');
            $table->boolean('has_sensitive_data')->default(false)->after('anonymization_detections');
            $table->integer('detections_count')->default(0)->after('has_sensitive_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submitted_documents', function (Blueprint $table) {
            $table->dropColumn(['anonymization_detections', 'has_sensitive_data', 'detections_count']);
        });
    }
};
