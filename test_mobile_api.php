<?php

// Test direct des endpoints mobile API

echo "=== TEST MOBILE API ENDPOINTS ===\n\n";

// Simuler une requête pour récupérer les routes
$baseUrl = 'https://dossypro.com';

// Test 1: Templates
echo "1. Testing Templates Download Endpoint\n";
echo "   Expected: {$baseUrl}/api/mobile/templates/1/download\n";
echo "   Status: ";

$routes = [
    'templates_index' => '/api/mobile/templates',
    'templates_download' => '/api/mobile/templates/1/download',
    'fiscal_resources' => '/api/mobile/fiscal-resources',
    'legal_search' => '/api/mobile/documents/search',
];

foreach ($routes as $name => $route) {
    echo "\n{$name}: {$baseUrl}{$route}\n";
}

echo "\n\n=== VERIFICATION LOCALE ===\n";

// Charger Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Lister les routes mobile
$routes = app('router')->getRoutes();
$mobileRoutes = [];

foreach ($routes as $route) {
    $uri = $route->uri();
    if (strpos($uri, 'api/mobile') === 0) {
        $mobileRoutes[] = [
            'method' => implode('|', $route->methods()),
            'uri' => $uri,
            'name' => $route->getName(),
            'action' => $route->getActionName(),
        ];
    }
}

echo "\nRoutes Mobile trouvées: " . count($mobileRoutes) . "\n";
echo "\nRoutes pertinentes:\n";

$searchTerms = ['templates', 'fiscal', 'documents/search'];
foreach ($mobileRoutes as $route) {
    foreach ($searchTerms as $term) {
        if (stripos($route['uri'], $term) !== false) {
            echo "  [{$route['method']}] /{$route['uri']}\n";
            echo "      -> {$route['action']}\n";
            break;
        }
    }
}

echo "\n=== TEST MODELE MobileAppSubscription ===\n";

try {
    $subscription = App\Models\MobileAppSubscription::with('plan')->first();
    if ($subscription) {
        echo "✓ Model chargé\n";
        echo "  - ID: {$subscription->id}\n";
        echo "  - Plan: " . ($subscription->plan ? $subscription->plan->name : 'NULL') . "\n";
        
        // Tester canSearch()
        if (method_exists($subscription, 'canSearch')) {
            echo "✓ Méthode canSearch() existe\n";
            $canSearch = $subscription->canSearch();
            echo "  - Résultat: " . ($canSearch ? 'true' : 'false') . "\n";
        } else {
            echo "✗ Méthode canSearch() MANQUANTE!\n";
        }
    } else {
        echo "✗ Aucune subscription trouvée\n";
    }
} catch (\Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DES TESTS ===\n";
