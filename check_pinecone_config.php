<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MobileAppSetting;

echo "=== VERIFICATION CONFIGURATION PINECONE ===\n\n";

$settings = MobileAppSetting::first();

if (!$settings) {
    echo "❌ Aucun enregistrement dans la table mobile_app_settings\n";
    exit(1);
}

echo "📊 Configuration actuelle dans mobile_app_settings:\n";
echo "─────────────────────────────────────────────────\n\n";

// Pinecone API Key
$apiKey = $settings->pinecone_api_key ?? 'NULL';
echo "🔑 Pinecone API Key:\n";
if ($apiKey && $apiKey !== 'NULL') {
    echo "   ✅ Configuré: " . substr($apiKey, 0, 20) . "..." . substr($apiKey, -10) . "\n";
} else {
    echo "   ❌ Non configuré (NULL)\n";
}
echo "\n";

// Pinecone Environment
$environment = $settings->pinecone_environment ?? 'NULL';
echo "🌍 Pinecone Environment:\n";
if ($environment && $environment !== 'NULL') {
    echo "   ✅ Configuré: {$environment}\n";
} else {
    echo "   ❌ Non configuré (NULL)\n";
}
echo "\n";

// Pinecone Index
$index = $settings->pinecone_index ?? 'NULL';
echo "📇 Pinecone Index Name:\n";
if ($index && $index !== 'NULL') {
    echo "   ✅ Configuré: {$index}\n";
} else {
    echo "   ❌ Non configuré (NULL)\n";
}
echo "\n";

// Pinecone Host (CRITIQUE)
$host = $settings->pinecone_host ?? 'NULL';
echo "🌐 Pinecone Host:\n";
if ($host && $host !== 'NULL' && $host !== '') {
    echo "   ✅ Configuré: {$host}\n";
    
    // Vérifier si c'est le bon format
    if (strpos($host, '-7udtg21') !== false && strpos($host, '-b74a') !== false) {
        echo "   ✅ Format CORRECT (contient -7udtg21 et -b74a)\n";
    } else {
        echo "   ⚠️  Format INCORRECT (manque suffixes -7udtg21 ou -b74a)\n";
        echo "   ℹ️  Host attendu: dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io\n";
    }
} else {
    echo "   ❌ Non configuré (NULL ou vide)\n";
    echo "   ⚠️  Le système utilisera le fallback .env ou construction automatique\n";
}
echo "\n";

// Pinecone Verify SSL
$verifySsl = $settings->pinecone_verify_ssl ?? 'NULL';
echo "🔒 Pinecone Verify SSL:\n";
if ($verifySsl !== 'NULL') {
    echo "   ✅ Configuré: " . ($verifySsl ? 'true' : 'false') . "\n";
} else {
    echo "   ⚠️  Non configuré (utilisera défaut: true)\n";
}
echo "\n";

// OpenAI API Key
$openaiKey = $settings->openai_api_key ?? 'NULL';
echo "🤖 OpenAI API Key:\n";
if ($openaiKey && $openaiKey !== 'NULL') {
    echo "   ✅ Configuré: " . substr($openaiKey, 0, 15) . "..." . substr($openaiKey, -5) . "\n";
} else {
    echo "   ❌ Non configuré (NULL)\n";
}
echo "\n";

echo "─────────────────────────────────────────────────\n";
echo "\n📋 RESUME:\n\n";

$configured = 0;
$total = 6;

if ($apiKey && $apiKey !== 'NULL') $configured++;
if ($environment && $environment !== 'NULL') $configured++;
if ($index && $index !== 'NULL') $configured++;
if ($host && $host !== 'NULL' && $host !== '') $configured++;
if ($verifySsl !== 'NULL') $configured++;
if ($openaiKey && $openaiKey !== 'NULL') $configured++;

echo "Champs configurés: {$configured}/{$total}\n\n";

if ($configured === $total) {
    echo "✅ Tous les champs sont configurés\n";
} else {
    echo "⚠️  Il y a des champs non configurés\n";
}

// Vérifier la priorité de configuration
echo "\n🔍 ORDRE DE PRIORITE DES CONFIGURATIONS:\n\n";
echo "1. Mobile App Settings (Base de données) - PRIORITE MAXIMALE\n";
echo "2. Fichier .env - FALLBACK\n";
echo "3. Construction automatique - DERNIER RECOURS\n\n";

if (!$host || $host === 'NULL' || $host === '') {
    echo "⚠️  IMPORTANT: pinecone_host est vide dans mobile_app_settings\n";
    echo "   → Le système utilisera la valeur de .env\n";
    echo "   → Si .env est aussi vide, construction automatique (INCORRECT)\n\n";
    
    $envHost = env('PINECONE_HOST');
    if ($envHost) {
        echo "ℹ️  Valeur dans .env: {$envHost}\n";
    } else {
        echo "❌ .env PINECONE_HOST est aussi vide!\n";
        echo "   → Construction automatique: {$index}.svc.{$environment}.pinecone.io\n";
        echo "   → Ce format est INCORRECT (manque suffixes)\n";
    }
}

echo "\n";
echo "=== FIN VERIFICATION ===\n";
