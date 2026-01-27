<?php
/**
 * Script robuste d'installation des dépendances d'extraction PDF/Word/Excel
 * À lancer depuis la racine du projet : php install_deps_final.php
 */

echo "====================================================\n";
echo "INSTALLATION DES DÉPENDANCES D'EXTRACTION\n";
echo "====================================================\n\n";

// Chemin du projet
$projectRoot = __DIR__;
chdir($projectRoot);

// ====== ÉTAPE 1 : Installer les packages PHP manquants ======
echo "📦 ÉTAPE 1 : Installation des packages PHP\n";
echo "-------------------------------------------\n";

$packages = [
    'smalot/pdfparser',
    'phpoffice/phpword',
    'phpoffice/phpspreadsheet'
];

foreach ($packages as $package) {
    echo "Installing $package...\n";
    $cmd = "composer require $package --no-dev";
    $output = shell_exec($cmd . " 2>&1");
    
    // Affiche uniquement les lignes importantes
    $lines = explode("\n", $output);
    foreach ($lines as $line) {
        if (strpos($line, 'Using version') !== false || 
            strpos($line, 'Installing') !== false ||
            strpos($line, 'error') !== false ||
            strpos($line, 'Error') !== false) {
            echo "  " . trim($line) . "\n";
        }
    }
}

echo "\n";

// ====== ÉTAPE 2 : Générer l'autoload optimisé ======
echo "⚙️  ÉTAPE 2 : Optimisation de l'autoload\n";
echo "-----------------------------------------\n";

echo "Lancement : composer dump-autoload -o\n";
$output = shell_exec("composer dump-autoload -o 2>&1");
if (strpos($output, 'Generating') !== false) {
    echo "✅ Autoload généré avec succès\n";
} else {
    echo "⚠️  Output : $output\n";
}

echo "\n";

// ====== ÉTAPE 3 : Installer les outils système ======
echo "🔧 ÉTAPE 3 : Installation des outils système\n";
echo "---------------------------------------------\n";

// Détecte le gestionnaire de paquets
$hasApt = shell_exec("which apt-get 2>/dev/null");
$hasYum = shell_exec("which yum 2>/dev/null");
$hasBrew = shell_exec("which brew 2>/dev/null");

if ($hasApt) {
    echo "Détecté : apt-get (Debian/Ubuntu)\n";
    echo "Lancement : sudo apt-get update && sudo apt-get install -y poppler-utils libreoffice-core\n";
    shell_exec("sudo apt-get update 2>&1");
    $output = shell_exec("sudo apt-get install -y poppler-utils libreoffice-core 2>&1");
    if (strpos($output, 'Setting up') !== false || strpos($output, 'already') !== false) {
        echo "✅ Paquets système installés\n";
    } else {
        echo "⚠️  Vérifiez manuellement l'installation\n";
    }
} elseif ($hasYum) {
    echo "Détecté : yum (CentOS/RHEL)\n";
    echo "Lancement : sudo yum install -y poppler libreoffice-core\n";
    shell_exec("sudo yum update -y 2>&1");
    shell_exec("sudo yum install -y poppler libreoffice-core 2>&1");
    echo "✅ Paquets système installés\n";
} elseif ($hasBrew) {
    echo "Détecté : brew (macOS)\n";
    echo "Lancement : brew install poppler libreoffice\n";
    shell_exec("brew install poppler libreoffice 2>&1");
    echo "✅ Paquets système installés\n";
} else {
    echo "⚠️  Gestionnaire de paquets non détecté\n";
    echo "Installez manuellement :\n";
    echo "  - Debian/Ubuntu : sudo apt-get install poppler-utils libreoffice-core\n";
    echo "  - CentOS/RHEL : sudo yum install poppler libreoffice-core\n";
    echo "  - macOS : brew install poppler libreoffice\n";
}

echo "\n";

// ====== ÉTAPE 4 : Vérification finale ======
echo "✅ ÉTAPE 4 : Vérification finale\n";
echo "--------------------------------\n";

$packages_check = [
    'Smalot\PdfParser\Parser' => 'smalot/pdfparser',
    'PhpOffice\PhpWord\IOFactory' => 'phpoffice/phpword',
    'PhpOffice\PhpSpreadsheet\IOFactory' => 'phpoffice/phpspreadsheet',
];

echo "Packages PHP :\n";
foreach ($packages_check as $class => $package) {
    if (class_exists($class)) {
        echo "  ✅ $package\n";
    } else {
        echo "  ❌ $package\n";
    }
}

echo "\nOutils système :\n";
$tools = ['pdftotext', 'libreoffice', 'unoconv'];
foreach ($tools as $tool) {
    $exists = shell_exec("which $tool 2>/dev/null");
    if ($exists) {
        echo "  ✅ $tool\n";
    } else {
        echo "  ❌ $tool\n";
    }
}

echo "\n====================================================\n";
echo "✅ Installation terminée\n";
echo "Lancez maintenant : php test_extraction.php\n";
echo "====================================================\n";
