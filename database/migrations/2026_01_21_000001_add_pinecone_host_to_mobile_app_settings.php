<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add pinecone_host and pinecone_verify_ssl to mobile_app_settings
     */
    public function up(): void
    {
        Schema::table('mobile_app_settings', function (Blueprint $table) {
            // Add Pinecone host configuration (specific endpoint URL)
            $table->text('pinecone_host')->nullable()->after('pinecone_environment');
            
            // Add SSL verification toggle
            $table->boolean('pinecone_verify_ssl')->default(true)->after('pinecone_host');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobile_app_settings', function (Blueprint $table) {
            $table->dropColumn(['pinecone_host', 'pinecone_verify_ssl']);
        });
    }
};
