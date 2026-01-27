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
        if (Schema::hasTable('legal_documents')) {
            Schema::table('legal_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('legal_documents', 'extracted_text')) {
                    $table->longText('extracted_text')->nullable()->after('description');
                }
                if (!Schema::hasColumn('legal_documents', 'extracted_text_length')) {
                    $table->integer('extracted_text_length')->default(0)->after('extracted_text');
                }
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
                if (Schema::hasColumn('legal_documents', 'extracted_text_length')) {
                    $table->dropColumn('extracted_text_length');
                }
                if (Schema::hasColumn('legal_documents', 'extracted_text')) {
                    $table->dropColumn('extracted_text');
                }
            });
        }
    }
};
