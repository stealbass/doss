<?php

/**
 * Script de diagnostic complet RAG - Teste recherche fiscale, juridique, templates
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SimpleRagService;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "   DIAGNOSTIC COMPLET RAG - RECHERCHE MULTI-SOURCES\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Instancier le service RAG
$ragService = app(SimpleRagService::class);

// Test query
$query = "donne moi le repertoire des centres de gestion agréés";
$country = "Cameroun";

echo "📝 Query: '{$query}'\n";
echo "🌍 Pays: {$country}\n\n";

// ═══════════════════════════════════════════════════════════════
// TEST 1: Recherche FISCALE directe
// ═══════════════════════════════════════════════════════════════
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: RECHERCHE FISCALE DIRECTE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $fiscalResults = $ragService->searchFiscalResourcesByCountry($query, $country, null, 10);
    
    echo "✅ Recherche fiscale réussie\n";
    echo "Résultats trouvés: " . count($fiscalResults) . "\n\n";
    
    if (empty($fiscalResults)) {
        echo "⚠️  AUCUN résultat fiscal trouvé!\n\n";
        
        // Vérifier la base de données
        echo "Vérification base de données:\n";
        $totalFiscal = DB::table('fiscal_social_resources')->count();
        echo "  • Total ressources fiscales: {$totalFiscal}\n";
        
        $cameroonFiscal = DB::table('fiscal_social_resources')
            ->whereIn('country', ['CM', 'Cameroon', 'Cameroun'])
            ->count();
        echo "  • Ressources Cameroun: {$cameroonFiscal}\n";
        
        $mobileFiscal = DB::table('fiscal_social_resources')
            ->where('is_mobile_visible', true)
            ->count();
        echo "  • Ressources mobile_visible: {$mobileFiscal}\n";
        
        $latestFiscal = DB::table('fiscal_social_resources')
            ->where('is_latest_version', true)
            ->count();
        echo "  • Ressources latest_version: {$latestFiscal}\n\n";
        
    } else {
        foreach ($fiscalResults as $i => $res) {
            echo "Résultat #" . ($i + 1) . ":\n";
            echo "  ID: {$res['id']}\n";
            echo "  Titre: {$res['title']}\n";
            echo "  Pays: {$res['country']}\n";
            echo "  Année: {$res['year']}\n";
            echo "  Type: {$res['resource_type']}\n";
            echo "  Description: " . substr($res['description'] ?? 'N/A', 0, 80) . "...\n";
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ ERREUR recherche fiscale: " . $e->getMessage() . "\n\n";
}

// ═══════════════════════════════════════════════════════════════
// TEST 2: Recherche JURIDIQUE
// ═══════════════════════════════════════════════════════════════
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 2: RECHERCHE JURIDIQUE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $legalResults = $ragService->searchByCountry("loi code juridique", $country, 5);
    
    echo "✅ Recherche juridique réussie\n";
    echo "Résultats trouvés: " . count($legalResults) . "\n\n";
    
    if (!empty($legalResults)) {
        foreach ($legalResults as $i => $res) {
            echo "Résultat #" . ($i + 1) . ":\n";
            echo "  ID: {$res['id']}\n";
            echo "  Titre: {$res['title']}\n";
            echo "  Pays: {$res['country']}\n";
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ ERREUR recherche juridique: " . $e->getMessage() . "\n\n";
}

// ═══════════════════════════════════════════════════════════════
// TEST 3: Recherche TEMPLATES
// ═══════════════════════════════════════════════════════════════
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 3: RECHERCHE TEMPLATES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $templateResults = $ragService->searchTemplatesByCountry("contrat bail", $country, 5);
    
    echo "✅ Recherche templates réussie\n";
    echo "Résultats trouvés: " . count($templateResults) . "\n\n";
    
    if (!empty($templateResults)) {
        foreach ($templateResults as $i => $res) {
            echo "Résultat #" . ($i + 1) . ":\n";
            echo "  ID: {$res['id']}\n";
            echo "  Titre: {$res['name']}\n";
            echo "  Type: {$res['template_type']}\n";
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ ERREUR recherche templates: " . $e->getMessage() . "\n\n";
}

// ═══════════════════════════════════════════════════════════════
// TEST 4: Recherche MULTI-SOURCES (comme dans l'API)
// ═══════════════════════════════════════════════════════════════
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 4: RECHERCHE MULTI-SOURCES (simulation API)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $multiResult = $ragService->getContextWithMultipleSourcesByCountry($query, $country, 2000);
    
    echo "✅ Recherche multi-sources réussie\n";
    echo "Sources trouvées: " . count($multiResult['sources'] ?? []) . "\n";
    echo "Contexte généré: " . strlen($multiResult['context'] ?? '') . " caractères\n\n";
    
    if (!empty($multiResult['sources'])) {
        echo "SOURCES TROUVÉES:\n";
        foreach ($multiResult['sources'] as $i => $source) {
            echo "  " . ($i + 1) . ". [{$source['type']}] {$source['title']}\n";
        }
        echo "\n";
    } else {
        echo "⚠️  AUCUNE source trouvée dans la recherche multi-sources!\n\n";
    }
    
    if (!empty($multiResult['context'])) {
        echo "CONTEXTE PREVIEW:\n";
        echo substr($multiResult['context'], 0, 500) . "...\n\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERREUR recherche multi-sources: " . $e->getMessage() . "\n\n";
}

// ═══════════════════════════════════════════════════════════════
// TEST 5: Stats globales de la base
// ═══════════════════════════════════════════════════════════════
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 5: STATISTIQUES GLOBALES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$stats = [
    'Ressources Fiscales' => DB::table('fiscal_social_resources')->count(),
    'Documents Juridiques' => DB::table('legal_library')->count(),
    'Templates' => DB::table('document_templates')->count(),
];

foreach ($stats as $label => $count) {
    echo sprintf("%-30s: %d\n", $label, $count);
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "   FIN DU DIAGNOSTIC\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";
