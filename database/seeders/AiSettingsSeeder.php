<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiSettingsSeeder extends Seeder
{
    /**
     * Seed les paramètres IA par défaut
     */
    public function run(): void
    {
        DB::table('ai_settings')->insert([
            'openai_api_key' => env('OPENAI_API_KEY'),
            'default_model' => 'gpt-3.5-turbo',
            'default_max_tokens' => 1000,
            'temperature' => 0.7,
            'rag_enabled' => true,
            'rag_mode' => 'advanced',
            'rag_top_k' => 5,
            'rag_similarity_threshold' => 0.7,
            'pinecone_api_key' => env('PINECONE_API_KEY'),
            'pinecone_environment' => env('PINECONE_ENVIRONMENT', 'gcp-starter'),
            'pinecone_index_name' => 'dossy-legal-docs',
            'system_prompt_legal_assistant' => "Vous êtes un assistant juridique expert spécialisé dans le droit camerounais. Votre rôle est d'aider les utilisateurs avec des questions juridiques en vous basant sur les documents juridiques de la bibliothèque Dossy Pro. Soyez précis, professionnel et citez vos sources lorsque possible. Si vous n'êtes pas sûr d'une réponse, indiquez-le clairement et suggérez de consulter un avocat.",
            'system_prompt_document_analysis' => "Vous êtes un expert en analyse de documents juridiques. Analysez le document fourni par l'utilisateur et extrayez les informations clés : type de document, parties impliquées, dates importantes, clauses principales, et tout élément juridique pertinent. Présentez votre analyse de manière structurée et claire.",
            'max_message_length' => 5000,
            'max_file_size_mb' => 10,
            'allowed_file_types' => 'pdf',
            'content_moderation_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('✅ Paramètres IA par défaut créés avec succès!');
        $this->command->info('');
        $this->command->info('⚠️  IMPORTANT: Configurez les clés API dans votre fichier .env:');
        $this->command->info('   - OPENAI_API_KEY=sk-...');
        $this->command->info('   - PINECONE_API_KEY=...');
        $this->command->info('   - PINECONE_ENVIRONMENT=gcp-starter');
    }
}
