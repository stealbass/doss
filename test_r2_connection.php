<?php
require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Charger Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TEST CONNEXION R2 ===\n\n";

// Récupérer les settings R2
$settings = DB::table('settings')
    ->whereIn('name', ['r2_key', 'r2_secret', 'r2_bucket', 'r2_endpoint', 'r2_url'])
    ->pluck('value', 'name')
    ->toArray();

echo "Configuration R2:\n";
echo "- r2_bucket: " . ($settings['r2_bucket'] ?? 'NON CONFIGURÉ') . "\n";
echo "- r2_endpoint: " . ($settings['r2_endpoint'] ?? 'NON CONFIGURÉ') . "\n";
echo "- r2_url: " . ($settings['r2_url'] ?? 'NON CONFIGURÉ') . "\n";
echo "- r2_key: " . (isset($settings['r2_key']) && !empty($settings['r2_key']) ? 'CONFIGURÉ (****)' : 'NON CONFIGURÉ') . "\n";
echo "- r2_secret: " . (isset($settings['r2_secret']) && !empty($settings['r2_secret']) ? 'CONFIGURÉ (****)' : 'NON CONFIGURÉ') . "\n\n";

// Test connexion S3/R2
try {
    $config = [
        'driver' => 's3',
        'key' => $settings['r2_key'] ?? '',
        'secret' => $settings['r2_secret'] ?? '',
        'region' => 'auto',
        'bucket' => $settings['r2_bucket'] ?? '',
        'endpoint' => $settings['r2_endpoint'] ?? '',
        'use_path_style_endpoint' => false,
    ];
    
    \Illuminate\Support\Facades\Config::set('filesystems.disks.r2', $config);
    
    echo "Test listage fichiers R2...\n";
    $files = \Illuminate\Support\Facades\Storage::disk('r2')->files('uploads/landing_page_image');
    
    if (empty($files)) {
        echo "⚠️  AUCUN fichier trouvé dans uploads/landing_page_image\n";
        echo "Le dossier existe sur R2 mais est vide.\n\n";
        
        echo "Fichiers à la racine de 'uploads':\n";
        $rootFiles = \Illuminate\Support\Facades\Storage::disk('r2')->files('uploads');
        if (empty($rootFiles)) {
            echo "⚠️  Aucun fichier dans 'uploads' non plus.\n";
        } else {
            foreach (array_slice($rootFiles, 0, 10) as $file) {
                echo "  - $file\n";
            }
        }
    } else {
        echo "✅ Fichiers trouvés:\n";
        foreach ($files as $file) {
            echo "  - $file\n";
            $url = \Illuminate\Support\Facades\Storage::disk('r2')->url($file);
            echo "    URL: $url\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN TEST ===\n";
