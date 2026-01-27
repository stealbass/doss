#!/usr/bin/env php
<?php

echo "\n=== VÉRIFICATION DES MODIFICATIONS FLUTTER ===\n\n";

$files = [
    'dossy_chat_ia/lib/providers/template_provider.dart' => '/templates/',
    'dossy_chat_ia/lib/providers/fiscal_resource_provider.dart' => '/fiscal-resources',
    'dossy_chat_ia/lib/data/services/search_service.dart' => '/documents/search',
];

$errors = [];

foreach ($files as $file => $expectedPath) {
    $fullPath = __DIR__ . '/' . $file;
    
    if (!file_exists($fullPath)) {
        $errors[] = "❌ Fichier introuvable: $file";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    
    // Vérifier qu'il n'y a PAS de double /mobile/
    if (strpos($content, '/mobile/mobile/') !== false) {
        $errors[] = "❌ $file contient encore /mobile/mobile/";
    } elseif (strpos($content, $expectedPath) !== false) {
        echo "✅ $file: URL correcte ($expectedPath)\n";
    } else {
        $errors[] = "⚠️  $file: impossible de vérifier le chemin $expectedPath";
    }
}

echo "\n=== VÉRIFICATION DU MODÈLE BACKEND ===\n\n";

$modelPath = __DIR__ . '/app/Models/MobileAppSubscription.php';
if (file_exists($modelPath)) {
    $content = file_get_contents($modelPath);
    
    if (strpos($content, 'function canSearch()') !== false) {
        echo "✅ Méthode canSearch() présente\n";
    } else {
        $errors[] = "❌ Méthode canSearch() MANQUANTE dans MobileAppSubscription.php";
    }
    
    if (strpos($content, 'function incrementSearch()') !== false) {
        echo "✅ Méthode incrementSearch() présente\n";
    } else {
        $errors[] = "❌ Méthode incrementSearch() MANQUANTE dans MobileAppSubscription.php";
    }
} else {
    $errors[] = "❌ Fichier MobileAppSubscription.php introuvable";
}

echo "\n=== RÉSUMÉ ===\n\n";

if (empty($errors)) {
    echo "✅ Toutes les modifications sont présentes!\n";
    echo "\nProchaines étapes:\n";
    echo "1. Sur le serveur de production, appliquer les modifications à MobileAppSubscription.php\n";
    echo "2. Nettoyer le cache Laravel: php artisan route:clear && php artisan cache:clear\n";
    echo "3. Rebuild l'app Flutter: flutter build apk\n";
    echo "4. Tester!\n";
} else {
    echo "❌ Problèmes détectés:\n\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
}

echo "\n";
