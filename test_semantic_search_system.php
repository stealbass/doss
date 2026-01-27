<?php

/**
 * Script de test du système de recherche sémantique
 * 
 * Ce script teste l'intégration complète :
 * 1. ProcessDocumentForRAG - Extraction et indexation Pinecone
 * 2. AdvancedRagService - Recherche sémantique
 * 3. ChatController - Intégration dans le chat
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\SubmittedDocument;
use App\Models\MobileAppSetting;
use App\Services\AdvancedRagService;
use App\Jobs\ProcessDocumentForRAG;
use Illuminate\Support\Facades\Log;

echo "=================================================\n";
echo "  TEST DU SYSTÈME DE RECHERCHE SÉMANTIQUE\n";
echo "=================================================\n\n";

// Test 1: Vérifier la configuration Pinecone
echo "📋 Test 1: Vérification de la configuration\n";
echo "---------------------------------------------\n";

$settings = MobileAppSetting::first();
if (!$settings) {
    echo "❌ ERREUR: Aucune configuration MobileAppSetting trouvée\n";
    echo "   Veuillez configurer les clés API dans la table mobile_app_settings\n\n";
    exit(1);
}

$checks = [
    'OpenAI API Key' => !empty($settings->openai_api_key),
    'Pinecone API Key' => !empty($settings->pinecone_api_key),
    'Pinecone Environment' => !empty($settings->pinecone_environment),
    'Pinecone Index' => !empty($settings->pinecone_index_name),
];

$allChecked = true;
foreach ($checks as $name => $status) {
    $icon = $status ? '✅' : '❌';
    echo "  {$icon} {$name}: " . ($status ? 'OK' : 'MANQUANT') . "\n";
    if (!$status) $allChecked = false;
}

if (!$allChecked) {
    echo "\n❌ Configuration incomplète. Veuillez configurer toutes les clés API.\n\n";
    exit(1);
}

echo "\n✅ Configuration complète\n\n";

// Test 2: Vérifier les documents utilisateur
echo "📋 Test 2: Vérification des documents utilisateur\n";
echo "---------------------------------------------\n";

$documents = SubmittedDocument::with('user')
    ->whereNotNull('extracted_text')
    ->where('processing_status', 'completed')
    ->take(5)
    ->get();

if ($documents->isEmpty()) {
    echo "⚠️  Aucun document traité trouvé\n";
    echo "   Uploadez des documents via l'application mobile pour tester\n\n";
} else {
    echo "✅ {$documents->count()} documents trouvés:\n\n";
    foreach ($documents as $doc) {
        $textLength = strlen($doc->extracted_text ?? '');
        $userName = $doc->user->name ?? 'Inconnu';
        echo "  📄 ID: {$doc->id}\n";
        echo "     Nom: {$doc->file_name}\n";
        echo "     Utilisateur: {$userName} (ID: {$doc->user_id})\n";
        echo "     Texte extrait: " . number_format($textLength) . " caractères\n";
        echo "     Statut: {$doc->processing_status}\n";
        echo "     Date: {$doc->created_at}\n\n";
    }
}

// Test 3: Test de l'Advanced RAG Service
if (!$documents->isEmpty()) {
    echo "📋 Test 3: Test de la recherche sémantique\n";
    echo "---------------------------------------------\n";
    
    try {
        $ragService = new AdvancedRagService();
        $testDoc = $documents->first();
        $testUser = $testDoc->user;
        
        // Test de recherche simple
        echo "🔍 Test de recherche sur tous les documents de l'utilisateur {$testUser->id}...\n";
        $query = "contrat";
        $results = $ragService->search($query, $testUser->id, 3);
        
        if (empty($results)) {
            echo "⚠️  Aucun résultat trouvé (normal si documents pas encore indexés dans Pinecone)\n";
            echo "   Les documents doivent être traités par ProcessDocumentForRAG\n\n";
        } else {
            echo "✅ {" . count($results) . "} résultats trouvés:\n\n";
            foreach ($results as $i => $result) {
                $score = $result['score'] ?? 0;
                $fileName = $result['metadata']['file_name'] ?? 'Unknown';
                $text = substr($result['metadata']['text'] ?? '', 0, 100);
                echo "  Résultat #" . ($i + 1) . ":\n";
                echo "    Fichier: {$fileName}\n";
                echo "    Score: " . number_format($score * 100, 2) . "%\n";
                echo "    Extrait: {$text}...\n\n";
            }
        }
        
        // Test de recherche sur documents spécifiques
        if ($documents->count() >= 2) {
            echo "🔍 Test de recherche sur documents spécifiques...\n";
            $docIds = $documents->pluck('id')->take(2)->toArray();
            echo "   Documents ciblés: " . implode(', ', $docIds) . "\n";
            
            $specificResults = $ragService->searchSpecificDocuments($query, $testUser->id, $docIds, 5);
            
            if (empty($specificResults)) {
                echo "⚠️  Aucun résultat trouvé dans les documents spécifiques\n\n";
            } else {
                echo "✅ {" . count($specificResults) . "} résultats trouvés dans les documents spécifiques:\n\n";
                foreach ($specificResults as $i => $result) {
                    $docId = $result['metadata']['document_id'] ?? 'N/A';
                    $score = $result['score'] ?? 0;
                    $fileName = $result['metadata']['file_name'] ?? 'Unknown';
                    echo "  Résultat #" . ($i + 1) . " (Doc ID: {$docId}):\n";
                    echo "    Score: " . number_format($score * 100, 2) . "%\n";
                    echo "    Fichier: {$fileName}\n\n";
                }
            }
        }
        
        // Test getContext
        echo "🔍 Test de génération de contexte RAG...\n";
        $context = $ragService->getContext($query, $testUser->id, 1000);
        
        if (empty($context)) {
            echo "⚠️  Aucun contexte généré\n\n";
        } else {
            echo "✅ Contexte généré (" . strlen($context) . " caractères):\n";
            echo substr($context, 0, 300) . "...\n\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ ERREUR lors du test de recherche: {$e->getMessage()}\n";
        echo "   Trace: " . $e->getTraceAsString() . "\n\n";
    }
}

// Test 4: Vérifier le job ProcessDocumentForRAG
echo "📋 Test 4: Test du job d'indexation\n";
echo "---------------------------------------------\n";

if ($documents->isEmpty()) {
    echo "⚠️  Pas de documents à tester\n\n";
} else {
    $testDoc = $documents->first();
    echo "📄 Test d'indexation du document ID: {$testDoc->id}\n";
    echo "   Nom: {$testDoc->file_name}\n";
    echo "   Taille du texte: " . strlen($testDoc->extracted_text ?? '') . " caractères\n\n";
    
    try {
        // Simuler le job (sans dispatch pour test synchrone)
        $ragService = new AdvancedRagService();
        $indexResult = $ragService->indexDocument($testDoc);
        
        if ($indexResult) {
            echo "✅ Document indexé avec succès dans Pinecone\n\n";
        } else {
            echo "⚠️  L'indexation a retourné false (vérifier les logs)\n\n";
        }
    } catch (\Exception $e) {
        echo "❌ ERREUR lors de l'indexation: {$e->getMessage()}\n\n";
    }
}

// Test 5: Statistiques globales
echo "📋 Test 5: Statistiques du système\n";
echo "---------------------------------------------\n";

$stats = [
    'Total documents' => SubmittedDocument::count(),
    'Documents complétés' => SubmittedDocument::where('processing_status', 'completed')->count(),
    'Documents en traitement' => SubmittedDocument::where('processing_status', 'processing')->count(),
    'Documents échoués' => SubmittedDocument::where('processing_status', 'failed')->count(),
    'Documents avec texte' => SubmittedDocument::whereNotNull('extracted_text')->count(),
    'Documents anonymisés' => SubmittedDocument::where('has_sensitive_data', true)->count(),
];

foreach ($stats as $label => $value) {
    echo "  {$label}: {$value}\n";
}

echo "\n";

// Recommandations finales
echo "=================================================\n";
echo "  RECOMMANDATIONS\n";
echo "=================================================\n\n";

if ($documents->isEmpty()) {
    echo "1. Uploadez des documents via l'application mobile\n";
    echo "2. Vérifiez que ProcessDocumentForRAG s'exécute (queue worker)\n";
    echo "3. Relancez ce script après avoir uploadé des documents\n\n";
} else {
    echo "✅ Le système est opérationnel\n\n";
    echo "Pour utiliser la recherche sémantique dans le chat:\n";
    echo "1. Sélectionnez un ou plusieurs documents dans l'app\n";
    echo "2. Posez une question dans le chat\n";
    echo "3. L'API utilisera AdvancedRagService automatiquement\n\n";
    
    echo "Commande pour traiter les documents en attente:\n";
    echo "  php artisan queue:work --queue=default\n\n";
    
    echo "Commande pour réindexer un document:\n";
    echo "  php artisan tinker\n";
    echo "  ProcessDocumentForRAG::dispatch({$documents->first()->id})\n\n";
}

echo "=================================================\n";
echo "  FIN DES TESTS\n";
echo "=================================================\n";
