<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SubmittedDocument;
use App\Jobs\ProcessDocumentForRAG;
use Illuminate\Support\Facades\Log;

class ProcessPendingDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:process-pending 
                            {--document= : ID spécifique d\'un document à traiter}
                            {--force : Forcer le retraitement même si déjà indexé}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Traite les documents en attente d\'extraction et d\'indexation Pinecone';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 Traitement des documents en attente...');
        
        $documentId = $this->option('document');
        $force = $this->option('force');
        
        if ($documentId) {
            // Traiter un document spécifique
            $document = SubmittedDocument::find($documentId);
            
            if (!$document) {
                $this->error("❌ Document {$documentId} non trouvé");
                return 1;
            }
            
            $this->info("📄 Traitement du document: {$document->original_filename}");
            $this->processDocument($document, $force);
            
        } else {
            // Traiter tous les documents non indexés
            $query = SubmittedDocument::query();
            
            if (!$force) {
                $query->where(function($q) {
                    $q->whereNull('is_indexed')
                      ->orWhere('is_indexed', 0)
                      ->orWhereNull('extracted_text')
                      ->orWhere('extracted_text', '');
                });
            }
            
            $documents = $query->get();
            $total = $documents->count();
            
            $this->info("📊 {$total} document(s) à traiter");
            
            $bar = $this->output->createProgressBar($total);
            $bar->start();
            
            foreach ($documents as $document) {
                $this->processDocument($document, $force);
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine(2);
        }
        
        $this->info('✅ Traitement terminé!');
        return 0;
    }
    
    /**
     * Traite un document individuel
     */
    protected function processDocument(SubmittedDocument $document, bool $force = false)
    {
        try {
            $this->newLine();
            $this->info("  → Document ID: {$document->id}");
            $this->info("  → Fichier: {$document->original_filename}");
            
            // Vérifier si déjà traité
            if (!$force && $document->is_indexed && !empty($document->extracted_text)) {
                $this->warn("  ⚠️  Déjà indexé (utilisez --force pour retraiter)");
                return;
            }
            
            // Dispatcher le job de traitement
            $this->info("  🚀 Dispatch du job ProcessDocumentForRAG...");
            ProcessDocumentForRAG::dispatch($document->id);
            
            // Attendre un peu pour laisser le job s'exécuter si on est en sync
            sleep(1);
            
            // Recharger le document pour voir les changements
            $document->refresh();
            
            $hasText = !empty($document->extracted_text);
            $isIndexed = $document->is_indexed ?? false;
            
            $this->info("  📝 Texte extrait: " . ($hasText ? "✅ (" . strlen($document->extracted_text) . " chars)" : "❌"));
            $this->info("  🔍 Indexé Pinecone: " . ($isIndexed ? "✅" : "❌"));
            
            if (!$hasText || !$isIndexed) {
                $this->warn("  ⚠️  Le job n'a pas terminé correctement. Vérifiez les logs.");
                Log::warning("Document {$document->id} processing incomplete", [
                    'has_text' => $hasText,
                    'is_indexed' => $isIndexed,
                ]);
            }
            
        } catch (\Exception $e) {
            $this->error("  ❌ ERREUR: " . $e->getMessage());
            Log::error("Error processing document {$document->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
