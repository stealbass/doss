<?php
/**
 * Test de connexion Pinecone avec les vraies credentials
 * Index Name: dossy-legal-doc (pas de 's')
 * Environment: gcp-starter
 */

$apiKey = 'pcsk_6aNQwn_E9qFgJtrPf9CCWgqo8RRVsE9wcxadY18FARzd';
$indexHost = 'dossy-legal-doc.svc.gcp-starter.pinecone.io';

echo "🔌 Test de connexion à Pinecone...\n";
echo "Index: dossy-legal-doc\n";
echo "Host: {$indexHost}\n\n";

// Test 1: describe_index_stats
$url = "https://{$indexHost}/describe_index_stats";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Api-Key: {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => true,
]);

echo "📊 Test 1: Describe Index Stats\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "❌ Erreur cURL: {$error}\n";
    echo "Code HTTP: {$httpCode}\n\n";
    
    // Si erreur TLS (error 35), proposer workaround
    if (strpos($error, 'gnutls_handshake') !== false) {
        echo "⚠️ ERREUR TLS DÉTECTÉE (gnutls_handshake)\n";
        echo "→ Votre serveur utilise gnutls au lieu de OpenSSL\n";
        echo "→ Solution: Workaround SSL temporaire (voir ci-dessous)\n\n";
    }
} else {
    echo "✅ Connexion réussie!\n";
    echo "Code HTTP: {$httpCode}\n";
    echo "Réponse: {$response}\n\n";
}

curl_close($ch);

// Test 2: Query avec désactivation SSL (WORKAROUND)
echo "📊 Test 2: Query avec workaround SSL\n";
$url = "https://{$indexHost}/query";

$testVector = array_fill(0, 1536, 0.01); // Vecteur de test

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Api-Key: {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'vector' => $testVector,
        'topK' => 3,
        'includeMetadata' => true
    ]),
    CURLOPT_TIMEOUT => 30,
    // WORKAROUND: Désactiver vérification SSL
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "❌ Erreur: {$error}\n";
    echo "Code HTTP: {$httpCode}\n";
} else {
    echo "✅ Query réussie avec workaround SSL!\n";
    echo "Code HTTP: {$httpCode}\n";
    echo "Réponse: " . substr($response, 0, 200) . "...\n";
}

curl_close($ch);

echo "\n===========================================\n";
echo "RÉSUMÉ\n";
echo "===========================================\n";
echo "Si Test 1 échoue avec erreur TLS → Appliquer workaround SSL\n";
echo "Si Test 2 réussit → Workaround fonctionne, à implémenter temporairement\n";
echo "Solution permanente → Contacter hébergeur pour mise à jour OpenSSL\n";
