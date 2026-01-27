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
            // Ensure processing_status default is 'pending' for background jobs
            $table->string('processing_status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submitted_documents', function (Blueprint $table) {
            $table->string('processing_status')->default('completed')->change();
        });
    }
};
