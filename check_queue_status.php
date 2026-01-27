<?php
/**
 * Script pour vérifier l'état de la queue et des jobs en attente
 */

require __DIR__.'/bootstrap/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSTIC QUEUE SYSTEM ===\n\n";

// 1. Vérifier si la table jobs existe
echo "1. Vérification de l'existence de la table 'jobs':\n";
try {
    $jobsTableExists = DB::select("SHOW TABLES LIKE 'jobs'");
    if (empty($jobsTableExists)) {
        echo "   ❌ ERREUR: La table 'jobs' n'existe pas!\n";
        echo "   → Solution: Exécutez 'php artisan queue:table' puis 'php artisan migrate'\n\n";
    } else {
        echo "   ✅ La table 'jobs' existe\n\n";
        
        // 2. Compter les jobs en attente
        echo "2. Jobs en attente dans la queue:\n";
        $pendingJobs = DB::table('jobs')->count();
        echo "   Total: $pendingJobs jobs\n";
        
        if ($pendingJobs > 0) {
            echo "\n   Détails des jobs:\n";
            $jobs = DB::table('jobs')
                ->select('id', 'queue', 'payload', 'attempts', 'created_at')
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();
            
            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);
                $jobClass = $payload['displayName'] ?? 'Unknown';
                $data = unserialize($payload['data']['command'] ?? '');
                
                echo "   - Job ID: {$job->id}\n";
                echo "     Classe: {$jobClass}\n";
                echo "     Tentatives: {$job->attempts}\n";
                echo "     Créé: {$job->created_at}\n";
                
                // Si c'est ProcessDocumentForRAG, afficher l'ID du document
                if (strpos($jobClass, 'ProcessDocumentForRAG') !== false && $data) {
                    try {
                        $reflection = new ReflectionClass($data);
                        $property = $reflection->getProperty('documentId');
                        $property->setAccessible(true);
                        $documentId = $property->getValue($data);
                        echo "     Document ID: {$documentId}\n";
                    } catch (Exception $e) {
                        // Ignorer les erreurs de réflexion
                    }
                }
                echo "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n\n";
}

// 3. Vérifier les failed jobs
echo "\n3. Vérification des jobs échoués:\n";
try {
    $failedJobsTableExists = DB::select("SHOW TABLES LIKE 'failed_jobs'");
    if (!empty($failedJobsTableExists)) {
        $failedJobs = DB::table('failed_jobs')->count();
        echo "   Total jobs échoués: $failedJobs\n";
        
        if ($failedJobs > 0) {
            echo "\n   Derniers jobs échoués:\n";
            $failed = DB::table('failed_jobs')
                ->select('id', 'connection', 'queue', 'payload', 'exception', 'failed_at')
                ->orderBy('failed_at', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($failed as $job) {
                $payload = json_decode($job->payload, true);
                $jobClass = $payload['displayName'] ?? 'Unknown';
                
                echo "   - Job ID: {$job->id}\n";
                echo "     Classe: {$jobClass}\n";
                echo "     Queue: {$job->queue}\n";
                echo "     Échoué: {$job->failed_at}\n";
                echo "     Erreur: " . substr($job->exception, 0, 200) . "...\n\n";
            }
        }
    } else {
        echo "   ⚠️  La table 'failed_jobs' n'existe pas\n";
    }
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n\n";
}

// 4. Vérifier le statut du document 48
echo "\n4. Statut du document 48:\n";
try {
    $doc = DB::table('submitted_documents')
        ->where('id', 48)
        ->first();
    
    if ($doc) {
        echo "   Document trouvé:\n";
        echo "   - ID: {$doc->id}\n";
        echo "   - Nom: {$doc->original_filename}\n";
        echo "   - Téléchargé: {$doc->created_at}\n";
        echo "   - Texte extrait: " . (strlen($doc->extracted_text ?? '') > 0 ? "OUI (" . strlen($doc->extracted_text) . " caractères)" : "NON") . "\n";
        echo "   - Indexé Pinecone: " . ($doc->is_indexed ?? 0 ? "OUI" : "NON") . "\n";
    } else {
        echo "   ❌ Document 48 non trouvé\n";
    }
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DU DIAGNOSTIC ===\n";
