<?php
/**
 * Installation manuelle en modifiant composer.json directement
 * À lancer : php force_install_packages.php
 */

echo "============================================================\n";
echo "FORCER L'INSTALLATION DES PACKAGES (modification directe)\n";
echo "============================================================\n\n";

$composerFile = __DIR__ . '/composer.json';

if (!file_exists($composerFile)) {
    die("❌ composer.json introuvable\n");
}

// 1. Lire composer.json
echo "📄 Lecture de composer.json...\n";
$composer = json_decode(file_get_contents($composerFile), true);

if (!$composer) {
    die("❌ Erreur de lecture composer.json\n");
}

// 2. Ajouter les packages manquants
echo "📦 Ajout des packages d'extraction...\n";

$packagesToAdd = [
    'smalot/pdfparser' => '^2.0',
    'phpoffice/phpword' => '^1.0',
    'phpoffice/phpspreadsheet' => '^1.29'
];

$added = false;
foreach ($packagesToAdd as $package => $version) {
    if (!isset($composer['require'][$package])) {
        $composer['require'][$package] = $version;
        echo "  ✅ Ajouté : $package\n";
        $added = true;
    } else {
        echo "  ⚠️  Déjà présent : $package\n";
    }
}

// 3. Sauvegarder composer.json
if ($added) {
    echo "\n💾 Sauvegarde de composer.json...\n";
    file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "✅ composer.json mis à jour\n\n";
}

// 4. Supprimer composer.lock pour forcer la résolution
echo "🗑️  Suppression de composer.lock...\n";
if (file_exists(__DIR__ . '/composer.lock')) {
    unlink(__DIR__ . '/composer.lock');
    echo "✅ composer.lock supprimé\n\n";
}

// 5. Supprimer vendor/ pour nettoyer
echo "🗑️  Suppression de vendor/...\n";
shell_exec("rm -rf vendor/ 2>&1");
echo "✅ vendor/ supprimé\n\n";

// 6. Composer install
echo "📦 Lancement de composer install...\n";
echo "------------------------------------\n";
passthru("composer install --no-dev --optimize-autoloader 2>&1");
echo "\n✅ composer install terminé\n\n";

// 7. Vérification
echo "✅ VÉRIFICATION\n";
echo "---------------\n";

require __DIR__ . '/vendor/autoload.php';

$packages = [
    'Smalot\PdfParser\Parser' => 'smalot/pdfparser',
    'PhpOffice\PhpWord\IOFactory' => 'phpoffice/phpword',
    'PhpOffice\PhpSpreadsheet\IOFactory' => 'phpoffice/phpspreadsheet',
];

$allOk = true;
foreach ($packages as $class => $package) {
    if (class_exists($class)) {
        echo "  ✅ $package\n";
    } else {
        echo "  ❌ $package\n";
        $allOk = false;
    }
}

echo "\n============================================================\n";
if ($allOk) {
    echo "✅ TOUT EST INSTALLÉ !\n";
    echo "Lancez : php test_extraction_simple.php\n";
} else {
    echo "❌ Certains packages manquent encore\n";
    echo "Vérifiez les erreurs ci-dessus\n";
}
echo "============================================================\n";
