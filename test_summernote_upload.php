<?php
/**
 * Script de diagnostic pour l'upload d'images Summernote
 * Teste la configuration Cloudflare R2 et la route d'upload
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

echo "=== DIAGNOSTIC SUMMERNOTE UPLOAD ===\n\n";

// 1. Vérifier les variables d'environnement R2
echo "1. Configuration Cloudflare R2:\n";
echo "   - R2_ACCESS_KEY_ID: " . (env('R2_ACCESS_KEY_ID') ? '✅ Définie' : '❌ Manquante') . "\n";
echo "   - R2_SECRET_ACCESS_KEY: " . (env('R2_SECRET_ACCESS_KEY') ? '✅ Définie' : '❌ Manquante') . "\n";
echo "   - R2_BUCKET: " . (env('R2_BUCKET') ? '✅ ' . env('R2_BUCKET') : '❌ Manquante') . "\n";
echo "   - R2_ENDPOINT: " . (env('R2_ENDPOINT') ? '✅ ' . env('R2_ENDPOINT') : '❌ Manquante') . "\n";
echo "   - R2_URL: " . (env('R2_URL') ? '✅ ' . env('R2_URL') : '❌ Manquante') . "\n\n";

// 2. Vérifier la configuration du disk R2
echo "2. Configuration du disk R2:\n";
try {
    $r2Config = config('filesystems.disks.r2');
    if ($r2Config) {
        echo "   ✅ Disk 'r2' configuré\n";
        echo "   - Driver: " . $r2Config['driver'] . "\n";
        echo "   - Region: " . $r2Config['region'] . "\n";
    } else {
        echo "   ❌ Disk 'r2' non configuré dans filesystems.php\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Tester la connexion R2
echo "3. Test de connexion R2:\n";
try {
    // Créer un fichier de test
    $testContent = 'Test Summernote Upload - ' . date('Y-m-d H:i:s');
    $testPath = 'push-notifications/inline/test_' . time() . '.txt';
    
    Storage::disk('r2')->put($testPath, $testContent, 'public');
    echo "   ✅ Upload vers R2 réussi!\n";
    
    // Vérifier l'existence
    if (Storage::disk('r2')->exists($testPath)) {
        echo "   ✅ Fichier trouvé sur R2\n";
        
        // Générer l'URL
        $url = env('R2_URL') . '/' . $testPath;
        echo "   ✅ URL générée: " . $url . "\n";
        
        // Nettoyer
        Storage::disk('r2')->delete($testPath);
        echo "   ✅ Fichier de test supprimé\n";
    } else {
        echo "   ❌ Fichier non trouvé sur R2\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Erreur de connexion: " . $e->getMessage() . "\n";
    echo "   Détails: " . $e->getTraceAsString() . "\n";
}
echo "\n";

// 4. Vérifier la route d'upload
echo "4. Vérification de la route:\n";
try {
    $routes = Route::getRoutes();
    $uploadRoute = null;
    
    foreach ($routes as $route) {
        if ($route->getName() === 'push-notifications.upload-image') {
            $uploadRoute = $route;
            break;
        }
    }
    
    if ($uploadRoute) {
        echo "   ✅ Route 'push-notifications.upload-image' trouvée\n";
        echo "   - URI: " . $uploadRoute->uri() . "\n";
        echo "   - Méthode: " . implode('|', $uploadRoute->methods()) . "\n";
        echo "   - Action: " . $uploadRoute->getActionName() . "\n";
    } else {
        echo "   ❌ Route 'push-notifications.upload-image' non trouvée\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Vérifier la méthode du contrôleur
echo "5. Vérification du contrôleur:\n";
$controllerPath = app_path('Http/Controllers/PushNotificationsController.php');
if (file_exists($controllerPath)) {
    echo "   ✅ PushNotificationsController.php trouvé\n";
    
    $controllerContent = file_get_contents($controllerPath);
    
    if (strpos($controllerContent, 'function uploadImage') !== false) {
        echo "   ✅ Méthode uploadImage() présente\n";
    } else {
        echo "   ❌ Méthode uploadImage() MANQUANTE!\n";
        echo "   ⚠️  Vous devez ajouter la méthode uploadImage() au contrôleur\n";
    }
    
    if (strpos($controllerContent, 'use Illuminate\Support\Facades\Storage') !== false) {
        echo "   ✅ Facade Storage importée\n";
    } else {
        echo "   ⚠️  Facade Storage non importée (peut causer des erreurs)\n";
    }
} else {
    echo "   ❌ Contrôleur non trouvé\n";
}
echo "\n";

// 6. Résumé
echo "=== RÉSUMÉ ===\n";
$allGood = env('R2_ACCESS_KEY_ID') && 
           env('R2_SECRET_ACCESS_KEY') && 
           env('R2_BUCKET') && 
           env('R2_ENDPOINT') && 
           env('R2_URL');

if ($allGood) {
    echo "✅ Configuration complète - L'upload devrait fonctionner\n";
    echo "\nSi vous avez encore des erreurs:\n";
    echo "1. Videz le cache: php artisan config:clear && php artisan route:clear\n";
    echo "2. Vérifiez les logs Laravel: storage/logs/laravel.log\n";
    echo "3. Testez l'upload depuis l'interface admin\n";
} else {
    echo "❌ Configuration INCOMPLÈTE\n";
    echo "\n📋 ACTIONS REQUISES:\n";
    echo "1. Ajoutez les variables R2 dans le fichier .env\n";
    echo "2. Exécutez: php artisan config:clear\n";
    echo "3. Relancez ce script pour valider\n";
}

echo "\n";
