<?php

/**
 * Script de test pour vérifier la configuration OpenAI et Pinecone
 * 
 * Utilisation : php test_openai_pinecone.php
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║   Test de Configuration OpenAI & Pinecone - DossuPro         ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// Couleurs pour le terminal
$green = "\033[0;32m";
$red = "\033[0;31m";
$yellow = "\033[1;33m";
$blue = "\033[0;34m";
$reset = "\033[0m";

$allOk = true;

echo "═══════════════════════════════════════════════════════════════\n";
echo " 1. VÉRIFICATION DES MOBILE APP SETTINGS (Base de données)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Vérifier MobileAppSetting
$mobileSettings = DB::table('mobile_app_settings')->first();

if ($mobileSettings) {
    echo "{$green}✓ Mobile App Settings trouvés dans la base{$reset}\n\n";
    
    // OpenAI
    echo "OpenAI API Key (DB): ";
    if (!empty($mobileSettings->openai_api_key) && $mobileSettings->openai_api_key !== '...') {
        echo "{$green}✓ Configurée{$reset} (" . substr($mobileSettings->openai_api_key, 0, 10) . "...)\n";
    } else {
        echo "{$yellow}⚠ Non configurée{$reset}\n";
    }
    
    // Pinecone
    echo "Pinecone API Key (DB): ";
    if (!empty($mobileSettings->pinecone_api_key) && $mobileSettings->pinecone_api_key !== '...') {
        echo "{$green}✓ Configurée{$reset} (" . substr($mobileSettings->pinecone_api_key, 0, 10) . "...)\n";
    } else {
        echo "{$yellow}⚠ Non configurée{$reset}\n";
    }
    
    echo "Pinecone Environment (DB): ";
    if (!empty($mobileSettings->pinecone_environment)) {
        echo "{$green}✓ {$mobileSettings->pinecone_environment}{$reset}\n";
    } else {
        echo "{$yellow}⚠ Non défini{$reset}\n";
    }
    
    echo "Pinecone Index Name (DB): ";
    if (!empty($mobileSettings->pinecone_index_name)) {
        echo "{$green}✓ {$mobileSettings->pinecone_index_name}{$reset}\n";
    } else {
        echo "{$yellow}⚠ Non défini{$reset}\n";
    }
    
    // Flutterwave (pour comparaison)
    echo "\nFlutterwave Public Key (DB): ";
    if (!empty($mobileSettings->flutterwave_public_key)) {
        echo "{$green}✓ Configurée{$reset} (exemple: comme les paiements qui fonctionnent)\n";
    } else {
        echo "{$yellow}⚠ Non configurée{$reset}\n";
    }
} else {
    echo "{$red}✗ Aucune configuration Mobile App Settings trouvée!{$reset}\n";
    echo "   Allez dans: Admin → Mobile App Settings\n";
    $allOk = false;
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo " 2. VÉRIFICATION DES VARIABLES D'ENVIRONNEMENT (Fallback)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Vérifier OpenAI API Key
$openaiKey = env('OPENAI_API_KEY');
echo "OpenAI API Key: ";
if (!empty($openaiKey) && $openaiKey !== '...' && strlen($openaiKey) > 20) {
    echo "{$green}✓ Configurée{$reset} (" . substr($openaiKey, 0, 10) . "...)\n";
} else {
    echo "{$red}✗ Manquante ou invalide{$reset}\n";
    $allOk = false;
}

// Vérifier Pinecone API Key
$pineconeKey = env('PINECONE_API_KEY');
echo "Pinecone API Key: ";
if (!empty($pineconeKey) && $pineconeKey !== '...' && strlen($pineconeKey) > 20) {
    echo "{$green}✓ Configurée{$reset} (" . substr($pineconeKey, 0, 10) . "...)\n";
} else {
    echo "{$red}✗ Manquante ou invalide{$reset}\n";
    $allOk = false;
}

// Vérifier Pinecone Environment
$pineconeEnv = env('PINECONE_ENVIRONMENT');
echo "Pinecone Environment: ";
if (!empty($pineconeEnv)) {
    echo "{$green}✓ {$pineconeEnv}{$reset}\n";
} else {
    echo "{$yellow}⚠ Non défini (utilise défaut: gcp-starter){$reset}\n";
}

// Vérifier Pinecone Index
$pineconeIndex = env('PINECONE_INDEX');
echo "Pinecone Index: ";
if (!empty($pineconeIndex)) {
    echo "{$green}✓ {$pineconeIndex}{$reset}\n";
} else {
    echo "{$yellow}⚠ Non défini (utilise défaut: dossy-documents){$reset}\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo " 2. VÉRIFICATION CONFIG/SERVICES.PHP\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Vérifier config services.openai
$openaiConfig = config('services.openai.api_key');
echo "services.openai.api_key: ";
if (!empty($openaiConfig)) {
    echo "{$green}✓ Configuré{$reset}\n";
} else {
    echo "{$red}✗ Non configuré{$reset}\n";
    $allOk = false;
}

// Vérifier config services.pinecone
$pineconeConfig = config('services.pinecone.api_key');
echo "services.pinecone.api_key: ";
if (!empty($pineconeConfig)) {
    echo "{$green}✓ Configuré{$reset}\n";
} else {
    echo "{$yellow}⚠ Non configuré (utilise env directement){$reset}\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo " 3. TEST OPENAI SERVICE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    $openaiService = new App\Services\OpenAIService();
    echo "Service OpenAI: {$green}✓ Initialisé{$reset}\n";
    
    // Test simple
    echo "\nTest d'appel API OpenAI (message simple)...\n";
    $result = $openaiService->chatWithContext(
        'Réponds juste "Test réussi" si tu reçois ce message.',
        '',
        []
    );
    
    if ($result['success']) {
        echo "{$green}✓ API OpenAI fonctionnelle{$reset}\n";
        echo "  Modèle utilisé: " . ($result['model'] ?? 'N/A') . "\n";
        echo "  Tokens utilisés: " . ($result['tokens_used']['total'] ?? 'N/A') . "\n";
        echo "  Réponse: " . substr($result['message'], 0, 100) . "...\n";
    } else {
        echo "{$red}✗ Erreur API OpenAI{$reset}\n";
        echo "  Erreur: " . ($result['error'] ?? 'Unknown') . "\n";
        $allOk = false;
    }
} catch (\Exception $e) {
    echo "{$red}✗ Erreur lors de l'initialisation du service OpenAI{$reset}\n";
    echo "  Message: " . $e->getMessage() . "\n";
    $allOk = false;
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo " 4. TEST ADVANCED RAG SERVICE (PINECONE)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    $ragService = new App\Services\AdvancedRagService();
    echo "Service RAG: {$green}✓ Initialisé{$reset}\n";
    
    // On ne peut pas tester Pinecone sans données, mais on vérifie la config
    $reflection = new ReflectionClass($ragService);
    
    $openaiProperty = $reflection->getProperty('openaiApiKey');
    $openaiProperty->setAccessible(true);
    $openaiKeyValue = $openaiProperty->getValue($ragService);
    
    echo "\nConfiguration RAG Service:\n";
    echo "  OpenAI Key: ";
    if (!empty($openaiKeyValue)) {
        echo "{$green}✓ Présente{$reset}\n";
    } else {
        echo "{$red}✗ Manquante{$reset}\n";
        $allOk = false;
    }
    
    $pineconeProperty = $reflection->getProperty('pineconeApiKey');
    $pineconeProperty->setAccessible(true);
    $pineconeKeyValue = $pineconeProperty->getValue($ragService);
    
    echo "  Pinecone Key: ";
    if (!empty($pineconeKeyValue)) {
        echo "{$green}✓ Présente{$reset}\n";
    } else {
        echo "{$red}✗ Manquante{$reset}\n";
        $allOk = false;
    }
    
    $envProperty = $reflection->getProperty('pineconeEnvironment');
    $envProperty->setAccessible(true);
    $envValue = $envProperty->getValue($ragService);
    echo "  Pinecone Environment: {$green}{$envValue}{$reset}\n";
    
    $indexProperty = $reflection->getProperty('pineconeIndex');
    $indexProperty->setAccessible(true);
    $indexValue = $indexProperty->getValue($ragService);
    echo "  Pinecone Index: {$green}{$indexValue}{$reset}\n";
    
} catch (\Exception $e) {
    echo "{$red}✗ Erreur lors de l'initialisation du service RAG{$reset}\n";
    echo "  Message: " . $e->getMessage() . "\n";
    $allOk = false;
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo " 5. VÉRIFICATION BASE DE DONNÉES (ChatGPT Settings)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    $chatgptKey = DB::table('settings')
        ->where('name', 'chatgpt_key')
        ->where('created_by', 1)
        ->value('value');
    
    echo "ChatGPT Key (DB): ";
    if (!empty($chatgptKey)) {
        echo "{$green}✓ Configurée dans la base{$reset} (" . substr($chatgptKey, 0, 10) . "...)\n";
    } else {
        echo "{$yellow}⚠ Non configurée dans la base (utilise .env){$reset}\n";
    }
    
    $chatgptModel = DB::table('settings')
        ->where('name', 'chatgpt_model')
        ->where('created_by', 1)
        ->value('value');
    
    echo "ChatGPT Model (DB): ";
    if (!empty($chatgptModel)) {
        echo "{$green}✓ {$chatgptModel}{$reset}\n";
    } else {
        echo "{$yellow}⚠ Non configuré{$reset}\n";
    }
    
} catch (\Exception $e) {
    echo "{$red}✗ Erreur lors de la vérification de la base{$reset}\n";
    echo "  Message: " . $e->getMessage() . "\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo " 6. RÉSUMÉ\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($allOk) {
    echo "{$green}✓ TOUTES LES VÉRIFICATIONS SONT PASSÉES{$reset}\n";
    echo "\nLe système est prêt à utiliser OpenAI et Pinecone !\n";
} else {
    echo "{$red}✗ CERTAINES VÉRIFICATIONS ONT ÉCHOUÉ{$reset}\n";
    echo "\nActions recommandées:\n";
    echo "1. Vérifiez le fichier .env pour les clés API\n";
    echo "2. Assurez-vous que les clés sont complètes (pas '...')\n";
    echo "3. Effacez le cache: php artisan config:clear\n";
    echo "4. Relancez ce test\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo " AIDE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Pour obtenir vos clés API:\n";
echo "  • OpenAI: https://platform.openai.com/api-keys\n";
echo "  • Pinecone: https://app.pinecone.io/ → API Keys\n\n";

echo "Documentation:\n";
echo "  • Voir: ANALYSE_CONFIG_OPENAI_PINECONE.md\n\n";

echo "═══════════════════════════════════════════════════════════════\n\n";
