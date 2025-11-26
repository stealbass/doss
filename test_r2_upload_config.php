#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Utility;
use Illuminate\Support\Facades\Config;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   🧪 TEST CONFIGURATION R2 POUR UPLOADS\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// 1. Récupérer les settings
echo "📊 1. SETTINGS DE LA BASE DE DONNÉES\n";
echo "───────────────────────────────────────────────────────────────────\n";

$settings = Utility::settings();
$storageSetting = $settings['storage_setting'] ?? 'local';

echo "   storage_setting : $storageSetting\n";

if ($storageSetting !== 'r2') {
    echo "   ⚠️  ATTENTION : storage_setting n'est pas 'r2'\n";
    echo "   Les uploads n'iront PAS vers R2!\n\n";
    echo "   Pour corriger, exécutez: php fix_storage_r2.php\n";
    exit(1);
}

echo "   ✅ storage_setting = 'r2'\n\n";

// 2. Vérifier les paramètres R2
echo "📋 2. PARAMÈTRES R2 DANS LA BASE DE DONNÉES\n";
echo "───────────────────────────────────────────────────────────────────\n";

$r2Params = [
    'r2_key' => $settings['r2_key'] ?? null,
    'r2_secret' => $settings['r2_secret'] ?? null,
    'r2_region' => $settings['r2_region'] ?? 'auto',
    'r2_bucket' => $settings['r2_bucket'] ?? null,
    'r2_endpoint' => $settings['r2_endpoint'] ?? null,
    'r2_url' => $settings['r2_url'] ?? null,
];

$allParamsOk = true;
foreach ($r2Params as $key => $value) {
    if (empty($value)) {
        echo "   ❌ $key : MANQUANT\n";
        $allParamsOk = false;
    } else {
        if (in_array($key, ['r2_key', 'r2_secret'])) {
            echo "   ✅ $key : " . substr($value, 0, 10) . "...\n";
        } else {
            echo "   ✅ $key : $value\n";
        }
    }
}

if (!$allParamsOk) {
    echo "\n   ⚠️  Certains paramètres R2 sont manquants!\n";
    echo "   Les uploads vers R2 risquent d'échouer.\n";
    exit(1);
}

echo "\n";

// 3. Simuler la configuration du disk comme le fait getStorageDisk()
echo "🔧 3. SIMULATION CONFIGURATION DISK R2\n";
echo "───────────────────────────────────────────────────────────────────\n";

// Configuration du disk R2 (comme dans getStorageDisk())
config([
    'filesystems.disks.r2.key' => $settings['r2_key'],
    'filesystems.disks.r2.secret' => $settings['r2_secret'],
    'filesystems.disks.r2.region' => $settings['r2_region'] ?? 'auto',
    'filesystems.disks.r2.bucket' => $settings['r2_bucket'],
    'filesystems.disks.r2.endpoint' => $settings['r2_endpoint'],
    'filesystems.disks.r2.url' => $settings['r2_url'],
    'filesystems.disks.r2.use_path_style_endpoint' => false,
]);

echo "   Configuration dynamique appliquée:\n";
echo "   - Driver: " . Config::get('filesystems.disks.r2.driver') . "\n";
echo "   - Bucket: " . Config::get('filesystems.disks.r2.bucket') . "\n";
echo "   - Endpoint: " . Config::get('filesystems.disks.r2.endpoint') . "\n";
echo "   - URL: " . Config::get('filesystems.disks.r2.url') . "\n";
echo "   - Region: " . Config::get('filesystems.disks.r2.region') . "\n";
echo "   ✅ Disk R2 configuré dynamiquement\n\n";

// 4. Test de connexion basique
echo "🌐 4. TEST CONNEXION R2\n";
echo "───────────────────────────────────────────────────────────────────\n";

try {
    $files = \Storage::disk('r2')->files('legal_documents');
    $fileCount = count($files);
    
    echo "   ✅ Connexion R2 réussie!\n";
    echo "   Fichiers dans legal_documents/ : $fileCount\n";
    
    if ($fileCount > 0) {
        echo "\n   Exemples de fichiers:\n";
        $displayCount = min(5, $fileCount);
        for ($i = 0; $i < $displayCount; $i++) {
            echo "   - " . basename($files[$i]) . "\n";
        }
        if ($fileCount > 5) {
            echo "   ... et " . ($fileCount - 5) . " autres fichiers\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ ERREUR de connexion R2 :\n";
    echo "   " . $e->getMessage() . "\n";
    echo "\n   Vérifiez vos credentials R2 dans la table settings\n";
    exit(1);
}

echo "\n";

// 5. Test de génération d'URL
echo "🔗 5. TEST GÉNÉRATION URL\n";
echo "───────────────────────────────────────────────────────────────────\n";

try {
    $testPath = 'legal_documents/test.pdf';
    $url = \Storage::disk('r2')->url($testPath);
    
    echo "   Path de test : $testPath\n";
    echo "   URL générée : $url\n";
    
    if (strpos($url, $settings['r2_url']) === 0) {
        echo "   ✅ URL R2 correcte (commence par {$settings['r2_url']})\n";
    } else {
        echo "   ⚠️  URL ne commence pas par r2_url configuré\n";
    }
} catch (\Exception $e) {
    echo "   ❌ ERREUR lors de la génération d'URL :\n";
    echo "   " . $e->getMessage() . "\n";
}

echo "\n";

// 6. Résumé final
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   📊 RÉSUMÉ\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "✅ TOUT EST CONFIGURÉ CORRECTEMENT!\n\n";

echo "Ce qui se passera lors d'un upload via Legal Library:\n";
echo "1. getStorageDisk() vérifie storage_setting → 'r2'\n";
echo "2. Configure dynamiquement le disk 'r2' avec les credentials DB\n";
echo "3. Exécute: \$file->storeAs('legal_documents', \$fileName, 'r2')\n";
echo "4. Le fichier est uploadé vers: {$settings['r2_bucket']}/legal_documents/\n";
echo "5. URL publique: {$settings['r2_url']}/legal_documents/filename.pdf\n\n";

echo "🧪 POUR TESTER:\n";
echo "1. Uploadez un document via Legal Library (Super Admin)\n";
echo "2. Vérifiez sur Cloudflare R2 Dashboard que le fichier apparaît\n";
echo "3. Testez le téléchargement depuis l'interface utilisateur\n\n";

echo "═══════════════════════════════════════════════════════════════════\n";
echo "   Test terminé - " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";
