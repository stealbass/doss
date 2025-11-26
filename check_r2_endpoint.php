#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   🔍 VÉRIFICATION ENDPOINT R2\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// Récupérer l'endpoint depuis la DB
$r2Endpoint = DB::table('settings')->where('key', 'r2_endpoint')->value('value');

echo "📋 ENDPOINT R2 ACTUEL DANS LA BASE DE DONNÉES:\n";
echo "───────────────────────────────────────────────────────────────────\n";
echo "   Valeur: $r2Endpoint\n\n";

// Analyser le problème
echo "🔍 ANALYSE:\n";
echo "───────────────────────────────────────────────────────────────────\n";

if (strpos($r2Endpoint, '//') !== false && strpos($r2Endpoint, 'https://') === 0) {
    $doubleSlashPos = strpos($r2Endpoint, '//', 8); // Chercher après "https://"
    if ($doubleSlashPos !== false) {
        echo "   ❌ PROBLÈME: Double slash '//' détecté à la position $doubleSlashPos\n";
        echo "   L'URL contient un double slash qui casse la connexion R2\n\n";
    }
}

if (strpos($r2Endpoint, '.r2.cloudflarestorage.com') === false) {
    echo "   ❌ PROBLÈME: Le format ne correspond pas au format Cloudflare R2\n";
    echo "   Format attendu: https://<ACCOUNT_ID>.r2.cloudflarestorage.com\n\n";
}

echo "✅ FORMAT CORRECT POUR CLOUDFLARE R2:\n";
echo "───────────────────────────────────────────────────────────────────\n";
echo "   https://<VOTRE_ACCOUNT_ID>.r2.cloudflarestorage.com\n\n";

echo "📝 OÙ TROUVER VOTRE ACCOUNT ID:\n";
echo "───────────────────────────────────────────────────────────────────\n";
echo "   1. Connectez-vous à Cloudflare Dashboard\n";
echo "   2. Allez dans R2 Object Storage\n";
echo "   3. Ouvrez votre bucket 'dossy-pro-documents'\n";
echo "   4. Cliquez sur 'Settings'\n";
echo "   5. Dans 'S3 API', copiez l'URL qui ressemble à:\n";
echo "      https://xxxxxxxxxxxxx.r2.cloudflarestorage.com\n\n";

echo "🔧 PROPOSITION DE CORRECTION:\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// Essayer d'extraire l'Account ID si possible
if (preg_match('/https:\/\/([a-f0-9]+)\.r2\.cloudflarestorage\.com/', $r2Endpoint, $matches)) {
    $accountId = $matches[1];
    $correctEndpoint = "https://{$accountId}.r2.cloudflarestorage.com";
    
    echo "✅ Account ID détecté: $accountId\n";
    echo "✅ Endpoint corrigé: $correctEndpoint\n\n";
    
    echo "Pour corriger, exécutez cette requête SQL:\n\n";
    echo "UPDATE settings SET value = '$correctEndpoint' WHERE `key` = 'r2_endpoint';\n\n";
} else {
    echo "❌ Impossible d'extraire l'Account ID automatiquement\n";
    echo "   L'endpoint actuel est trop malformé\n\n";
    
    echo "CORRECTION MANUELLE REQUISE:\n";
    echo "1. Trouvez votre Account ID sur Cloudflare Dashboard\n";
    echo "2. Exécutez cette requête SQL:\n\n";
    echo "   UPDATE settings \n";
    echo "   SET value = 'https://<VOTRE_ACCOUNT_ID>.r2.cloudflarestorage.com' \n";
    echo "   WHERE `key` = 'r2_endpoint';\n\n";
}

echo "═══════════════════════════════════════════════════════════════════\n\n";
