<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('appointments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->integer('contact');
                $table->string('motive');
                $table->string('date');
                $table->string('notes')->default(null);
                $table->integer('created_by');
                $table->timestamps();
            });
        }
    }
};
