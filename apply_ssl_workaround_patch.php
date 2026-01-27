<?php
/**
 * Script pour appliquer automatiquement le patch SSL workaround
 * Utilise avec Laravel HTTP (pas cURL natif)
 */

echo "🔧 Application du patch SSL workaround pour Pinecone\n\n";

// 1. Créer config/http.php
echo "📝 Création de config/http.php...\n";

$httpConfigPath = __DIR__ . '/config/http.php';
$httpConfig = <<<'PHP'
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    | 
    | Workaround temporaire pour erreur TLS gnutls avec Pinecone
    | ⚠️ À RETIRER après correction du serveur par l'hébergeur
    |
    */

    'options' => [
        'verify' => false, // ⚠️ TEMPORAIRE SEULEMENT!
    ],
];

PHP;

file_put_contents($httpConfigPath, $httpConfig);
echo "✅ config/http.php créé\n\n";

// 2. Modifier app/Services/AdvancedRagService.php
echo "📝 Modification de AdvancedRagService.php...\n";

$servicePath = __DIR__ . '/app/Services/AdvancedRagService.php';
$serviceContent = file_get_contents($servicePath);

// Trouver et remplacer toutes les requêtes Pinecone
$patterns = [
    // Pattern 1: queryPinecone method
    [
        'search' => "            \$response = Http::withHeaders([\n                'Api-Key' => \$this->pineconeApiKey,\n                'Content-Type' => 'application/json',\n            ])->post(\$url, [",
        'replace' => "            \$response = Http::withOptions([\n                'verify' => false  // ⚠️ Workaround TLS temporaire\n            ])->withHeaders([\n                'Api-Key' => \$this->pineconeApiKey,\n                'Content-Type' => 'application/json',\n            ])->post(\$url, ["
    ],
    // Pattern 2: deleteVectors method
    [
        'search' => "            \$response = Http::withHeaders([\n                'Api-Key' => \$this->pineconeApiKey,\n                'Content-Type' => 'application/json',\n            ])->delete(\$url, [",
        'replace' => "            \$response = Http::withOptions([\n                'verify' => false  // ⚠️ Workaround TLS temporaire\n            ])->withHeaders([\n                'Api-Key' => \$this->pineconeApiKey,\n                'Content-Type' => 'application/json',\n            ])->delete(\$url, ["
    ]
];

$modified = false;
foreach ($patterns as $pattern) {
    if (strpos($serviceContent, $pattern['search']) !== false) {
        $serviceContent = str_replace($pattern['search'], $pattern['replace'], $serviceContent);
        $modified = true;
        echo "✅ Pattern trouvé et modifié\n";
    }
}

if ($modified) {
    // Backup original
    copy($servicePath, $servicePath . '.backup');
    echo "✅ Backup créé: AdvancedRagService.php.backup\n";
    
    // Save modified file
    file_put_contents($servicePath, $serviceContent);
    echo "✅ AdvancedRagService.php modifié\n\n";
} else {
    echo "⚠️ Aucune modification nécessaire (peut-être déjà appliqué?)\n\n";
}

// 3. Ajouter un commentaire TODO dans le fichier
$todoComment = <<<'PHP'

// ⚠️⚠️⚠️ TODO: RETIRER CE WORKAROUND SSL APRÈS FIX SERVEUR ⚠️⚠️⚠️
// Ticket support hébergeur ouvert le: [DATE]
// À retirer: withOptions(['verify' => false])
// Fichier à supprimer: config/http.php

PHP;

if (strpos($serviceContent, 'TODO: RETIRER CE WORKAROUND') === false) {
    // Insérer après la première ligne <?php
    $serviceContent = preg_replace(
        '/^<\?php\n/',
        "<?php\n{$todoComment}\n",
        $serviceContent
    );
    file_put_contents($servicePath, $serviceContent);
    echo "✅ Commentaire TODO ajouté\n\n";
}

// 4. Clear cache Laravel
echo "🗑️ Clear cache Laravel...\n";
exec('php artisan config:clear');
exec('php artisan cache:clear');
echo "✅ Cache cleared\n\n";

// 5. Test connexion
echo "🧪 Test de connexion Pinecone avec workaround...\n";
include __DIR__ . '/test_pinecone_connection_real.php';

echo "\n";
echo "===========================================\n";
echo "✅ PATCH APPLIQUÉ AVEC SUCCÈS\n";
echo "===========================================\n";
echo "\n";
echo "⚠️ IMPORTANT:\n";
echo "1. Ce workaround désactive la vérification SSL\n";
echo "2. À utiliser UNIQUEMENT en développement/test\n";
echo "3. Ouvrir ticket support hébergeur IMMÉDIATEMENT\n";
echo "4. RETIRER dès que l'hébergeur corrige le problème\n";
echo "\n";
echo "Pour retirer le patch:\n";
echo "  rm config/http.php\n";
echo "  cp app/Services/AdvancedRagService.php.backup app/Services/AdvancedRagService.php\n";
echo "  php artisan config:clear\n";
echo "\n";
echo "Fichiers modifiés:\n";
echo "  - config/http.php (créé)\n";
echo "  - app/Services/AdvancedRagService.php (modifié)\n";
echo "  - app/Services/AdvancedRagService.php.backup (backup)\n";
echo "\n";
