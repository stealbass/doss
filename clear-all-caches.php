<?php
// Temporary script to clear Laravel cache
// Upload this to your server root and access via browser
// Delete this file after use for security

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Clearing caches...\n<br>";

Artisan::call('config:clear');
echo "✓ Config cache cleared\n<br>";

Artisan::call('route:clear');
echo "✓ Route cache cleared\n<br>";

Artisan::call('cache:clear');
echo "✓ Application cache cleared\n<br>";

Artisan::call('view:clear');
echo "✓ View cache cleared\n<br>";

Artisan::call('optimize:clear');
echo "✓ All optimization caches cleared\n<br>";

echo "\n<br><strong>All caches cleared successfully!</strong>\n<br>";
echo "Please delete this file for security.";
