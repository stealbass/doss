#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Utility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "   🔍 DIAGNOSTIC STORAGE - DOSSY PRO\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// 1. Vérifier la valeur dans la DB
echo "📊 1. VÉRIFICATION BASE DE DONNÉES\n";
echo "───────────────────────────────────────────────────────────\n";

$storageSettingDB = DB::table('settings')
    ->where('key', 'storage_setting')
    ->value('value');

echo "   storage_setting (DB directe) : " . ($storageSettingDB ?? '❌ NON TROUVÉ') . "\n";

$r2Settings = DB::table('settings')
    ->whereIn('key', ['r2_key', 'r2_secret', 'r2_bucket', 'r2_endpoint', 'r2_url'])
    ->pluck('value', 'key')
    ->toArray();

echo "\n   Configuration R2 dans la DB:\n";
foreach (['r2_key', 'r2_secret', 'r2_bucket', 'r2_endpoint', 'r2_url'] as $key) {
    $value = $r2Settings[$key] ?? null;
    if ($value) {
        if (in_array($key, ['r2_key', 'r2_secret'])) {
            echo "   ✅ $key : " . substr($value, 0, 10) . "... (masqué)\n";
        } else {
            echo "   ✅ $key : $value\n";
        }
    } else {
        echo "   ❌ $key : NON CONFIGURÉ\n";
    }
}

// 2. Vérifier via Utility::settings()
echo "\n📦 2. VÉRIFICATION VIA Utility::settings()\n";
echo "───────────────────────────────────────────────────────────\n";

$settings = Utility::settings();
$storageSetting = $settings['storage_setting'] ?? 'NON TROUVÉ';

echo "   storage_setting (Utility::settings()) : $storageSetting\n";

if (isset($settings['r2_url'])) {
    echo "   ✅ r2_url : {$settings['r2_url']}\n";
} else {
    echo "   ❌ r2_url : NON TROUVÉ\n";
}

// 3. Vérifier via Utility::getValByName()
echo "\n🔑 3. VÉRIFICATION VIA Utility::getValByName()\n";
echo "───────────────────────────────────────────────────────────\n";

$storageByName = Utility::getValByName('storage_setting');
echo "   storage_setting (getValByName) : " . ($storageByName ?? 'NULL') . "\n";

// 4. Vérifier la configuration du disk R2
echo "\n💾 4. CONFIGURATION DISK R2 (config/filesystems.php)\n";
echo "───────────────────────────────────────────────────────────\n";

$r2DiskConfig = Config::get('filesystems.disks.r2');
if ($r2DiskConfig) {
    echo "   ✅ Disk R2 configuré\n";
    echo "   Driver: {$r2DiskConfig['driver']}\n";
    echo "   Bucket: " . ($r2DiskConfig['bucket'] ?? 'NON DÉFINI') . "\n";
    echo "   Endpoint: " . ($r2DiskConfig['endpoint'] ?? 'NON DÉFINI') . "\n";
} else {
    echo "   ❌ Disk R2 NON configuré dans filesystems.php\n";
}

// 5. Test de détermination du disk
echo "\n🎯 5. QUEL DISK SERA UTILISÉ POUR LES UPLOADS ?\n";
echo "───────────────────────────────────────────────────────────\n";

$storageSetting = $settings['storage_setting'] ?? 'local';
$diskToUse = ($storageSetting === 'r2') ? 'r2' : 'public';

echo "   Valeur de storage_setting : '$storageSetting'\n";
echo "   Disk qui sera utilisé : '$diskToUse'\n";

if ($diskToUse === 'r2') {
    echo "   ✅ Les uploads iront vers R2\n";
} else {
    echo "   ⚠️  Les uploads iront vers LOCAL (storage/app/public)\n";
}

// 6. Test URL generation
echo "\n🌐 6. TEST GÉNÉRATION URL\n";
echo "───────────────────────────────────────────────────────────\n";

$testPath = 'legal_documents/test.pdf';
try {
    $url = Utility::get_file($testPath);
    echo "   Utility::get_file('$testPath')\n";
    echo "   → URL: $url\n";
    
    if (strpos($url, 'files.dossypro.com') !== false || strpos($url, 'r2.dev') !== false) {
        echo "   ✅ URL R2 générée correctement\n";
    } else {
        echo "   ⚠️  URL locale générée (pas R2)\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

// 7. Recommandations
echo "\n💡 7. DIAGNOSTIC ET RECOMMANDATIONS\n";
echo "═══════════════════════════════════════════════════════════\n";

if ($storageSettingDB !== 'r2') {
    echo "❌ PROBLÈME IDENTIFIÉ:\n";
    echo "   storage_setting n'est pas 'r2' dans la base de données\n";
    echo "   Valeur actuelle: '$storageSettingDB'\n\n";
    echo "✅ SOLUTION:\n";
    echo "   Exécutez cette requête SQL:\n";
    echo "   \n";
    echo "   UPDATE settings SET value = 'r2' WHERE `key` = 'storage_setting';\n";
    echo "   \n";
    echo "   Ou utilisez l'interface Admin:\n";
    echo "   Super Admin → Settings → Storage Settings → Sélectionner 'R2'\n";
} elseif ($storageSetting !== 'r2') {
    echo "❌ PROBLÈME IDENTIFIÉ:\n";
    echo "   Cache Laravel en cours\n";
    echo "   DB dit 'r2' mais Utility::settings() dit '$storageSetting'\n\n";
    echo "✅ SOLUTION:\n";
    echo "   php artisan cache:clear\n";
    echo "   php artisan config:clear\n";
} else {
    echo "✅ TOUT EST CORRECT!\n";
    echo "   storage_setting = 'r2'\n";
    echo "   Les nouveaux uploads devraient aller vers R2\n\n";
    
    if (empty($r2Settings['r2_url'])) {
        echo "⚠️  ATTENTION:\n";
        echo "   r2_url n'est pas configuré!\n";
        echo "   Les fichiers seront uploadés mais les URLs seront incorrectes\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "   Diagnostic terminé - " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════\n\n";
