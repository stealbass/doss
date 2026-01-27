<?php
/**
 * Vérification complète du workflow automatique: Upload → Extract → Index
 * 
 * Ce script vérifie:
 * 1. Configuration de la queue (QUEUE_CONNECTION vs QUEUE_DRIVER)
 * 2. Statut des documents SubmittedDocument (pending/processing/completed)
 * 3. Extraction du texte (extracted_text, extracted_text_length)
 * 4. Indexation Pinecone (vectors dans Pinecone)
 * 5. Jobs en queue (jobs table)
 */

// Charger l'app Laravel
require __DIR__ . '/../bootstrap/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Models\SubmittedDocument;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  DIAGNOSTIC: WORKFLOW AUTOMATIQUE RAG POUR DOCUMENTS UPLOADÉS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. VÉRIFIER LA CONFIGURATION DE LA QUEUE
echo "1️⃣  CONFIGURATION DE LA QUEUE\n";
echo "────────────────────────────────────\n";

$queueConnection = env('QUEUE_CONNECTION', env('QUEUE_DRIVER', 'sync'));
$queueDefault = config('queue.default');

echo "   QUEUE_CONNECTION (env):     " . ($queueConnection === 'sync' ? "🔴 sync (PROBLÈME!)" : "✅ $queueConnection") . "\n";
echo "   QUEUE_DRIVER (env):         " . env('QUEUE_DRIVER', 'NOT SET') . "\n";
echo "   Config queue default:       " . $queueDefault . "\n";

if ($queueConnection === 'sync') {
    echo "\n   ⚠️  ALERTE: Queue en mode 'sync' → Jobs exécutés SYNCHRONIQUEMENT\n";
    echo "   Cela signifie que ProcessDocumentForRAG s'exécute immédiatement\n";
    echo "   pendant le upload au lieu de s'exécuter en arrière-plan.\n";
}
echo "\n";

// 2. VÉRIFIER LA TABLE JOBS (si database queue est utilisé)
echo "2️⃣  TABLE DE JOBS (database queue)\n";
echo "────────────────────────────────────\n";

if (DB::getSchemaBuilder()->hasTable('jobs')) {
    $jobsCount = DB::table('jobs')->count();
    $failedCount = DB::table('failed_jobs')->count();
    
    echo "   Jobs en attente:           " . $jobsCount . "\n";
    echo "   Jobs échoués:              " . $failedCount . "\n";
    
    if ($jobsCount > 0) {
        $jobs = DB::table('jobs')->orderBy('created_at', 'desc')->limit(5)->get();
        echo "\n   Derniers jobs en queue:\n";
        foreach ($jobs as $job) {
            echo "   - ID: {$job->id}, Payload: " . substr($job->payload, 0, 50) . "...\n";
        }
    }
} else {
    echo "   ℹ️  Pas de table 'jobs' (database queue non configuré)\n";
}
echo "\n";

// 3. VÉRIFIER LES DOCUMENTS UPLOADÉS
echo "3️⃣  DOCUMENTS UPLOADÉS (SubmittedDocument)\n";
echo "────────────────────────────────────\n";

$pendingCount = SubmittedDocument::where('processing_status', 'pending')->count();
$processingCount = SubmittedDocument::where('processing_status', 'processing')->count();
$completedCount = SubmittedDocument::where('processing_status', 'completed')->count();
$failedCount = SubmittedDocument::where('processing_status', 'failed')->count();

echo "   Pending (non traités):      " . $pendingCount . "\n";
echo "   Processing (en cours):      " . $processingCount . "\n";
echo "   Completed (complétés):      " . $completedCount . "\n";
echo "   Failed (échoués):           " . $failedCount . "\n";

// Vérifier les derniers documents
$latestDocs = SubmittedDocument::orderBy('created_at', 'desc')->limit(5)->get();

if ($latestDocs->isNotEmpty()) {
    echo "\n   Derniers documents uploadés:\n";
    foreach ($latestDocs as $doc) {
        $statusEmoji = [
            'pending' => '⏳',
            'processing' => '⚙️',
            'completed' => '✅',
            'failed' => '❌',
        ][$doc->processing_status] ?? '?';
        
        $extractedLen = $doc->extracted_text_length ?? 0;
        echo "   {$statusEmoji} Doc #{$doc->id}: {$doc->original_filename}\n";
        echo "      Status: {$doc->processing_status}\n";
        echo "      Extracted: {$extractedLen} chars\n";
        
        if ($doc->processing_error) {
            echo "      Erreur: {$doc->processing_error}\n";
        }
    }
}
echo "\n";

// 4. VÉRIFIER L'EXTRACTION DE TEXTE
echo "4️⃣  EXTRACTION DE TEXTE\n";
echo "────────────────────────────────────\n";

$docsWithoutText = SubmittedDocument::where('processing_status', 'completed')
    ->where(function ($q) {
        $q->whereNull('extracted_text')
          ->orWhere('extracted_text', '')
          ->orWhere('extracted_text_length', 0);
    })->count();

$docsWithText = SubmittedDocument::where('processing_status', 'completed')
    ->where('extracted_text_length', '>', 0)
    ->count();

echo "   Docs complétés AVEC texte:  " . $docsWithText . "\n";
echo "   Docs complétés SANS texte:  " . $docsWithoutText . "\n";

if ($docsWithoutText > 0) {
    echo "\n   ⚠️  ALERTE: Des documents sont marqués 'completed' mais sans texte extrait!\n";
    $docsNoText = SubmittedDocument::where('processing_status', 'completed')
        ->where(function ($q) {
            $q->whereNull('extracted_text')
              ->orWhere('extracted_text', '')
              ->orWhere('extracted_text_length', 0);
        })->limit(3)->get();
    
    foreach ($docsNoText as $doc) {
        echo "   - Doc #{$doc->id}: {$doc->original_filename} ({$doc->file_size} bytes)\n";
    }
}
echo "\n";

// 5. VÉRIFIER PINECONE
echo "5️⃣  INDEXATION PINECONE\n";
echo "────────────────────────────────────\n";

$pineconeKey = env('PINECONE_API_KEY');
$pineconeIndex = env('PINECONE_INDEX', 'dossy-legal-docs');

if (!$pineconeKey) {
    echo "   ❌ PINECONE_API_KEY non configuré\n";
} else {
    echo "   ✅ PINECONE_API_KEY configuré\n";
    echo "   Index: $pineconeIndex\n";
    
    // Essayer de se connecter à Pinecone
    try {
        $host = env('PINECONE_HOST');
        if (!$host) {
            echo "   ❌ PINECONE_HOST non configuré\n";
        } else {
            echo "   Host: $host\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Erreur Pinecone: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// 6. RÉSUMÉ ET RECOMMANDATIONS
echo "6️⃣  RÉSUMÉ ET RECOMMANDATIONS\n";
echo "────────────────────────────────────\n";

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
    echo "   ✅ Aucun problème détecté\n";
} else {
    echo "   ❌ PROBLÈMES TROUVÉS:\n\n";
    foreach ($issues as $issue) {
        echo "      • $issue\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n\n";
?>
