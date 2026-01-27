<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Foundation\Application;
use App\Services\AdvancedRagService;
use App\Models\MobileAppSetting;

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Foundation\Http\Kernel')->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 DEBUG SOURCES - Traçage Complet\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// 1. Vérifier config Pinecone
echo "1️⃣  VÉRIFICATION CONFIGURATION PINECONE:\n";
echo "   " . str_repeat("─", 50) . "\n";

$settings = MobileAppSetting::first();
if (!$settings) {
    echo "   ❌ ERREUR: Aucun MobileAppSetting trouvé!\n";
    exit(1);
}

echo "   ✓ Pinecone API Key: " . (substr($settings->pinecone_api_key, 0, 10) ?? '❌ VIDE') . "...\n";
echo "   ✓ Pinecone Environment: " . ($settings->pinecone_environment ?? '❌ VIDE') . "\n";
echo "   ✓ Pinecone Index: " . ($settings->pinecone_index_name ?? '❌ VIDE') . "\n";
echo "   ✓ OpenAI API Key: " . (substr($settings->openai_api_key, 0, 10) ?? '❌ VIDE') . "...\n\n";

// 2. Tester question de type "juridique"
echo "2️⃣  TEST QUESTION JURIDIQUE:\n";
echo "   " . str_repeat("─", 50) . "\n";

$testQuestion = "Quels sont les droits des employés en cas de licenciement?";
echo "   Question: \"$testQuestion\"\n";
echo "   Type détecté: juridique\n\n";

// 3. Appeler AdvancedRagService
echo "3️⃣  APPEL ADVANCEDRAGSERVICE:\n";
echo "   " . str_repeat("─", 50) . "\n";

$ragService = new AdvancedRagService();

// Reflectez les propriétés privées
$reflection = new ReflectionClass($ragService);

// Accédez aux propriétés privées via reflection
$properties = [
    'openaiApiKey' => 'OpenAI Key',
    'pineconeApiKey' => 'Pinecone Key',
    'pineconeEnvironment' => 'Pinecone Env',
    'pineconeIndex' => 'Pinecone Index',
];

foreach ($properties as $propName => $label) {
    try {
        $prop = $reflection->getProperty($propName);
        $prop->setAccessible(true);
        $value = $prop->getValue($ragService);
        
        if (strpos($label, 'Key') !== false) {
            echo "   ✓ {$label}: " . substr($value, 0, 10) . "...\n";
        } else {
            echo "   ✓ {$label}: {$value}\n";
        }
    } catch (Exception $e) {
        echo "   ❌ {$label}: Erreur - " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 4. Appeler getLibraryContext
echo "4️⃣  APPEL GETLIBRARYCONTEXT():\n";
echo "   " . str_repeat("─", 50) . "\n";

try {
    $result = $ragService->getLibraryContext(
        $testQuestion,
        ['legal_document'],  // Uniquement type juridique
        5,  // topK
        0.7 // threshold
    );
    
    echo "   Réponse retournée:\n";
    echo "   {\n";
    echo "     'context': " . (strlen($result['context']) > 50 ? "✓ " . strlen($result['context']) . " caractères" : "❌ VIDE") . "\n";
    echo "     'sources_count': " . count($result['sources']) . "\n";
    
    if (!empty($result['sources'])) {
        echo "     'sources': [\n";
        foreach ($result['sources'] as $idx => $source) {
            echo "       [\n";
            echo "         'id': " . ($source['id'] ?? '?') . ",\n";
            echo "         'title': " . substr($source['title'] ?? '?', 0, 30) . "...,\n";
            echo "         'type': " . ($source['type'] ?? '?') . ",\n";
            echo "         'file_path': " . ($source['file_path'] ?? '?') . "\n";
            echo "       ]\n";
            if ($idx > 2) {
                echo "       ... (" . (count($result['sources']) - 3) . " de plus)\n";
                break;
            }
        }
        echo "     ]\n";
    } else {
        echo "     'sources': ❌ TABLEAU VIDE !\n";
    }
    echo "   }\n";
    
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
    echo "   Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n";

// 5. Vérifier les logs
echo "5️⃣  VÉRIFIER LOGS LARAVEL:\n";
echo "   " . str_repeat("─", 50) . "\n";
echo "   Consultez: storage/logs/laravel.log\n";
echo "   Cherchez: 'Error querying Pinecone' ou 'Pinecone'\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "✅ TEST TERMINÉ\n";
echo "═══════════════════════════════════════════════════════════\n\n";
