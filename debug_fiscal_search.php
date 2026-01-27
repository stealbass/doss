<?php
// Debug script to test fiscal resource search
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Models/FiscalSocialResource.php';

use App\Models\FiscalSocialResource;
use Illuminate\Database\Capsule\Manager as DB;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class)->handle(
    $request = \Illuminate\Http\Request::capture()
);

// Query fiscal resources for Cameroon
$results = FiscalSocialResource::where('country', 'Cameroon')
    ->orWhere('country', 'Cameroun')
    ->get();

echo "=== Fiscal Resources for Cameroon ===\n";
echo "Total found: " . $results->count() . "\n\n";

foreach ($results as $resource) {
    echo "ID: " . $resource->id . "\n";
    echo "Title: " . $resource->title . "\n";
    echo "Description: " . ($resource->description ? substr($resource->description, 0, 200) : 'N/A') . "\n";
    echo "AI Context: " . ($resource->ai_context ? substr($resource->ai_context, 0, 200) : 'N/A') . "\n";
    echo "Key Points: " . (is_array($resource->key_points) ? implode(', ', $resource->key_points) : $resource->key_points) . "\n";
    echo "Content: " . ($resource->content ? substr($resource->content, 0, 200) : 'N/A') . "\n";
    echo "---\n\n";
}

// Now test the FULLTEXT search
echo "\n=== Testing FULLTEXT Search ===\n";
$searchQuery = "centres de gestion agréés";
echo "Query: " . $searchQuery . "\n\n";

$searchResults = FiscalSocialResource::whereRaw(
    "MATCH(title, description, ai_context, key_points) AGAINST(? IN BOOLEAN MODE)",
    [$searchQuery]
)
->where('country', 'Cameroon')
->orWhere('country', 'Cameroun')
->get();

echo "FULLTEXT Results: " . $searchResults->count() . "\n";
foreach ($searchResults as $resource) {
    echo "- " . $resource->title . "\n";
}

// Test with keyword variations
echo "\n=== Testing Keyword Variations ===\n";
$keywords = ['centres gestion', 'agréés', 'gestion', 'Cameroun', 'fiscal'];
foreach ($keywords as $keyword) {
    $count = FiscalSocialResource::where('country', 'Cameroon')
        ->orWhere('country', 'Cameroun')
        ->where(function($q) use ($keyword) {
            $q->where('title', 'LIKE', '%' . $keyword . '%')
              ->orWhere('description', 'LIKE', '%' . $keyword . '%')
              ->orWhere('ai_context', 'LIKE', '%' . $keyword . '%');
        })
        ->count();
    echo "Keyword '$keyword': $count results\n";
}
?>
