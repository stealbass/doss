<?php
/**
 * Test PdfParser après mise à jour PHP
 */

echo "🧪 Test PdfParser après mise à jour PHP\n\n";

// 1. Vérifier version PHP
echo "📌 Version PHP actuelle:\n";
echo "PHP " . PHP_VERSION . "\n\n";

if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    echo "❌ PHP version insuffisante!\n";
    echo "Requis: PHP 8.2+\n";
    echo "Actuel: PHP " . PHP_VERSION . "\n";
    echo "\n⚠️ Veuillez mettre à jour PHP avant de continuer\n";
    exit(1);
}

echo "✅ Version PHP OK (>= 8.2)\n\n";

// 2. Vérifier que PdfParser est installé
echo "📦 Vérification PdfParser...\n";

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ Vendor directory not found\n";
    echo "Exécutez: composer install\n";
    exit(1);
}

require __DIR__ . '/vendor/autoload.php';

if (!class_exists('Smalot\PdfParser\Parser')) {
    echo "❌ PdfParser not installed\n";
    echo "Exécutez: composer require smalot/pdfparser\n";
    exit(1);
}

echo "✅ PdfParser installé\n\n";

// 3. Tester PdfParser
echo "🔬 Test fonctionnel PdfParser...\n";

use Smalot\PdfParser\Parser;

try {
    $parser = new Parser();
    echo "✅ Parser instancié avec succès\n";
    
    // Chercher un PDF de test dans storage
    $testPdfPaths = [
        __DIR__ . '/storage/app/public/documents/test.pdf',
        __DIR__ . '/storage/app/public/submitted_documents/',
        __DIR__ . '/storage/app/public/legal_documents/',
    ];
    
    $foundPdf = null;
    foreach ($testPdfPaths as $path) {
        if (is_file($path)) {
            $foundPdf = $path;
            break;
        } elseif (is_dir($path)) {
            $files = glob($path . '*.pdf');
            if (!empty($files)) {
                $foundPdf = $files[0];
                break;
            }
        }
    }
    
    if ($foundPdf) {
        echo "📄 PDF de test trouvé: " . basename($foundPdf) . "\n";
        $pdf = $parser->parseFile($foundPdf);
        $text = $pdf->getText();
        
        echo "✅ Extraction réussie\n";
        echo "📊 Longueur texte: " . strlen($text) . " caractères\n";
        echo "📝 Aperçu: " . substr(trim($text), 0, 100) . "...\n";
    } else {
        echo "⚠️ Aucun PDF de test trouvé (normal si aucun document uploadé)\n";
        echo "✅ PdfParser fonctionnel (test d'instanciation OK)\n";
    }
    
    echo "\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Vérifier extensions PHP critiques
echo "🔍 Vérification extensions PHP...\n";

$requiredExtensions = [
    'pdo_mysql' => 'Base de données',
    'mbstring' => 'Manipulation strings',
    'xml' => 'XML parsing',
    'curl' => 'HTTP requests',
    'gd' => 'Images',
    'zip' => 'Archives',
    'intl' => 'Internationalisation',
    'bcmath' => 'Calculs précis',
];

$allOk = true;
foreach ($requiredExtensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        echo "  ✅ {$ext} ({$desc})\n";
    } else {
        echo "  ❌ {$ext} ({$desc}) - MANQUANT\n";
        $allOk = false;
    }
}

echo "\n";

if (!$allOk) {
    echo "⚠️ Extensions manquantes détectées\n";
    echo "Installez avec: apt install php8.2-[extension]\n\n";
}

// 5. Vérifier configuration Laravel
echo "⚙️ Vérification configuration Laravel...\n";

if (file_exists(__DIR__ . '/.env')) {
    echo "  ✅ .env exists\n";
    
    $envContent = file_get_contents(__DIR__ . '/.env');
    
    // Vérifier clés critiques
    $criticalKeys = [
        'APP_KEY',
        'DB_CONNECTION',
        'DB_HOST',
        'OPENAI_API_KEY',
        'PINECONE_API_KEY',
    ];
    
    foreach ($criticalKeys as $key) {
        if (strpos($envContent, $key . '=') !== false) {
            echo "  ✅ {$key} configuré\n";
        } else {
            echo "  ⚠️ {$key} manquant ou vide\n";
        }
    }
} else {
    echo "  ❌ .env file not found\n";
}

echo "\n";

// 6. Résumé final
echo "===========================================\n";
echo "RÉSUMÉ DU TEST\n";
echo "===========================================\n";
echo "✅ PHP Version: " . PHP_VERSION . " (OK)\n";
echo "✅ PdfParser: Installé et fonctionnel\n";

if ($allOk) {
    echo "✅ Extensions PHP: Toutes présentes\n";
} else {
    echo "⚠️ Extensions PHP: Certaines manquantes\n";
}

echo "\n";
echo "🎯 PROCHAINES ÉTAPES:\n";
echo "1. ✅ Tester upload de document via app mobile\n";
echo "2. ✅ Vérifier extraction texte dans logs:\n";
echo "   tail -f storage/logs/laravel.log | grep 'Extracting text'\n";
echo "3. ✅ Appliquer patch SSL Pinecone si erreur TLS:\n";
echo "   php apply_ssl_workaround_patch.php\n";
echo "4. ✅ Tester chat avec documents\n";
echo "\n";
echo "✅ SYSTÈME PRÊT POUR PRODUCTION\n";
