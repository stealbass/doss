<?php
/**
 * Script FINAL et ROBUSTE pour installer les dépendances
 * À lancer depuis la racine du projet : php install_now.php
 */

echo "====================================================\n";
echo "INSTALLATION FORCÉE DES DÉPENDANCES\n";
echo "====================================================\n\n";

$projectRoot = __DIR__;
chdir($projectRoot);

// ====== 1. FORCER composer install ======
echo "📦 ÉTAPE 1 : composer install\n";
echo "------------------------------\n";
$cmd = "composer install --no-dev --optimize-autoloader";
echo ">> $cmd\n";
$output = shell_exec($cmd . " 2>&1");
echo $output;

echo "\n";

// ====== 2. Ajouter les packages manquants ======
echo "📦 ÉTAPE 2 : Ajouter les packages PHP\n";
echo "--------------------------------------\n";

$packages = [
    'smalot/pdfparser',
    'phpoffice/phpword',
    'phpoffice/phpspreadsheet'
];

foreach ($packages as $package) {
    $cmd = "composer require $package --no-interaction --no-dev";
    echo ">> $cmd\n";
    $output = shell_exec($cmd . " 2>&1");
    
    // Affiche seulement les lignes importantes
    $lines = explode("\n", $output);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && (
            strpos($line, 'Using') !== false ||
            strpos($line, 'Added') !== false ||
            strpos($line, 'Installing') !== false ||
            strpos($line, 'error') !== false ||
            strpos($line, 'Error') !== false
        )) {
            echo "   $line\n";
        }
    }
}

echo "\n";

// ====== 3. composer dump-autoload ======
echo "⚙️  ÉTAPE 3 : Générer l'autoload optimisé\n";
echo "----------------------------------------\n";
$cmd = "composer dump-autoload -o --no-dev";
echo ">> $cmd\n";
$output = shell_exec($cmd . " 2>&1");
if (strpos($output, 'Generating') !== false || strpos($output, 'Not writing new') !== false) {
    echo "✅ Autoload généré\n";
} else {
    echo $output;
}

echo "\n";

// ====== 4. Installer les outils système ======
echo "🔧 ÉTAPE 4 : Installer les outils système\n";
echo "---------------------------------------\n";

// Détecte le gestionnaire de paquets
$hasApt = trim(shell_exec("which apt-get 2>/dev/null"));
$hasYum = trim(shell_exec("which yum 2>/dev/null"));

if (!empty($hasApt)) {
    echo "Détecté : apt-get (Debian/Ubuntu)\n\n";
    
    // Update
    echo ">> sudo apt-get update\n";
    shell_exec("sudo apt-get update 2>&1");
    echo "✅ Cache APT mise à jour\n\n";
    
    // poppler-utils (pour pdftotext)
    echo ">> sudo apt-get install -y poppler-utils\n";
    $output = shell_exec("sudo apt-get install -y poppler-utils 2>&1");
    if (strpos($output, 'already the newest') !== false || strpos($output, 'Setting up') !== false) {
        echo "✅ poppler-utils OK\n";
    } else {
        echo "   $output\n";
    }
    
    // libreoffice-core (pour Word/Excel)
    echo ">> sudo apt-get install -y libreoffice-core libreoffice-common\n";
    $output = shell_exec("sudo apt-get install -y libreoffice-core libreoffice-common 2>&1");
    if (strpos($output, 'already the newest') !== false || strpos($output, 'Setting up') !== false) {
        echo "✅ libreoffice OK\n";
    } else {
        echo "   $output\n";
    }
    
} elseif (!empty($hasYum)) {
    echo "Détecté : yum (CentOS/RHEL)\n\n";
    
    echo ">> sudo yum update -y\n";
    shell_exec("sudo yum update -y 2>&1");
    
    echo ">> sudo yum install -y poppler libreoffice-core\n";
    shell_exec("sudo yum install -y poppler libreoffice-core 2>&1");
    echo "✅ Paquets système installés\n";
    
} else {
    echo "⚠️  apt-get ni yum détectés\n";
    echo "Installation manuelle requise :\n";
    echo "  - Debian/Ubuntu : sudo apt-get install poppler-utils libreoffice-core\n";
    echo "  - CentOS/RHEL : sudo yum install poppler libreoffice-core\n";
}

echo "\n";

// ====== 5. Vérification finale ======
echo "✅ VÉRIFICATION FINALE\n";
echo "----------------------\n\n";

// Packages PHP
echo "📦 Packages PHP :\n";
$packages_check = [
    'Smalot\PdfParser\Parser' => 'smalot/pdfparser',
    'PhpOffice\PhpWord\IOFactory' => 'phpoffice/phpword',
    'PhpOffice\PhpSpreadsheet\IOFactory' => 'phpoffice/phpspreadsheet',
];

$allPhpOk = true;
foreach ($packages_check as $class => $package) {
    if (class_exists($class)) {
        echo "   ✅ $package\n";
    } else {
        echo "   ❌ $package\n";
        $allPhpOk = false;
    }
}

// Outils système
echo "\n🔧 Outils système :\n";
$tools = [
    'pdftotext' => 'Pour PDF',
    'libreoffice' => 'Pour Word/Excel',
];

$allToolsOk = true;
foreach ($tools as $tool => $desc) {
    $exists = trim(shell_exec("which $tool 2>/dev/null"));
    if (!empty($exists)) {
        echo "   ✅ $tool ($desc)\n";
    } else {
        echo "   ❌ $tool ($desc)\n";
        $allToolsOk = false;
    }
}

echo "\n";
echo "====================================================\n";

if ($allPhpOk && $allToolsOk) {
    echo "✅ TOUT EST INSTALLÉ ! Lancez : php test_extraction.php\n";
} else {
    echo "⚠️  Certaines dépendances manquent encore.\n";
    echo "Relancez ce script ou installez manuellement :\n";
    if (!$allPhpOk) {
        echo "  - PHP : composer install && composer dump-autoload -o\n";
    }
    if (!$allToolsOk) {
        echo "  - Système : sudo apt-get install poppler-utils libreoffice-core\n";
    }
}

echo "====================================================\n";
