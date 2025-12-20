<?php
/**
 * Script de nettoyage des caches Laravel
 * Accès : https://dossypro.com/clear-cache.php?token=DOSSY2024CLEAR
 * 
 * ⚠️ SÉCURITÉ : Supprimez ce fichier après utilisation !
 */

// Sécurité : Protéger avec un token
$token = $_GET['token'] ?? '';
$validToken = 'DOSSY2024CLEAR'; // Changez ce token !

if ($token !== $validToken) {
    die('❌ Accès refusé. Token invalide.');
}

echo "<pre>";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         🔧 NETTOYAGE DES CACHES LARAVEL                      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$cleared = [];
$errors = [];

// 1. Nettoyer le cache de l'application
$cacheDataPath = __DIR__ . '/storage/framework/cache/data';
if (is_dir($cacheDataPath)) {
    $files = glob($cacheDataPath . '/*');
    $count = 0;
    foreach ($files as $file) {
        if (basename($file) !== '.gitignore' && is_file($file)) {
            @unlink($file);
            $count++;
        }
    }
    $cleared[] = "✅ Cache de l'application vidé (" . $count . " fichiers)";
} else {
    $errors[] = "⚠️  Dossier cache/data introuvable";
}

// 2. Nettoyer les vues compilées
$viewsPath = __DIR__ . '/storage/framework/views';
if (is_dir($viewsPath)) {
    $files = glob($viewsPath . '/*.php');
    $count = 0;
    foreach ($files as $file) {
        if (basename($file) !== '.gitignore' && is_file($file)) {
            @unlink($file);
            $count++;
        }
    }
    $cleared[] = "✅ Vues compilées vidées (" . $count . " fichiers)";
} else {
    $errors[] = "⚠️  Dossier views introuvable";
}

// 3. Nettoyer le cache de configuration
$bootstrapCachePath = __DIR__ . '/bootstrap/cache';
if (is_dir($bootstrapCachePath)) {
    $filesToDelete = [
        'config.php',
        'routes.php',
        'routes-v7.php',
        'services.php',
        'packages.php'
    ];
    $count = 0;
    foreach ($filesToDelete as $filename) {
        $file = $bootstrapCachePath . '/' . $filename;
        if (file_exists($file) && is_file($file)) {
            @unlink($file);
            $count++;
        }
    }
    $cleared[] = "✅ Cache de configuration vidé (" . $count . " fichiers)";
} else {
    $errors[] = "⚠️  Dossier bootstrap/cache introuvable";
}

// 4. Nettoyer les sessions (optionnel)
$sessionsPath = __DIR__ . '/storage/framework/sessions';
if (is_dir($sessionsPath)) {
    $files = glob($sessionsPath . '/*');
    $count = 0;
    foreach ($files as $file) {
        if (basename($file) !== '.gitignore' && is_file($file)) {
            @unlink($file);
            $count++;
        }
    }
    $cleared[] = "✅ Sessions vidées (" . $count . " fichiers)";
}

// 5. Nettoyer les logs anciens (garder les 100 dernières lignes)
$logPath = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logPath)) {
    $lines = @file($logPath);
    if ($lines !== false) {
        $lastLines = array_slice($lines, -100);
        @file_put_contents($logPath, implode('', $lastLines));
        $cleared[] = "✅ Logs nettoyés (gardé 100 dernières lignes)";
    }
}

// 6. Vérifier les fichiers importants
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "VÉRIFICATION DES FICHIERS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$controllers = [
    'MobileDashboardController.php',
    'MobileAnalyticsController.php',
    'DocumentTemplateController.php',
    'FiscalSocialResourceController.php',
    'CalculatorController.php',
];

foreach ($controllers as $controller) {
    $path = __DIR__ . '/app/Http/Controllers/' . $controller;
    if (file_exists($path)) {
        echo "✅ " . $controller . " existe\n";
    } else {
        echo "❌ " . $controller . " MANQUANT\n";
        $errors[] = "❌ Fichier manquant : " . $controller;
    }
}

// Affichage des résultats
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "RÉSULTATS DU NETTOYAGE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

foreach ($cleared as $message) {
    echo $message . "\n";
}

if (!empty($errors)) {
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "AVERTISSEMENTS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    foreach ($errors as $error) {
        echo $error . "\n";
    }
    
    if (strpos(implode('', $errors), 'MANQUANT') !== false) {
        echo "\n⚠️  FICHIERS MANQUANTS DÉTECTÉS !\n";
        echo "Téléchargez-les depuis GitHub :\n";
        echo "https://github.com/stealbass/doss/tree/main/app/Http/Controllers\n\n";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎉 NETTOYAGE TERMINÉ !\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "Maintenant, testez vos URLs :\n";
echo "• https://dossypro.com/mobile-dashboard\n";
echo "• https://dossypro.com/mobile-analytics\n";
echo "• https://dossypro.com/document-templates\n";
echo "• https://dossypro.com/fiscal-resources\n";
echo "• https://dossypro.com/calculators\n";
echo "• https://dossypro.com/legal-library/category/create\n\n";

echo "⚠️  IMPORTANT : Rechargez les pages avec CTRL+F5\n\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  ⚠️  SÉCURITÉ : Supprimez ce fichier après utilisation !     ║\n";
echo "║                                                              ║\n";
echo "║  Via FTP : Supprimez clear-cache.php de public_html/        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "</pre>";
?>
