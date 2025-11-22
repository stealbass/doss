<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            
            // Rôle et contenu
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->text('content');
            
            // Documents attachés (si l'utilisateur a uploadé un PDF)
            $table->json('attached_documents')->nullable(); // Array of document IDs
            
            // Contexte RAG (documents utilisés pour la réponse)
            $table->json('rag_context')->nullable(); // Array of legal doc IDs used
            $table->integer('rag_documents_count')->default(0);
            
            // Statistiques OpenAI
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            
            // Modèle utilisé pour cette réponse
            $table->string('ai_model', 50)->nullable();
            
            // Feedback utilisateur
            $table->boolean('is_helpful')->nullable(); // True/False/Null
            $table->text('feedback_comment')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index('conversation_id');
            $table->index(['conversation_id', 'role']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
