<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submitted_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('conversation_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('message_id')->nullable()->constrained()->onDelete('cascade');
            
            // Informations fichier
            $table->string('original_filename');
            $table->string('stored_filename'); // Nom unique sur R2
            $table->string('storage_path'); // Chemin complet sur R2
            $table->string('mime_type', 100);
            $table->bigInteger('file_size'); // En bytes
            
            // Contenu extrait pour analyse IA
            $table->longText('extracted_text')->nullable();
            $table->integer('extracted_text_length')->default(0);
            
            // Métadonnées PDF
            $table->integer('page_count')->default(0);
            $table->json('metadata')->nullable(); // Auteur, titre, date création, etc.
            
            // Traitement
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_error')->nullable();
            
            // URL temporaire (expire après 24h)
            $table->text('temporary_url')->nullable();
            $table->timestamp('url_expires_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('user_id');
            $table->index('conversation_id');
            $table->index('processing_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submitted_documents');
    }
};
