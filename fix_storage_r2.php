#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "   🔧 CORRECTION AUTOMATIQUE STORAGE → R2\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// 1. Vérifier l'état actuel
echo "📊 1. VÉRIFICATION DE L'ÉTAT ACTUEL\n";
echo "───────────────────────────────────────────────────────────\n";

$currentValue = DB::table('settings')
    ->where('key', 'storage_setting')
    ->value('value');

echo "   Valeur actuelle de storage_setting : " . ($currentValue ?? 'NON TROUVÉ') . "\n";

if ($currentValue === 'r2') {
    echo "   ✅ Déjà configuré sur 'r2'\n\n";
    
    echo "🧹 2. NETTOYAGE DU CACHE\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "   Clearing cache...\n";
    Artisan::call('cache:clear');
    echo "   ✅ Cache cleared\n";
    
    echo "   Clearing config...\n";
    Artisan::call('config:clear');
    echo "   ✅ Config cleared\n";
    
    echo "   Clearing view cache...\n";
    Artisan::call('view:clear');
    echo "   ✅ View cache cleared\n\n";
    
    echo "✅ TOUT EST BON!\n";
    echo "   Les nouveaux uploads devraient maintenant aller vers R2\n";
    echo "   Testez en uploadant un document dans Legal Library\n";
} else {
    echo "   ⚠️  Actuellement: '$currentValue' (doit être 'r2')\n\n";
    
    echo "🔧 2. MISE À JOUR DE LA CONFIGURATION\n";
    echo "───────────────────────────────────────────────────────────\n";
    
    $exists = DB::table('settings')->where('key', 'storage_setting')->exists();
    
    if ($exists) {
        DB::table('settings')
            ->where('key', 'storage_setting')
            ->update(['value' => 'r2']);
        echo "   ✅ storage_setting mis à jour : 'r2'\n";
    } else {
        DB::table('settings')->insert([
            'key' => 'storage_setting',
            'value' => 'r2',
            'created_by' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   ✅ storage_setting créé : 'r2'\n";
    }
    
    echo "\n🧹 3. NETTOYAGE DU CACHE\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "   Clearing cache...\n";
    Artisan::call('cache:clear');
    echo "   ✅ Cache cleared\n";
    
    echo "   Clearing config...\n";
    Artisan::call('config:clear');
    echo "   ✅ Config cleared\n";
    
    echo "   Clearing view cache...\n";
    Artisan::call('view:clear');
    echo "   ✅ View cache cleared\n\n";
    
    // Vérifier que ça a marché
    $newValue = DB::table('settings')
        ->where('key', 'storage_setting')
        ->value('value');
    
    echo "📊 4. VÉRIFICATION FINALE\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "   Nouvelle valeur : '$newValue'\n";
    
    if ($newValue === 'r2') {
        echo "   ✅ SUCCÈS! storage_setting = 'r2'\n\n";
        echo "✅ CORRECTION TERMINÉE!\n";
        echo "   Les nouveaux uploads iront maintenant vers R2\n";
        echo "   Testez en uploadant un document dans Legal Library\n";
    } else {
        echo "   ❌ ÉCHEC! La valeur n'a pas été mise à jour\n";
    }
}

// Vérifier la config R2
echo "\n📋 5. VÉRIFICATION CONFIGURATION R2\n";
echo "═══════════════════════════════════════════════════════════\n";

$r2Keys = ['r2_key', 'r2_secret', 'r2_bucket', 'r2_endpoint', 'r2_url'];
$r2Config = DB::table('settings')
    ->whereIn('key', $r2Keys)
    ->pluck('value', 'key')
    ->toArray();

$allConfigured = true;
foreach ($r2Keys as $key) {
    if (empty($r2Config[$key])) {
        echo "   ❌ $key : NON CONFIGURÉ\n";
        $allConfigured = false;
    } else {
        if (in_array($key, ['r2_key', 'r2_secret'])) {
            echo "   ✅ $key : " . substr($r2Config[$key], 0, 10) . "...\n";
        } else {
            echo "   ✅ $key : {$r2Config[$key]}\n";
        }
    }
}

if (!$allConfigured) {
    echo "\n⚠️  ATTENTION:\n";
    echo "   Certains paramètres R2 ne sont pas configurés\n";
    echo "   Configurez-les via: Super Admin → Settings → Storage Settings\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "   Script terminé - " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════\n\n";
