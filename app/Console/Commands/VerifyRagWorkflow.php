<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\SubmittedDocument;

class VerifyRagWorkflow extends Command
{
    protected $signature = 'rag:verify';
    protected $description = 'Vérifier le workflow automatique RAG pour les documents uploadés';

    public function handle()
    {
        $this->line("\n═══════════════════════════════════════════════════════════════");
        $this->line("  DIAGNOSTIC: WORKFLOW AUTOMATIQUE RAG POUR DOCUMENTS UPLOADÉS");
        $this->line("═══════════════════════════════════════════════════════════════\n");

        // 1. VÉRIFIER LA CONFIGURATION DE LA QUEUE
        $this->line("<fg=cyan>1️⃣  CONFIGURATION DE LA QUEUE</>");
        $this->line("────────────────────────────────────");

        $queueConnection = env('QUEUE_CONNECTION', env('QUEUE_DRIVER', 'sync'));
        $queueDefault = config('queue.default');

        $this->line("   QUEUE_CONNECTION (env):     " . ($queueConnection === 'sync' ? "<fg=red>🔴 sync (PROBLÈME!)</>" : "<fg=green>✅ $queueConnection</>"));
        $this->line("   QUEUE_DRIVER (env):         " . env('QUEUE_DRIVER', 'NOT SET'));
        $this->line("   Config queue default:       " . $queueDefault);

        if ($queueConnection === 'sync') {
            $this->line("\n   <fg=yellow>⚠️  ALERTE: Queue en mode 'sync' → Jobs exécutés SYNCHRONIQUEMENT</>");
            $this->line("   Cela signifie que ProcessDocumentForRAG s'exécute immédiatement");
            $this->line("   pendant le upload au lieu de s'exécuter en arrière-plan.");
        }
        $this->line("");

        // 2. VÉRIFIER LA TABLE JOBS
        $this->line("<fg=cyan>2️⃣  TABLE DE JOBS (database queue)</>");
        $this->line("────────────────────────────────────");

        if (DB::getSchemaBuilder()->hasTable('jobs')) {
            $jobsCount = DB::table('jobs')->count();
            $failedCount = DB::table('failed_jobs')->count();
            
            $this->line("   Jobs en attente:           " . $jobsCount);
            $this->line("   Jobs échoués:              " . $failedCount);
            
            if ($jobsCount > 0) {
                $jobs = DB::table('jobs')->orderBy('created_at', 'desc')->limit(5)->get();
                $this->line("\n   Derniers jobs en queue:");
                foreach ($jobs as $job) {
                    $this->line("   - ID: {$job->id}, Payload: " . substr($job->payload, 0, 50) . "...");
                }
            }
        } else {
            $this->line("   ℹ️  Pas de table 'jobs' (database queue non configuré)");
        }
        $this->line("");

        // 3. VÉRIFIER LES DOCUMENTS UPLOADÉS
        $this->line("<fg=cyan>3️⃣  DOCUMENTS UPLOADÉS (SubmittedDocument)</>");
        $this->line("────────────────────────────────────");

        $pendingCount = SubmittedDocument::where('processing_status', 'pending')->count();
        $processingCount = SubmittedDocument::where('processing_status', 'processing')->count();
        $completedCount = SubmittedDocument::where('processing_status', 'completed')->count();
        $failedCount = SubmittedDocument::where('processing_status', 'failed')->count();

        $this->line("   Pending (non traités):      " . $pendingCount);
        $this->line("   Processing (en cours):      " . $processingCount);
        $this->line("   Completed (complétés):      " . $completedCount);
        $this->line("   Failed (échoués):           " . $failedCount);

        // Vérifier les derniers documents
        $latestDocs = SubmittedDocument::orderBy('created_at', 'desc')->limit(10)->get();

        if ($latestDocs->isNotEmpty()) {
            $this->line("\n   Derniers documents uploadés:");
            foreach ($latestDocs as $doc) {
                $statusEmoji = [
                    'pending' => '⏳',
                    'processing' => '⚙️',
                    'completed' => '✅',
                    'failed' => '❌',
                ][$doc->processing_status] ?? '?';
                
                $extractedLen = $doc->extracted_text_length ?? 0;
                $this->line("   {$statusEmoji} Doc #{$doc->id}: {$doc->original_filename}");
                $this->line("      Status: {$doc->processing_status}");
                $this->line("      Extracted: {$extractedLen} chars");
                
                if ($doc->processing_error) {
                    $this->line("      <fg=red>Erreur: {$doc->processing_error}</>");
                }
            }
        }
        $this->line("");

        // 4. VÉRIFIER L'EXTRACTION DE TEXTE
        $this->line("<fg=cyan>4️⃣  EXTRACTION DE TEXTE</>");
        $this->line("────────────────────────────────────");

        $docsWithoutText = SubmittedDocument::where('processing_status', 'completed')
            ->where(function ($q) {
                $q->whereNull('extracted_text')
                  ->orWhere('extracted_text', '')
                  ->orWhere('extracted_text_length', 0);
            })->count();

        $docsWithText = SubmittedDocument::where('processing_status', 'completed')
            ->where('extracted_text_length', '>', 0)
            ->count();

        $this->line("   Docs complétés AVEC texte:  " . $docsWithText);
        $this->line("   Docs complétés SANS texte:  " . $docsWithoutText);

        if ($docsWithoutText > 0) {
            $this->line("\n   <fg=yellow>⚠️  ALERTE: Des documents sont marqués 'completed' mais sans texte extrait!</>");
            $docsNoText = SubmittedDocument::where('processing_status', 'completed')
                ->where(function ($q) {
                    $q->whereNull('extracted_text')
                      ->orWhere('extracted_text', '')
                      ->orWhere('extracted_text_length', 0);
                })->limit(3)->get();
            
            foreach ($docsNoText as $doc) {
                $this->line("   - Doc #{$doc->id}: {$doc->original_filename} ({$doc->file_size} bytes)");
            }
        }
        $this->line("");

        // 5. VÉRIFIER PINECONE
        $this->line("<fg=cyan>5️⃣  INDEXATION PINECONE</>");
        $this->line("────────────────────────────────────");

        $pineconeKey = env('PINECONE_API_KEY');
        $pineconeIndex = env('PINECONE_INDEX', 'dossy-legal-docs');

        if (!$pineconeKey) {
            $this->line("   <fg=red>❌ PINECONE_API_KEY non configuré</>");
        } else {
            $this->line("   <fg=green>✅ PINECONE_API_KEY configuré</>");
            $this->line("   Index: $pineconeIndex");
        }
        $this->line("");

        // 6. RÉSUMÉ ET RECOMMANDATIONS
        $this->line("<fg=cyan>6️⃣  RÉSUMÉ ET RECOMMANDATIONS</>");
        $this->line("────────────────────────────────────");

        $issues = [];

        // Vérifier QUEUE_CONNECTION
        if (!env('QUEUE_CONNECTION') && env('QUEUE_DRIVER') === 'sync') {
            $issues[] = "QUEUE_CONNECTION manquant dans .env (utilise QUEUE_DRIVER=sync)";
        }

        if ($queueConnection === 'sync' && $processingCount === 0 && $pendingCount > 0) {
            $issues[] = "Queue en 'sync' mais documents en 'pending' → extraction pas en cours";
        }

        if ($pendingCount > 0) {
            $issues[] = "$pendingCount documents en 'pending' → extraction n'a pas démarré";
        }

        if ($completedCount > 0 && $docsWithoutText > 0) {
            $issues[] = "Extraction échouée silencieusement pour $docsWithoutText documents";
        }

        if (empty($issues)) {
            $this->line("   <fg=green>✅ Aucun problème détecté</>");
        } else {
            $this->line("   <fg=red>❌ PROBLÈMES TROUVÉS:</>\n");
            foreach ($issues as $issue) {
                $this->line("      • $issue");
            }
        }

        $this->line("\n═══════════════════════════════════════════════════════════════\n");
    }
}
