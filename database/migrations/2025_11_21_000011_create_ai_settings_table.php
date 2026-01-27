<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            
            // Configuration OpenAI
            $table->string('openai_api_key')->nullable();
            $table->string('default_model', 50)->default('gpt-3.5-turbo');
            $table->integer('default_max_tokens')->default(1000);
            $table->decimal('temperature', 3, 2)->default(0.7);
            
            // Configuration RAG
            $table->boolean('rag_enabled')->default(true);
            $table->enum('rag_mode', ['simple', 'advanced'])->default('advanced');
            $table->integer('rag_top_k')->default(5); // Nombre de documents à récupérer
            $table->decimal('rag_similarity_threshold', 3, 2)->default(0.7);
            
            // Configuration Pinecone (pour RAG Advanced)
            $table->string('pinecone_api_key')->nullable();
            $table->string('pinecone_environment')->nullable();
            $table->string('pinecone_index_name')->default('dossy-legal-docs');
            
            // Prompts système
            $table->text('system_prompt_legal_assistant')->nullable();
            $table->text('system_prompt_document_analysis')->nullable();
            
            // Limites de sécurité
            $table->integer('max_message_length')->default(5000);
            $table->integer('max_file_size_mb')->default(10);
            $table->string('allowed_file_types')->default('pdf');
            
            // Modération
            $table->boolean('content_moderation_enabled')->default(true);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
