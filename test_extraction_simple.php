<?php
/**
 * TEST D'EXTRACTION - Version minimaliste (sans Laravel)
 * À lancer depuis la racine du projet : php test_extraction_simple.php
 */

echo "🔍 TEST D'EXTRACTION DE DOCUMENTS (version simple)\n";
echo "==================================================\n\n";

// 1. Vérifier les packages PHP
echo "1️⃣ VÉRIFICATION DES PACKAGES INSTALLÉS\n";
echo "----------------------------------------\n";

$packages = [
    'Smalot\PdfParser\Parser' => 'smalot/pdfparser',
    'PhpOffice\PhpWord\IOFactory' => 'phpoffice/phpword',
    'PhpOffice\PhpSpreadsheet\IOFactory' => 'phpoffice/phpspreadsheet',
];

$allPackagesOk = true;
foreach ($packages as $class => $package) {
    $exists = class_exists($class);
    $status = $exists ? '✅ INSTALLÉ' : '❌ MANQUANT';
    echo "$package : $status\n";
    if (!$exists) $allPackagesOk = false;
}

echo "\n";

// 2. Vérifier les outils système
echo "2️⃣ VÉRIFICATION DES COMMANDES SYSTÈME LINUX\n";
echo "-------------------------------------------\n";

$commands = ['pdftotext', 'libreoffice', 'unoconv'];
$allToolsOk = true;
foreach ($commands as $cmd) {
    $exists = trim(shell_exec("which $cmd 2>/dev/null")) !== '';
    $status = $exists ? '✅ DISPONIBLE' : '❌ MANQUANT';
    echo "$cmd : $status\n";
    if (!$exists && ($cmd === 'pdftotext' || $cmd === 'libreoffice')) {
        $allToolsOk = false;
    }
}

echo "\n";

// 3. Vérifier la connexion à la base de données
echo "3️⃣ VÉRIFICATION DE LA CONNEXION À LA BDD\n";
echo "--------------------------------------\n";

try {
    // Charger la configuration .env
    $envPath = __DIR__ . '/.env';
    if (file_exists($envPath)) {
        $env = parse_ini_file($envPath);
        $dbHost = $env['DB_HOST'] ?? 'localhost';
        $dbUser = $env['DB_USERNAME'] ?? 'root';
        $dbPass = $env['DB_PASSWORD'] ?? '';
        $dbName = $env['DB_DATABASE'] ?? '';
        
        echo "  BDD : $dbName @ $dbHost\n";
        
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        echo "  ✅ Connexion OK\n";
        
        // Compter les documents
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM submitted_documents");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        echo "  Documents en BDD : $count\n";
        
        // Afficher les derniers documents
        echo "\n  Derniers documents :\n";
        $stmt = $pdo->query("SELECT id, file_name, mime_type, file_size, extracted_text FROM submitted_documents ORDER BY created_at DESC LIMIT 5");
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($docs)) {
            echo "    ❌ Aucun document trouvé\n";
        } else {
            foreach ($docs as $doc) {
                $hasText = strlen($doc['extracted_text'] ?? '') > 0 ? '✅' : '❌';
                $size = number_format($doc['file_size'] / 1024, 2);
                echo "    Doc #{$doc['id']} : {$doc['file_name']} ({$size}KB) $hasText\n";
            }
        }
    } else {
        echo "  ❌ Fichier .env non trouvé\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Erreur BDD : {$e->getMessage()}\n";
}

echo "\n";

// 4. Résumé
echo "✅ RÉSUMÉ\n";
echo "--------\n";

if ($allPackagesOk && $allToolsOk) {
    echo "✅ Tous les prérequis sont installés !\n";
    echo "Vous pouvez maintenant uploader des documents.\n";
    echo "Relancez ce script après un upload pour voir l'extraction.\n";
} else {
    echo "❌ Des dépendances manquent :\n";
    if (!$allPackagesOk) {
        echo "  - PHP : composer require smalot/pdfparser phpoffice/phpword phpoffice/phpspreadsheet\n";
    }
    if (!$allToolsOk) {
        echo "  - Système : sudo apt-get install poppler-utils libreoffice-core\n";
    }
}

echo "\n";
