<?php
/**
 * Script de Test des Dépendances Phase 2
 * 
 * Ce script vérifie que toutes les dépendances nécessaires
 * pour la Phase 2 (RAG Services + Mobile API) sont correctement installées.
 * 
 * Usage: php test_dependencies.php
 */

require __DIR__ . '/vendor/autoload.php';

echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "  🧪 TEST DES DÉPENDANCES - PHASE 2\n";
echo "═══════════════════════════════════════════════════════════════════════════\n\n";

$allPassed = true;

// ===========================
// 1. Test PHP Version
// ===========================
echo "1. PHP Version\n";
echo "   Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    echo "   ✅ PHP 8.2+ détecté\n\n";
} else {
    echo "   ❌ PHP 8.2+ requis (version actuelle: " . PHP_VERSION . ")\n\n";
    $allPassed = false;
}

// ===========================
// 2. Test OpenAI Client
// ===========================
echo "2. OpenAI PHP Client\n";
try {
    if (class_exists('OpenAI\Client')) {
        echo "   ✅ openai-php/client installé\n";
        
        // Tester avec clé API si disponible
        if (getenv('OPENAI_API_KEY')) {
            echo "   ✅ OPENAI_API_KEY configurée\n";
            // Note: Ne pas faire d'appel API réel ici pour éviter les coûts
        } else {
            echo "   ⚠️  OPENAI_API_KEY non configurée dans .env\n";
        }
    } else {
        echo "   ❌ openai-php/client non trouvé\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// ===========================
// 3. Test Alternative OpenAI (orhanerday)
// ===========================
echo "3. OpenAI Alternative (orhanerday/open-ai)\n";
try {
    if (class_exists('Orhanerday\OpenAi\OpenAi')) {
        echo "   ✅ orhanerday/open-ai installé (alternative OpenAI client)\n";
    } else {
        echo "   ⚠️  orhanerday/open-ai non trouvé (optionnel)\n";
    }
} catch (Exception $e) {
    echo "   ⚠️  Erreur: " . $e->getMessage() . "\n";
}
echo "\n";

// ===========================
// 4. Test Pinecone Client
// ===========================
echo "4. Pinecone PHP SDK\n";
try {
    if (class_exists('Probots\Pinecone\Client')) {
        echo "   ✅ probots-io/pinecone-php installé\n";
        
        // Vérifier config Pinecone
        if (getenv('PINECONE_API_KEY')) {
            echo "   ✅ PINECONE_API_KEY configurée\n";
        } else {
            echo "   ⚠️  PINECONE_API_KEY non configurée dans .env\n";
        }
        
        if (getenv('PINECONE_ENVIRONMENT')) {
            echo "   ✅ PINECONE_ENVIRONMENT configuré (" . getenv('PINECONE_ENVIRONMENT') . ")\n";
        } else {
            echo "   ⚠️  PINECONE_ENVIRONMENT non configuré dans .env\n";
        }
        
        if (getenv('PINECONE_INDEX')) {
            echo "   ✅ PINECONE_INDEX configuré (" . getenv('PINECONE_INDEX') . ")\n";
        } else {
            echo "   ⚠️  PINECONE_INDEX non configuré dans .env\n";
        }
    } else {
        echo "   ❌ probots-io/pinecone-php non trouvé\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// ===========================
// 5. Test PDF Parser
// ===========================
echo "5. PDF Parser (smalot/pdfparser)\n";
try {
    if (class_exists('Smalot\PdfParser\Parser')) {
        echo "   ✅ smalot/pdfparser installé\n";
        
        // Tester instanciation
        $parser = new \Smalot\PdfParser\Parser();
        echo "   ✅ Parser PDF instancié avec succès\n";
    } else {
        echo "   ❌ smalot/pdfparser non trouvé\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// ===========================
// 6. Test Laravel Sanctum
// ===========================
echo "6. Laravel Sanctum\n";
try {
    if (class_exists('Laravel\Sanctum\Sanctum')) {
        echo "   ✅ laravel/sanctum installé\n";
    } else {
        echo "   ❌ laravel/sanctum non trouvé\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// ===========================
// 7. Test Guzzle HTTP
// ===========================
echo "7. Guzzle HTTP Client\n";
try {
    if (class_exists('GuzzleHttp\Client')) {
        echo "   ✅ guzzlehttp/guzzle installé\n";
        
        // Tester instanciation
        $client = new \GuzzleHttp\Client();
        echo "   ✅ Client HTTP instancié avec succès\n";
    } else {
        echo "   ❌ guzzlehttp/guzzle non trouvé\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// ===========================
// 8. Test Flysystem S3 (R2)
// ===========================
echo "8. Flysystem AWS S3 (Cloudflare R2)\n";
try {
    if (class_exists('League\Flysystem\AwsS3V3\AwsS3V3Adapter')) {
        echo "   ✅ league/flysystem-aws-s3-v3 installé\n";
    } else {
        echo "   ❌ league/flysystem-aws-s3-v3 non trouvé\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// ===========================
// 9. Vérification Composer Autoload
// ===========================
echo "9. Composer Autoload\n";
try {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        echo "   ✅ vendor/autoload.php existe\n";
        
        // Vérifier si optimisé
        if (file_exists(__DIR__ . '/vendor/composer/autoload_classmap.php')) {
            $classmap = require __DIR__ . '/vendor/composer/autoload_classmap.php';
            echo "   ✅ Autoloader optimisé (" . count($classmap) . " classes)\n";
        } else {
            echo "   ⚠️  Autoloader non optimisé (recommandé: composer install --optimize-autoloader)\n";
        }
    } else {
        echo "   ❌ vendor/autoload.php non trouvé\n";
        echo "   ℹ️  Exécuter: composer install\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// ===========================
// 10. Extensions PHP Requises
// ===========================
echo "10. Extensions PHP\n";
$requiredExtensions = [
    'json',
    'mbstring',
    'openssl',
    'PDO',
    'curl',
    'zip',
    'xml',
];

foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ Extension '$ext' chargée\n";
    } else {
        echo "   ❌ Extension '$ext' manquante\n";
        $allPassed = false;
    }
}
echo "\n";

// ===========================
// RÉSUMÉ
// ===========================
echo "═══════════════════════════════════════════════════════════════════════════\n";
if ($allPassed) {
    echo "✅ TOUTES LES DÉPENDANCES SONT INSTALLÉES ET FONCTIONNELLES\n";
    echo "═══════════════════════════════════════════════════════════════════════════\n\n";
    echo "Prochaines étapes:\n";
    echo "1. Configurer .env avec OPENAI_API_KEY et PINECONE_*\n";
    echo "2. Exécuter: php artisan migrate\n";
    echo "3. Exécuter: php artisan db:seed --class=MobileAppPlansSeeder\n";
    echo "4. Tester l'API: curl -X POST https://dossy.alwaysdata.net/api/mobile/register\n\n";
    exit(0);
} else {
    echo "❌ CERTAINES DÉPENDANCES SONT MANQUANTES\n";
    echo "═══════════════════════════════════════════════════════════════════════════\n\n";
    echo "Actions requises:\n";
    echo "1. Exécuter: composer install --optimize-autoloader --no-dev\n";
    echo "2. Vérifier la version PHP (>= 8.2)\n";
    echo "3. Re-exécuter ce script: php test_dependencies.php\n\n";
    exit(1);
}
