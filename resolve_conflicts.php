<?php
/**
 * Résoudre les conflits de dépendances
 * À lancer : php resolve_conflicts.php
 */

echo "============================================================\n";
echo "RÉSOLUTION DES CONFLITS DE DÉPENDANCES\n";
echo "============================================================\n\n";

$composerFile = __DIR__ . '/composer.json';

if (!file_exists($composerFile)) {
    die("❌ composer.json introuvable\n");
}

// 1. Lire composer.json
echo "📄 Lecture de composer.json...\n";
$composer = json_decode(file_get_contents($composerFile), true);

// 2. Ajuster les versions pour éviter les conflits
echo "⚙️  Ajustement des versions pour compatibilité...\n";

// phpoffice/phpspreadsheet : accepter 1.x et 2.x (compatible avec maatwebsite/excel)
$composer['require']['phpoffice/phpspreadsheet'] = '^1.15|^2.0';
echo "  ✅ phpoffice/phpspreadsheet : ^1.15|^2.0 (compatible maatwebsite/excel)\n";

// Ajouter les autres si manquants
if (!isset($composer['require']['smalot/pdfparser'])) {
    $composer['require']['smalot/pdfparser'] = '^2.0';
    echo "  ✅ smalot/pdfparser ajouté\n";
}

if (!isset($composer['require']['phpoffice/phpword'])) {
    $composer['require']['phpoffice/phpword'] = '^1.0';
    echo "  ✅ phpoffice/phpword ajouté\n";
}

// 3. Sauvegarder
echo "\n💾 Sauvegarde de composer.json...\n";
file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "✅ composer.json mis à jour\n\n";

// 4. Nettoyer
echo "🗑️  Nettoyage...\n";
if (file_exists(__DIR__ . '/composer.lock')) {
    unlink(__DIR__ . '/composer.lock');
    echo "  ✅ composer.lock supprimé\n";
}
shell_exec("rm -rf vendor/ 2>&1");
echo "  ✅ vendor/ supprimé\n\n";

// 5. composer update (au lieu de install)
echo "📦 Lancement de composer update...\n";
echo "-----------------------------------\n";
passthru("composer update --no-dev --with-dependencies 2>&1");
echo "\n";

// 6. Vérification
echo "✅ VÉRIFICATION\n";
echo "---------------\n";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
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
        echo "✅ SUCCÈS ! Tous les packages sont installés\n";
        echo "Lancez : php test_extraction_simple.php\n";
    } else {
        echo "⚠️  Certains packages manquent encore\n";
    }
    echo "============================================================\n";
} else {
    echo "❌ vendor/autoload.php n'existe pas\n";
    echo "L'installation a échoué. Vérifiez les erreurs ci-dessus.\n";
}
