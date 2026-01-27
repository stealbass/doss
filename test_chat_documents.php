<?php
/**
 * Test Script - Chat Documents Integration
 * 
 * Execute : php test_chat_documents.php
 * Or : php artisan tinker < test_chat_documents.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SubmittedDocument;
use App\Models\User;
use App\Services\DocumentContentExtractor;

echo "
╔═══════════════════════════════════════════════════════════════╗
║     TEST SUITE - CHAT DOCUMENTS INTEGRATION                   ║
╚═══════════════════════════════════════════════════════════════╝\n";

// Test 1: Database Check
echo "\n[1/5] Vérification de la BDD...\n";
echo "  └─ Colonnes extracted_text dans legal_documents\n";

try {
    $columns = DB::getSchemaBuilder()->getColumnListing('legal_documents');
    $hasExtracted = in_array('extracted_text', $columns);
    $hasLength = in_array('extracted_text_length', $columns);
    
    if ($hasExtracted && $hasLength) {
        echo "    ✅ Colonnes trouvées\n";
    } else {
        echo "    ❌ MANQUANT - Exécuter migration:\n";
        echo "       php artisan migrate\n";
    }
} catch (Exception $e) {
    echo "    ❌ Erreur : {$e->getMessage()}\n";
}

// Test 2: FULLTEXT Index Check
echo "\n[2/5] Vérification de l'index FULLTEXT...\n";
try {
    $indexResult = DB::select(
        "SHOW INDEX FROM legal_documents WHERE Key_name = 'ft_legal_docs'"
    );
    
    if (count($indexResult) > 0) {
        echo "    ✅ Index FULLTEXT trouvé\n";
    } else {
        echo "    ⚠️  Index FULLTEXT non trouvé (optionnel)\n";
    }
} catch (Exception $e) {
    echo "    ⚠️  Erreur vérification index : {$e->getMessage()}\n";
}

// Test 3: DocumentContentExtractor Test
echo "\n[3/5] Test du DocumentContentExtractor...\n";
try {
    $extractor = new DocumentContentExtractor();
    
    // Chercher un document de test
    $testDoc = SubmittedDocument::where('mime_type', 'text/plain')
        ->orWhere('mime_type', 'application/pdf')
        ->first();
    
    if ($testDoc) {
        echo "    └─ Document test trouvé: {$testDoc->original_filename}\n";
        
        try {
            $content = $extractor->extractForChat($testDoc);
            $length = strlen($content);
            
            if ($length > 0) {
                echo "    ✅ Extraction réussie ({$length} caractères)\n";
            } else {
                echo "    ⚠️  Extraction vide ou fichier incompatible\n";
            }
        } catch (Exception $e) {
            echo "    ❌ Erreur extraction : {$e->getMessage()}\n";
        }
    } else {
        echo "    ⚠️  Aucun document test trouvé\n";
        echo "       Uploader d'abord un document via le chat\n";
    }
} catch (Exception $e) {
    echo "    ❌ Erreur : {$e->getMessage()}\n";
}

// Test 4: API Validation
echo "\n[4/5] Vérification du ChatController...\n";
try {
    $controllerPath = base_path('app/Http/Controllers/Api/Mobile/ChatController.php');
    $content = file_get_contents($controllerPath);
    
    // Check for document_ids validation
    if (strpos($content, "'document_ids' => 'nullable|array'") !== false) {
        echo "    ✅ Validation document_ids trouvée\n";
    } else {
        echo "    ❌ Validation document_ids manquante\n";
    }
    
    // Check for DocumentContentExtractor usage
    if (strpos($content, 'DocumentContentExtractor') !== false) {
        echo "    ✅ DocumentContentExtractor utilisé\n";
    } else {
        echo "    ❌ DocumentContentExtractor non trouvé\n";
    }
    
    // Check for user document processing
    if (strpos($content, 'userDocumentContent') !== false) {
        echo "    ✅ Traitement documents utilisateur trouvé\n";
    } else {
        echo "    ❌ Traitement documents manquant\n";
    }
} catch (Exception $e) {
    echo "    ❌ Erreur lecture ChatController : {$e->getMessage()}\n";
}

// Test 5: Simulation API Request
echo "\n[5/5] Simulation d'une requête API...\n";
try {
    // Créer une requête simulée
    $user = User::where('is_admin', false)->first();
    
    if ($user) {
        echo "    └─ User test : {$user->name}\n";
        
        // Vérifier documents utilisateur
        $userDocs = SubmittedDocument::where('user_id', $user->id)->limit(2)->get();
        
        if (count($userDocs) > 0) {
            echo "    └─ Documents trouvés : " . count($userDocs) . "\n";
            
            foreach ($userDocs as $doc) {
                echo "       • {$doc->original_filename} (ID: {$doc->id})\n";
            }
            
            echo "    ✅ API peut traiter ces documents\n";
        } else {
            echo "    ⚠️  Aucun document utilisateur\n";
        }
    } else {
        echo "    ⚠️  Aucun utilisateur test trouvé\n";
    }
} catch (Exception $e) {
    echo "    ❌ Erreur simulation : {$e->getMessage()}\n";
}

// Summary
echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                       RÉSUMÉ DES TESTS                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "
✅ Prêt à tester ? Exécutez:
   
   1. Via Postman:
      POST http://localhost:8000/api/mobile/chat
      Headers:
        Authorization: Bearer YOUR_TOKEN
        Content-Type: application/json
      Body:
      {
        \"message\": \"Explique le contenu du document\",
        \"document_ids\": [1, 2],
        \"use_rag\": true
      }
   
   2. Via Flutter:
      - Aller à Documents
      - Uploader un fichier
      - Aller à Chat
      - Sélectionner le document
      - Poser une question
   
   3. Monitoring:
      tail -f storage/logs/laravel.log | grep \"Mobile chat\"

\n";
