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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'jurisdiction')) {
                $table->string('jurisdiction', 10)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'mobile_role')) {
                $table->string('mobile_role', 50)->nullable()->after('jurisdiction');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'jurisdiction')) {
                $table->dropColumn('jurisdiction');
            }
            if (Schema::hasColumn('users', 'mobile_role')) {
                $table->dropColumn('mobile_role');
            }
        });
    }
};
