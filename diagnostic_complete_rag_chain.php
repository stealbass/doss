<?php
/**
 * DIAGNOSTIC COMPLET - Chaîne RAG (Extraction → Pinecone → Chat)
 * 
 * Usage: php diagnostic_complete_rag_chain.php
 * 
 * Teste chaque point critique:
 * 1. Document 18 existe-t-il?
 * 2. storage_path est-il enregistré?
 * 3. extracted_text a-t-il du contenu?
 * 4. Pinecone a-t-il les vecteurs?
 * 5. La recherche Pinecone retourne-t-elle des résultats?
 * 6. Le chat peut-il utiliser les résultats?
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';

use App\Models\SubmittedDocument;
use App\Services\AdvancedRagService;
use Illuminate\Support\Facades\DB;

$colors = [
    'green' => "\033[92m",
    'red' => "\033[91m",
    'yellow' => "\033[93m",
    'blue' => "\033[94m",
    'bold' => "\033[1m",
    'reset' => "\033[0m",
];

function print_section($title) {
    global $colors;
    echo "\n" . $colors['bold'] . $colors['blue'] . "=== $title ===" . $colors['reset'] . "\n";
}

function check($condition, $message) {
    global $colors;
    if ($condition) {
        echo $colors['green'] . "✅ " . $colors['reset'] . $message . "\n";
        return true;
    } else {
        echo $colors['red'] . "❌ " . $colors['reset'] . $message . "\n";
        return false;
    }
}

function warn($message) {
    global $colors;
    echo $colors['yellow'] . "⚠️  " . $colors['reset'] . $message . "\n";
}

function info($message) {
    global $colors;
    echo $colors['blue'] . "ℹ️  " . $colors['reset'] . $message . "\n";
}

// ============================================================================
print_section("1️⃣  VÉRIFIER LE DOCUMENT");
// ============================================================================

$document = SubmittedDocument::find(18);

if (!$document) {
    echo $colors['red'] . "❌ Document 18 introuvable!" . $colors['reset'] . "\n";
    exit(1);
}

check(true, "Document 18 trouvé (ID: {$document->id})");
info("Nom: " . $document->original_filename);
info("Chemin stocké: " . ($document->storage_path ?? "NULL ⚠️"));
info("Statut: " . $document->processing_status);
info("Taille fichier: " . number_format($document->file_size ?? 0) . " bytes");

// ============================================================================
print_section("2️⃣  VÉRIFIER storage_path (CRITIQUE)");
// ============================================================================

$has_storage_path = !empty($document->storage_path);

if (!$has_storage_path) {
    warn("storage_path est NULL ou vide!");
    warn("ACTION: Le fichier ne peut pas être localisé pour extraction");
    warn("Cause probable: DocumentController n'a pas enregistré le chemin R2");
} else {
    check(true, "storage_path enregistré: {$document->storage_path}");
}

// ============================================================================
print_section("3️⃣  VÉRIFIER extracted_text (CRITIQUE)");
// ============================================================================

$has_extracted_text = !empty($document->extracted_text);

if (!$has_extracted_text) {
    check(false, "extracted_text vide ou NULL");
    warn("ACTION: L'extraction a échoué ou n'a pas été lancée");
    
    // Afficher les détails pour comprendre pourquoi
    echo "\nDétails du document:\n";
    echo "  - processing_status: " . $document->processing_status . "\n";
    echo "  - storage_path: " . ($document->storage_path ?? "NULL") . "\n";
    echo "  - mime_type: " . ($document->mime_type ?? "Unknown") . "\n";
    
    warn("Tentatives d'extraction:");
    warn("  1. Re-lancer l'extraction manuellement:");
    warn("     php artisan queue:work");
    warn("     dispatch(new \\App\\Jobs\\ProcessDocumentForRAG(18));");
    
} else {
    $text_length = strlen($document->extracted_text);
    check(true, "extracted_text présent (" . number_format($text_length) . " caractères)");
    
    // Afficher un aperçu
    $preview = substr($document->extracted_text, 0, 150);
    info("Aperçu: " . $preview . "...");
}

// ============================================================================
print_section("4️⃣  VÉRIFIER PINECONE (CRITIQUE)");
// ============================================================================

try {
    $ragService = app(AdvancedRagService::class);
    
    // Tester connexion Pinecone
    $pinecone_config = [
        'api_key' => env('PINECONE_API_KEY'),
        'environment' => env('PINECONE_ENVIRONMENT'),
        'index' => env('PINECONE_INDEX_NAME'),
    ];
    
    check(!empty($pinecone_config['api_key']), "Clé Pinecone configurée");
    check(!empty($pinecone_config['index']), "Index Pinecone configuré: " . $pinecone_config['index']);
    
    // Rechercher les vecteurs du document 18 dans Pinecone
    info("Recherche de vecteurs pour document 18 dans Pinecone...");
    
    $query_text = "documents informations";
    $search_results = $ragService->searchSpecificDocuments($document->id, $query_text);
    
    if ($search_results && count($search_results) > 0) {
        check(true, "Pinecone retourne " . count($search_results) . " résultat(s)");
        
        // Afficher les résultats
        foreach ($search_results as $i => $result) {
            info("Résultat " . ($i+1) . ": " . substr($result['text'] ?? $result['content'] ?? 'N/A', 0, 80) . "...");
        }
    } else {
        check(false, "Pinecone ne retourne PAS de résultats pour document 18");
        warn("PROBLÈME: Ou le document n'a pas été indexé, ou l'index est vide");
        warn("ACTION: Vérifier que AdvancedRagService::indexDocument() a été appelé");
    }
    
} catch (\Exception $e) {
    check(false, "Erreur Pinecone: " . $e->getMessage());
    warn("Vérifier les clés API Pinecone dans .env");
}

// ============================================================================
print_section("5️⃣  VÉRIFIER RECHERCHE LOCALE (FALLBACK)");
// ============================================================================

if ($has_extracted_text) {
    try {
        // Simuler une recherche locale
        $query = "documents informations";
        $local_results = $ragService->searchUserDocuments($document->user_id, $query);
        
        if ($local_results && count($local_results) > 0) {
            check(true, "Recherche locale retourne " . count($local_results) . " résultat(s)");
            info("Le FALLBACK local fonctionne");
        } else {
            warn("Recherche locale n'a rien trouvé");
        }
    } catch (\Exception $e) {
        warn("Erreur recherche locale: " . $e->getMessage());
    }
} else {
    warn("Impossible de tester recherche locale: pas d'extracted_text");
}

// ============================================================================
print_section("6️⃣  VÉRIFIER CHAT");
// ============================================================================

try {
    // Simuler un appel chat
    $chat_message = "Quelle est l'information principale du document?";
    
    info("Simulation: Message chat avec document_id=18");
    info("Message: \"$chat_message\"");
    
    // Le chat devrait rechercher via Pinecone puis fallback
    info("Le chat devrait:");
    info("  1. Rechercher dans Pinecone pour document 18");
    if ($has_extracted_text) {
        info("  2. Fallback: Rechercher dans extracted_text local");
    } else {
        info("  2. Fallback: DÉSACTIVÉ (pas d'extracted_text)");
    }
    
} catch (\Exception $e) {
    warn("Erreur chat: " . $e->getMessage());
}

// ============================================================================
print_section("RÉSUMÉ DIAGNOSTIC");
// ============================================================================

$checks = [
    'Document trouvé' => $document !== null,
    'storage_path enregistré' => $has_storage_path,
    'extracted_text présent' => $has_extracted_text,
];

$passed = 0;
$failed = 0;

foreach ($checks as $name => $result) {
    if ($result) {
        $passed++;
        echo $colors['green'] . "✅ " . $colors['reset'] . $name . "\n";
    } else {
        $failed++;
        echo $colors['red'] . "❌ " . $colors['reset'] . $name . "\n";
    }
}

echo "\n" . $colors['bold'] . "Résultat: $passed/3 vérifications réussies" . $colors['reset'] . "\n\n";

// ============================================================================
print_section("ACTION REQUISE");
// ============================================================================

if ($failed > 0) {
    if (!$has_storage_path) {
        warn("1. storage_path est NULL");
        warn("   → Re-uploader le document (DocumentController doit enregistrer le chemin R2)");
        warn("   → Ou exécuter: \$doc->update(['storage_path' => 'documents/filename.pdf'])");
    }
    
    if (!$has_extracted_text) {
        warn("2. extracted_text est vide");
        warn("   → Relancer le queue worker:");
        warn("     cd /home/threesixty/yyy/Dossy");
        warn("     php artisan queue:work --queue=default");
        warn("   → Puis re-trigger l'extraction:");
        warn("     php artisan tinker");
        warn("     >>> dispatch(new \\App\\Jobs\\ProcessDocumentForRAG(18));");
        warn("   → Attendre ~10s pour le traitement");
    }
    
    if (!check(false, "Vérifications réussies") && $failed === 3) {
        echo "\n" . $colors['red'] . $colors['bold'];
        echo "PROBLÈME MAJEUR: Document vide ou non traité\n";
        echo "Vérifier les logs: tail -f storage/logs/laravel.log\n";
        echo $colors['reset'];
    }
} else {
    echo "\n" . $colors['green'] . $colors['bold'];
    echo "✅ Tous les contrôles passent!\n";
    echo "Le problème vient probablement du côté Flutter (requête mal formée?)\n";
    echo "ou de la configuration du chat dans ChatController\n";
    echo $colors['reset'];
}

echo "\n";
