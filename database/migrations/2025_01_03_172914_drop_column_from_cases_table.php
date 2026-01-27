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
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn('court_hall');
            $table->dropColumn('floor');
            $table->dropColumn('your_team');
            $table->dropColumn('opponents');
            $table->dropColumn('opponent_advocates');
            $table->dropColumn('filing_party');
            $table->dropColumn('case_status');
            $table->dropColumn('sub_motion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->bigInteger('court_hall')->after('filing_date')->nullable();
            $table->bigInteger('floor')->after('court_hall')->nullable();
            $table->string('your_team')->after('FIR_year')->nullable();
            $table->longText('opponents')->after('your_team')->nullable();
            $table->longText('opponent_advocates')->after('opponents')->nullable();
            $table->string('filing_party')->default(null)->after('case_docs')->nullable();
            $table->string('case_status')->default(null)->after('filing_party')->nullable();
            $table->integer('sub_motion')->default(null)->after('motion')->nullable();
        });
    }
};
