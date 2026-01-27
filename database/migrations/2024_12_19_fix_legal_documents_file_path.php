<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Populate file_path for any documents that don't have it
        // This typically happens with legacy documents imported before file_path tracking
        DB::statement("
            UPDATE legal_documents 
            SET file_path = CONCAT('legal_documents/', file_name) 
            WHERE (file_path IS NULL OR file_path = '') 
            AND file_name IS NOT NULL
        ");
        
        // Log the action
        \Illuminate\Support\Facades\Log::info('Migration: Fixed empty file_path values for legal_documents');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is irreversible - it just repairs data
        // Rollback would mean losing the repairs
    }
};
