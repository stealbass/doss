<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "═══════════════════════════════════════════════════════════════\n";
echo "   DIAGNOSTIC DES RESSOURCES FISCALES - CAMEROUN\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Test 1: All fiscal resources
echo "📊 TEST 1: TOTAL des ressources fiscales\n";
echo "─────────────────────────────────────────\n";
$total = DB::table('fiscal_social_resources')->count();
echo "Total dans la base: {$total}\n\n";

// Test 2: By country variants
echo "📍 TEST 2: Ressources par PAYS\n";
echo "─────────────────────────────────────────\n";
$countries = ['CM', 'Cameroon', 'Cameroun'];
foreach ($countries as $country) {
    $count = DB::table('fiscal_social_resources')
        ->where('country', $country)
        ->count();
    echo "  • Country = '{$country}': {$count} ressource(s)\n";
}
echo "\n";

// Test 3: By visibility flags
echo "🔍 TEST 3: Ressources MOBILES VISIBLES\n";
echo "─────────────────────────────────────────\n";
foreach ($countries as $country) {
    $count = DB::table('fiscal_social_resources')
        ->where('country', $country)
        ->where('is_mobile_visible', true)
        ->count();
    echo "  • Country = '{$country}' + is_mobile_visible: {$count}\n";
}
echo "\n";

// Test 4: Latest version flag
echo "📌 TEST 4: Ressources DERNIERE VERSION\n";
echo "─────────────────────────────────────────\n";
foreach ($countries as $country) {
    $count = DB::table('fiscal_social_resources')
        ->where('country', $country)
        ->where('is_latest_version', true)
        ->count();
    echo "  • Country = '{$country}' + is_latest_version: {$count}\n";
}
echo "\n";

// Test 5: STRICT (both filters)
echo "🔒 TEST 5: STRICT (mobile_visible + latest_version)\n";
echo "─────────────────────────────────────────\n";
foreach ($countries as $country) {
    $count = DB::table('fiscal_social_resources')
        ->where('country', $country)
        ->where('is_mobile_visible', true)
        ->where('is_latest_version', true)
        ->count();
    echo "  • Country = '{$country}': {$count}\n";
}
echo "\n";

// Test 6: List of ALL Cameroon resources (any variant)
echo "📋 TEST 6: LISTE DES RESSOURCES CAMEROUN\n";
echo "─────────────────────────────────────────\n";
$resources = DB::table('fiscal_social_resources')
    ->whereIn('country', $countries)
    ->select('id', 'title', 'country', 'year', 'is_mobile_visible', 'is_latest_version', 'resource_type')
    ->get();

if ($resources->isEmpty()) {
    echo "❌ AUCUNE ressource trouvée pour le Cameroun!\n";
} else {
    echo "✅ {$resources->count()} ressource(s) trouvée(s):\n\n";
    foreach ($resources as $r) {
        echo "  ID: {$r->id}\n";
        echo "  Titre: {$r->title}\n";
        echo "  Pays: {$r->country}\n";
        echo "  Année: {$r->year}\n";
        echo "  Type: {$r->resource_type}\n";
        echo "  Mobile visible: " . ($r->is_mobile_visible ? '✅ OUI' : '❌ NON') . "\n";
        echo "  Latest version: " . ($r->is_latest_version ? '✅ OUI' : '❌ NON') . "\n";
        echo "  ─────────────────────────────────────────\n";
    }
}
echo "\n";

// Test 7: Check for ANY fiscal resources
echo "🌍 TEST 7: TOUS LES PAYS (si Cameroun vide)\n";
echo "─────────────────────────────────────────\n";
$allCountries = DB::table('fiscal_social_resources')
    ->select('country', DB::raw('COUNT(*) as count'))
    ->groupBy('country')
    ->get();

foreach ($allCountries as $c) {
    echo "  • {$c->country}: {$c->count} ressource(s)\n";
}
echo "\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "   FIN DU DIAGNOSTIC\n";
echo "═══════════════════════════════════════════════════════════════\n";
