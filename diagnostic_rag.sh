#!/bin/bash

# 🔍 Diagnostic Script - Vérifier extraction, indexation, et chat

echo "==============================================="
echo "🔍 DIAGNOSTIC COMPLET - Documents RAG"
echo "==============================================="
echo ""

# 1. Vérifier les documents en base de données
echo "1️⃣  DOCUMENTS EN BASE DE DONNÉES"
echo "-----------------------------------"
php artisan tinker << 'EOF'
$docs = \App\Models\SubmittedDocument::with('user')
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();

foreach ($docs as $doc) {
    echo "\n📄 Document ID {$doc->id} - {$doc->original_filename}\n";
    echo "   User: {$doc->user->name}\n";
    echo "   Status: {$doc->processing_status}\n";
    echo "   Storage Path: " . ($doc->storage_path ?: '(NULL - PROBLÈME!)') . "\n";
    echo "   Extracted Text: " . (strlen($doc->extracted_text ?? '') > 0 ? 'YES (' . strlen($doc->extracted_text) . ' chars)' : 'NO') . "\n";
    echo "   Error: " . ($doc->processing_error ?: 'None') . "\n";
}
EOF
echo ""

# 2. Vérifier Pinecone
echo "2️⃣  VÉRIFIER PINECONE"
echo "-----------------------------------"
php artisan tinker << 'EOF'
$ragService = app(\App\Services\AdvancedRagService::class);

// Récupérer le dernier document uploadé
$doc = \App\Models\SubmittedDocument::orderBy('id', 'desc')->first();

if (!$doc) {
    echo "❌ Aucun document trouvé\n";
    exit;
}

echo "📍 Recherche dans Pinecone pour document ID {$doc->id}\n";

// Essayer une requête simple
try {
    $results = $ragService->searchSpecificDocuments(
        "test",
        $doc->user_id,
        [$doc->id],
        5
    );
    
    echo "   Résultats Pinecone: " . count($results) . " match(s)\n";
    
    if (count($results) > 0) {
        echo "   ✅ Pinecone a des vecteurs pour ce document\n";
        foreach ($results as $r) {
            echo "      - Score: {$r['score']}, Text: " . substr($r['text'], 0, 50) . "...\n";
        }
    } else {
        echo "   ❌ Pinecone VIDE pour ce document\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur Pinecone: {$e->getMessage()}\n";
}
EOF
echo ""

# 3. Vérifier extraction locale
echo "3️⃣  VÉRIFIER EXTRACTION LOCALE"
echo "-----------------------------------"
php artisan tinker << 'EOF'
$doc = \App\Models\SubmittedDocument::orderBy('id', 'desc')->first();

if (!$doc || !$doc->extracted_text) {
    echo "❌ Aucun texte extrait trouvé\n";
    exit;
}

echo "✅ Texte extrait présent\n";
echo "   Longueur: " . strlen($doc->extracted_text) . " caractères\n";
echo "   Preview: " . substr($doc->extracted_text, 0, 100) . "...\n";
EOF
echo ""

# 4. Vérifier Queue
echo "4️⃣  VÉRIFIER QUEUE"
echo "-----------------------------------"
php artisan queue:failed-jobs
echo ""

# 5. Vérifier logs récents
echo "5️⃣  LOGS RÉCENTS (ProcessDocumentForRAG)"
echo "-----------------------------------"
tail -50 storage/logs/laravel.log | grep -i "document\|extraction\|pinecone\|processing" || echo "Aucun log relevé"
echo ""

echo "==============================================="
echo "📋 RÉSUMÉ DES VÉRIFICATIONS"
echo "==============================================="
echo ""
echo "Points à vérifier:"
echo "1. ✅ Storage Path rempli en DB? (non NULL)"
echo "2. ✅ Extracted Text présent? (non NULL)"
echo "3. ✅ Pinecone a des vecteurs? (searchSpecificDocuments retourne résultats)"
echo "4. ✅ Queue worker en cours? (pas de failed jobs)"
echo "5. ✅ Logs montrent extraction réussie?"
echo ""
echo "Si StoragePath est NULL ou vide:"
echo "  → Redéployer DocumentController.php"
echo "  → Réuploader un document"
echo ""
echo "Si Extraction échoue:"
echo "  → Vérifier logs du job"
echo "  → python3/python disponible?"
echo "  → Fichier trouvé (local ou R2)?"
echo ""
echo "Si Pinecone vide:"
echo "  → AdvancedRagService::indexDocument appelé?"
echo "  → API keys Pinecone/OpenAI valides?"
echo ""
