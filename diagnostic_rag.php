<?php
/**
 * 🔍 Diagnostic RAG Complet
 * Teste chaque étape: extraction, indexation, recherche
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\SubmittedDocument;
use App\Services\AdvancedRagService;

// Initialiser Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════╗\n";
echo "║   🔍 DIAGNOSTIC RAG - Document Reading    ║\n";
echo "╚════════════════════════════════════════════╝\n";
echo "\n";

// 1. Chercher dernier document
echo "1️⃣  VÉRIFIER DERNIER DOCUMENT\n";
echo "─────────────────────────────────\n";

$doc = SubmittedDocument::orderBy('id', 'desc')->first();

if (!$doc) {
    echo "❌ Aucun document trouvé. Veuillez en uploader un.\n";
    exit(1);
}

echo "✅ Document trouvé:\n";
echo "   ID: {$doc->id}\n";
echo "   Filename: {$doc->original_filename}\n";
echo "   User: {$doc->user->name ?? 'Unknown'}\n";
echo "   Status: {$doc->processing_status}\n";
echo "\n";

// 2. Vérifier storage_path
echo "2️⃣  VÉRIFIER STORAGE PATH\n";
echo "─────────────────────────────────\n";

if (empty($doc->storage_path)) {
    echo "❌ CRITIQUE: storage_path est VIDE!\n";
    echo "   Cela signifie que DocumentController n'a pas enregistré le chemin.\n";
    echo "   Le fichier ne peut pas être trouvé pour extraction.\n";
    echo "\n   Solution: Redéployer DocumentController.php et réuploader.\n";
    exit(1);
}

echo "✅ Storage path présent: {$doc->storage_path}\n";
echo "\n";

// 3. Vérifier extraction
echo "3️⃣  VÉRIFIER EXTRACTION\n";
echo "─────────────────────────────────\n";

if (empty($doc->extracted_text)) {
    echo "❌ Texte extrait VIDE\n";
    echo "   Cela signifie que ProcessDocumentForRAG n'a pas fonctionné.\n";
    echo "\n   Causes possibles:\n";
    echo "   - Python script n'a pas trouvé le fichier\n";
    echo "   - boto3 non installé (pour R2)\n";
    echo "   - Queue worker pas en cours d'exécution\n";
    echo "\n   Solution: \n";
    echo "   1. Vérifier les logs: tail -f storage/logs/laravel.log\n";
    echo "   2. Relancer queue worker: php artisan queue:work\n";
    echo "   3. Vérifier Python: python3 scripts/extract_documents.py --document-id {$doc->id}\n";
    exit(1);
}

echo "✅ Texte extrait présent\n";
echo "   Longueur: " . strlen($doc->extracted_text) . " caractères\n";
echo "   Preview: " . substr($doc->extracted_text, 0, 100) . "...\n";
echo "\n";

// 4. Vérifier Pinecone indexation
echo "4️⃣  VÉRIFIER PINECONE INDEXATION\n";
echo "─────────────────────────────────\n";

$ragService = app(AdvancedRagService::class);

try {
    $results = $ragService->searchSpecificDocuments(
        "test search",
        $doc->user_id,
        [$doc->id],
        5
    );
    
    if (count($results) > 0) {
        echo "✅ Pinecone INDEXÉ: " . count($results) . " match(s) trouvés\n";
        foreach ($results as $i => $r) {
            echo "   [{$i}] Score: {$r['score']}, Text: " . substr($r['text'] ?? '', 0, 80) . "\n";
        }
    } else {
        echo "❌ Pinecone VIDE - Document pas indexé\n";
        echo "   Cela signifie que AdvancedRagService::indexDocument n'a pas réussi.\n";
        echo "\n   Causes possibles:\n";
        echo "   - API keys Pinecone/OpenAI invalides\n";
        echo "   - Erreur lors de la génération d'embeddings\n";
        echo "   - Chunk text vide\n";
        echo "\n   Solution: Vérifier les logs du job ProcessDocumentForRAG\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur Pinecone: {$e->getMessage()}\n";
}
echo "\n";

// 5. Tester recherche local (fallback)
echo "5️⃣  TESTER RECHERCHE LOCALE (Fallback)\n";
echo "─────────────────────────────────\n";

try {
    $localResults = $ragService->searchUserDocuments(
        "test",
        $doc->user_id,
        5
    );
    
    if (count($localResults) > 0) {
        echo "✅ Recherche locale fonctionnelle: " . count($localResults) . " résultat(s)\n";
    } else {
        echo "⚠️ Recherche locale: 0 résultats\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur recherche locale: {$e->getMessage()}\n";
}
echo "\n";

// 6. Résumé
echo "╔════════════════════════════════════════════╗\n";
echo "║   📊 RÉSUMÉ DU DIAGNOSTIC                 ║\n";
echo "╚════════════════════════════════════════════╝\n";
echo "\n";

$checks = [
    'storage_path non-vide' => !empty($doc->storage_path),
    'extracted_text non-vide' => !empty($doc->extracted_text),
    'Pinecone indexé' => count($results ?? []) > 0,
    'Fallback local fonctionnel' => count($localResults ?? []) > 0,
];

$passed = 0;
$failed = 0;

foreach ($checks as $check => $status) {
    $icon = $status ? '✅' : '❌';
    echo "$icon $check\n";
    if ($status) $passed++; else $failed++;
}

echo "\n";
echo "Résultat: $passed/4 checks passed\n";

if ($failed === 0) {
    echo "\n🎉 TOUT FONCTIONNE! Le document est correctement indexé.\n";
    echo "L'IA devrait maintenant pouvoir lire le document dans le chat.\n";
} else {
    echo "\n⚠️ PROBLÈME DÉTECTÉ: Vérifier les étapes marquées ❌\n";
}
echo "\n";
?>
