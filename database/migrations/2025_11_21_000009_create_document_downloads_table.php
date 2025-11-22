<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('document_id'); // ID du document de la bibliothèque juridique
            
            // Informations téléchargement
            $table->string('document_title');
            $table->string('document_category')->nullable();
            $table->bigInteger('file_size')->default(0);
            
            // Provenance
            $table->enum('source', ['mobile_app', 'web_chat'])->default('mobile_app');
            
            // Métadonnées
            $table->string('ip_address', 45)->nullable();
            $table->string('device_type', 50)->nullable(); // ios, android, web
            $table->text('user_agent')->nullable();
            
            $table->timestamp('downloaded_at');
            $table->timestamps();
            
            // Index
            $table->index('user_id');
            $table->index('document_id');
            $table->index(['user_id', 'document_id']);
            $table->index('downloaded_at');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_downloads');
    }
};
