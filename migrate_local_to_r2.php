<?php
/**
 * Script de migration des fichiers locaux vers Cloudflare R2
 * 
 * Ce script copie tous les fichiers du stockage local vers R2
 * sans supprimer les fichiers locaux (backup conservé)
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

// Charger Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  MIGRATION FICHIERS LOCAUX → CLOUDFLARE R2\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Récupérer configuration R2
$settings = DB::table('settings')
    ->whereIn('name', ['r2_key', 'r2_secret', 'r2_bucket', 'r2_endpoint', 'r2_url', 'r2_region'])
    ->pluck('value', 'name')
    ->toArray();

echo "📋 Configuration R2:\n";
echo "   Bucket: " . ($settings['r2_bucket'] ?? 'NON CONFIGURÉ') . "\n";
echo "   Endpoint: " . ($settings['r2_endpoint'] ?? 'NON CONFIGURÉ') . "\n";
echo "   URL publique: " . ($settings['r2_url'] ?? 'NON CONFIGURÉ') . "\n";
echo "   Credentials: " . (isset($settings['r2_key']) && !empty($settings['r2_key']) ? '✅ Configurées' : '❌ Manquantes') . "\n\n";

if (empty($settings['r2_key']) || empty($settings['r2_secret']) || empty($settings['r2_bucket'])) {
    echo "❌ ERREUR: Configuration R2 incomplète dans la base de données.\n";
    echo "   Veuillez configurer R2 dans Admin → Paramètres → Stockage\n\n";
    exit(1);
}

// Configurer le disk R2
Config::set('filesystems.disks.r2', [
    'driver' => 's3',
    'key' => $settings['r2_key'],
    'secret' => $settings['r2_secret'],
    'region' => $settings['r2_region'] ?? 'auto',
    'bucket' => $settings['r2_bucket'],
    'endpoint' => $settings['r2_endpoint'],
    'url' => $settings['r2_url'],
    'use_path_style_endpoint' => false,
    'throw' => false,
]);

// Test connexion R2
echo "🔌 Test connexion à R2...\n";
try {
    $testFiles = Storage::disk('r2')->files('');
    echo "   ✅ Connexion R2 OK (bucket contient " . count($testFiles) . " fichiers)\n\n";
} catch (\Exception $e) {
    echo "   ❌ ERREUR connexion R2: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Dossiers à migrer
$directories = [
    'uploads/landing_page_image',  // Logo site, favicon
    'uploads/profile',              // Avatars utilisateurs
    'uploads/logo',                 // Logos entreprises
    'uploads/documents',            // Documents
    'uploads/bill',                 // Factures
    'legal_documents',              // Bibliothèque juridique (PDFs)
    'uploads',                      // Autres fichiers à la racine
];

$stats = [
    'total' => 0,
    'uploaded' => 0,
    'skipped' => 0,
    'errors' => 0,
];

echo "🚀 Début migration...\n\n";

foreach ($directories as $directory) {
    echo "📁 Traitement: $directory\n";
    
    // Vérifier si le dossier existe localement
    $localPath = storage_path('app/public/' . $directory);
    
    if (!file_exists($localPath)) {
        echo "   ⚠️  Dossier local inexistant, passage au suivant\n\n";
        continue;
    }
    
    // Lister fichiers locaux
    try {
        // Utiliser glob pour lister les fichiers réels
        $pattern = $localPath . '/*';
        $localFiles = glob($pattern);
        
        if (empty($localFiles)) {
            echo "   ℹ️  Dossier vide\n\n";
            continue;
        }
        
        echo "   Trouvé " . count($localFiles) . " fichier(s)\n";
        
        foreach ($localFiles as $localFilePath) {
            if (is_dir($localFilePath)) {
                continue; // Skip subdirectories for now
            }
            
            $stats['total']++;
            
            $filename = basename($localFilePath);
            $relativePath = $directory . '/' . $filename;
            
            // Vérifier si le fichier existe déjà sur R2
            if (Storage::disk('r2')->exists($relativePath)) {
                echo "   ⏭️  Existe déjà: $filename\n";
                $stats['skipped']++;
                continue;
            }
            
            // Copier vers R2
            try {
                $fileContent = file_get_contents($localFilePath);
                $mimeType = mime_content_type($localFilePath);
                
                Storage::disk('r2')->put($relativePath, $fileContent, [
                    'visibility' => 'public',
                    'ContentType' => $mimeType,
                ]);
                
                echo "   ✅ Uploadé: $filename (" . number_format(filesize($localFilePath) / 1024, 2) . " KB)\n";
                $stats['uploaded']++;
                
            } catch (\Exception $e) {
                echo "   ❌ ERREUR upload $filename: " . $e->getMessage() . "\n";
                $stats['errors']++;
            }
        }
        
        echo "\n";
        
    } catch (\Exception $e) {
        echo "   ❌ ERREUR listage: " . $e->getMessage() . "\n\n";
        $stats['errors']++;
    }
}

// Résumé
echo "═══════════════════════════════════════════════════════════\n";
echo "  RÉSUMÉ MIGRATION\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "📊 Fichiers traités: " . $stats['total'] . "\n";
echo "✅ Uploadés sur R2: " . $stats['uploaded'] . "\n";
echo "⏭️  Déjà existants (ignorés): " . $stats['skipped'] . "\n";
echo "❌ Erreurs: " . $stats['errors'] . "\n\n";

if ($stats['uploaded'] > 0) {
    echo "🎉 Migration réussie !\n\n";
    echo "📋 PROCHAINES ÉTAPES:\n";
    echo "1. Vérifiez que les fichiers sont accessibles:\n";
    echo "   " . ($settings['r2_url'] ?? '') . "/uploads/landing_page_image/site_logo.png\n\n";
    echo "2. Si tout fonctionne, vous pouvez changer le Storage setting à 'r2'\n";
    echo "   dans Admin → Paramètres → Stockage\n\n";
    echo "3. Les fichiers locaux sont conservés (backup)\n";
    echo "   Vous pouvez les supprimer plus tard si tout fonctionne.\n\n";
} else {
    echo "ℹ️  Aucun nouveau fichier à migrer.\n\n";
}

echo "FIN DU SCRIPT\n\n";
