<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Métadonnées conversation
            $table->string('title', 200)->nullable(); // Titre auto-généré ou personnalisé
            $table->text('summary')->nullable(); // Résumé de la conversation
            
            // Provenance
            $table->enum('source', ['mobile_app', 'web_chat'])->default('mobile_app');
            
            // Statistiques
            $table->integer('messages_count')->default(0);
            $table->integer('total_tokens_used')->default(0);
            
            // Modèle IA utilisé
            $table->string('ai_model', 50)->default('gpt-3.5-turbo');
            
            // Archivage
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_favorite')->default(false);
            
            // Dernière activité
            $table->timestamp('last_message_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('user_id');
            $table->index(['user_id', 'is_archived']);
            $table->index(['user_id', 'is_favorite']);
            $table->index('last_message_at');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
