<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\DocumentTemplate;
use App\Models\FiscalSocialResource;
use App\Models\CalculatorConfig;
use App\Models\LegalAlert;

echo "=== DIAGNOSTIC BIBLIOTHÈQUE PRO ===\n\n";

// Templates
$templatesCount = DocumentTemplate::count();
$templatesVisible = DocumentTemplate::where('is_mobile_visible', true)->count();
echo "📄 Templates: $templatesCount total, $templatesVisible visibles mobile\n";
if ($templatesCount > 0) {
    $sample = DocumentTemplate::with('category')->first();
    echo "   Exemple: {$sample->title} - Catégorie: " . ($sample->category->name ?? 'N/A') . "\n";
    echo "   file_url: " . ($sample->file_url ?? 'NULL') . "\n";
    echo "   file_path: " . ($sample->file_path ?? 'NULL') . "\n";
}

echo "\n";

// Fiscal Resources  
echo "💰 Ressources Fiscales:\n";
try {
    $fiscalCount = FiscalSocialResource::count();
    $fiscalVisible = FiscalSocialResource::where('is_mobile_visible', true)->count();
    echo "   Total: $fiscalCount, Visibles mobile: $fiscalVisible\n";
    if ($fiscalCount > 0) {
        $sample = FiscalSocialResource::first();
        echo "   Exemple: {$sample->title} - Pays: {$sample->country}, Année: {$sample->year}\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n";

// Calculators
echo "🔢 Calculateurs:\n";
try {
    $calcCount = CalculatorConfig::count();
    $calcVisible = CalculatorConfig::where('is_mobile_visible', true)->count();
    $calcActive = CalculatorConfig::where('is_active', true)->count();
    echo "   Total: $calcCount, Visibles: $calcVisible, Actifs: $calcActive\n";
    if ($calcCount > 0) {
        $sample = CalculatorConfig::first();
        echo "   Exemple: {$sample->calculator_name} - Type: {$sample->calculator_type}\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n";

// Legal Alerts
echo "🔔 Alertes Juridiques:\n";
try {
    $alertsCount = LegalAlert::count();
    $alertsPublished = LegalAlert::where('is_published', true)->count();
    echo "   Total: $alertsCount, Publiées: $alertsPublished\n";
    if ($alertsCount > 0) {
        $sample = LegalAlert::first();
        echo "   Exemple: {$sample->title} - Pays: {$sample->country}\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
