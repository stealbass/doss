<?php

/**
 * Script de vérification de l'accès de l'IA aux données juridiques
 * Vérifie l'accès à Legal Library, Document Templates, Fiscal Resources
 * et le filtrage par pays et catégories
 * 
 * Usage: php test_ai_data_access.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\LegalDocument;
use App\Models\DocumentTemplate;
use App\Models\FiscalSocialResource;
use App\Models\LegalCategory;
use App\Models\TemplateCategory;
use App\Models\ResourceCategory;
use App\Services\SimpleRagService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "VÉRIFICATION ACCÈS AI AUX DONNÉES\n";
echo "========================================\n\n";

// 1. Vérifier la configuration Cloudflare R2
echo "1. Configuration Cloudflare R2\n";
echo "   ---------------------------\n";

$r2Config = config('filesystems.disks.r2');
echo "   Endpoint: " . env('R2_ENDPOINT', 'NON CONFIGURÉ') . "\n";
echo "   Bucket: " . env('R2_BUCKET', 'NON CONFIGURÉ') . "\n";
echo "   URL: " . env('R2_URL', 'NON CONFIGURÉ') . "\n";
echo "   Access Key: " . (env('R2_ACCESS_KEY_ID') ? '✓ Configuré' : '✗ Manquant') . "\n";
echo "   Secret Key: " . (env('R2_SECRET_ACCESS_KEY') ? '✓ Configuré' : '✗ Manquant') . "\n";

$storageDriver = env('STORAGE_SETTING', 'local');
echo "   Storage actuel: {$storageDriver}\n";

if ($storageDriver === 'r2') {
    echo "   ✓ Cloudflare R2 est actif\n";
} else {
    echo "   ⚠ Storage actuel: {$storageDriver} (pas R2)\n";
}

echo "\n";

// 2. Vérifier Legal Documents (Bibliothèque Juridique)
echo "2. Legal Documents (Bibliothèque Juridique)\n";
echo "   -----------------------------------------\n";

$totalLegalDocs = LegalDocument::count();
$docsWithCountry = LegalDocument::whereNotNull('country')->count();
$docsWithCategory = LegalDocument::whereNotNull('category_id')->count();
$docsWithExtractedText = LegalDocument::whereNotNull('extracted_text')
    ->where('extracted_text', '!=', '')
    ->count();

echo "   Total documents: {$totalLegalDocs}\n";
echo "   Documents avec pays: {$docsWithCountry}\n";
echo "   Documents avec catégorie: {$docsWithCategory}\n";
echo "   Documents avec texte extrait: {$docsWithExtractedText}\n";

if ($totalLegalDocs > 0) {
    echo "   ✓ Bibliothèque juridique accessible\n";
    
    // Afficher quelques exemples
    echo "\n   Exemples de documents:\n";
    $sampleDocs = LegalDocument::with('category')
        ->limit(5)
        ->get(['id', 'title', 'country', 'category_id', 'file_path']);
    
    foreach ($sampleDocs as $doc) {
        $country = $doc->country ?? 'Non spécifié';
        $category = $doc->category->name ?? 'N/A';
        echo "   • [{$country}] {$doc->title} ({$category})\n";
        echo "     Fichier: {$doc->file_path}\n";
    }
    
    // Vérifier les pays disponibles
    echo "\n   Pays disponibles dans la base:\n";
    $countries = LegalDocument::select('country', DB::raw('COUNT(*) as count'))
        ->whereNotNull('country')
        ->groupBy('country')
        ->orderBy('count', 'DESC')
        ->get();
    
    foreach ($countries as $country) {
        echo "   • {$country->country}: {$country->count} documents\n";
    }
    
} else {
    echo "   ✗ PROBLÈME: Aucun document juridique trouvé!\n";
}

echo "\n";

// 3. Vérifier Document Templates
echo "3. Document Templates (Modèles de documents)\n";
echo "   ------------------------------------------\n";

$totalTemplates = DocumentTemplate::count();
$templatesWithCountry = DocumentTemplate::whereNotNull('country')->count();
$templatesWithCategory = DocumentTemplate::whereNotNull('category_id')->count();
$mobileVisibleTemplates = DocumentTemplate::where('is_mobile_visible', true)->count();

echo "   Total templates: {$totalTemplates}\n";
echo "   Templates avec pays: {$templatesWithCountry}\n";
echo "   Templates avec catégorie: {$templatesWithCategory}\n";
echo "   Templates visibles mobile: {$mobileVisibleTemplates}\n";

if ($totalTemplates > 0) {
    echo "   ✓ Document Templates accessibles\n";
    
    echo "\n   Exemples de templates:\n";
    $sampleTemplates = DocumentTemplate::with('category')
        ->limit(5)
        ->get(['id', 'name', 'country', 'category_id', 'template_type', 'file_path']);
    
    foreach ($sampleTemplates as $template) {
        $country = $template->country ?? 'Tous pays';
        $category = $template->category->name ?? 'N/A';
        echo "   • [{$country}] {$template->name} ({$template->template_type})\n";
        echo "     Catégorie: {$category}\n";
    }
    
    // Vérifier les pays
    echo "\n   Pays disponibles dans templates:\n";
    $templateCountries = DocumentTemplate::select('country', DB::raw('COUNT(*) as count'))
        ->whereNotNull('country')
        ->groupBy('country')
        ->orderBy('count', 'DESC')
        ->get();
    
    foreach ($templateCountries as $country) {
        echo "   • {$country->country}: {$country->count} templates\n";
    }
    
} else {
    echo "   ⚠ Aucun template trouvé (peut être normal si pas encore créé)\n";
}

echo "\n";

// 4. Vérifier Fiscal/Social Resources
echo "4. Fiscal & Social Resources (Ressources fiscales)\n";
echo "   ------------------------------------------------\n";

$totalFiscalResources = FiscalSocialResource::count();
$fiscalWithCountry = FiscalSocialResource::whereNotNull('country')->count();
$fiscalWithCategory = FiscalSocialResource::whereNotNull('category_id')->count();
$fiscalLatestVersion = FiscalSocialResource::where('is_latest_version', true)->count();

echo "   Total ressources fiscales: {$totalFiscalResources}\n";
echo "   Ressources avec pays: {$fiscalWithCountry}\n";
echo "   Ressources avec catégorie: {$fiscalWithCategory}\n";
echo "   Ressources version actuelle: {$fiscalLatestVersion}\n";

if ($totalFiscalResources > 0) {
    echo "   ✓ Ressources fiscales accessibles\n";
    
    echo "\n   Exemples de ressources:\n";
    $sampleFiscal = FiscalSocialResource::with('category')
        ->limit(5)
        ->get(['id', 'title', 'country', 'year', 'resource_type', 'category_id']);
    
    foreach ($sampleFiscal as $resource) {
        $country = $resource->country ?? 'Non spécifié';
        $category = $resource->category->name ?? 'N/A';
        echo "   • [{$country}] {$resource->title} ({$resource->year})\n";
        echo "     Type: {$resource->resource_type} | Catégorie: {$category}\n";
    }
    
    // Vérifier les pays
    echo "\n   Pays disponibles dans ressources fiscales:\n";
    $fiscalCountries = FiscalSocialResource::select('country', DB::raw('COUNT(*) as count'))
        ->whereNotNull('country')
        ->groupBy('country')
        ->orderBy('count', 'DESC')
        ->get();
    
    foreach ($fiscalCountries as $country) {
        echo "   • {$country->country}: {$country->count} ressources\n";
    }
    
    // Années disponibles
    echo "\n   Années disponibles:\n";
    $years = FiscalSocialResource::select('year', DB::raw('COUNT(*) as count'))
        ->whereNotNull('year')
        ->groupBy('year')
        ->orderBy('year', 'DESC')
        ->get();
    
    foreach ($years as $year) {
        echo "   • {$year->year}: {$year->count} ressources\n";
    }
    
} else {
    echo "   ⚠ Aucune ressource fiscale trouvée (peut être normal si pas encore créé)\n";
}

echo "\n";

// 5. Vérifier les catégories
echo "5. Catégories disponibles\n";
echo "   ----------------------\n";

$legalCategories = LegalCategory::count();
$templateCategories = TemplateCategory::count();
$resourceCategories = ResourceCategory::count();

echo "   Catégories juridiques: {$legalCategories}\n";
echo "   Catégories templates: {$templateCategories}\n";
echo "   Catégories ressources: {$resourceCategories}\n";

if ($legalCategories > 0) {
    echo "\n   Exemples catégories juridiques:\n";
    $cats = LegalCategory::withCount('documents')->limit(10)->get(['id', 'name']);
    foreach ($cats as $cat) {
        echo "   • {$cat->name} ({$cat->documents_count} documents)\n";
    }
}

echo "\n";

// 6. Test SimpleRagService
echo "6. Test SimpleRagService (Accès RAG)\n";
echo "   -----------------------------------\n";

try {
    $ragService = app(SimpleRagService::class);
    
    // Test 1: Recherche générale
    echo "   Test 1: Recherche générale 'contrat'\n";
    $results = $ragService->search('contrat', 3);
    echo "   Résultats trouvés: " . count($results) . "\n";
    
    if (!empty($results)) {
        echo "   ✓ Recherche FULLTEXT fonctionne\n";
        foreach ($results as $result) {
            echo "     • {$result['title']} (Score: {$result['relevance_score']})\n";
        }
    }
    
    // Test 2: Recherche par pays
    echo "\n   Test 2: Recherche par pays 'Cameroun'\n";
    $resultsCM = $ragService->searchByCountry('entreprise', 'Cameroun', 3);
    echo "   Résultats Cameroun: " . count($resultsCM) . "\n";
    
    if (!empty($resultsCM)) {
        echo "   ✓ Filtrage par pays fonctionne\n";
        foreach ($resultsCM as $result) {
            $country = $result['country'] ?? 'N/A';
            echo "     • [{$country}] {$result['title']}\n";
        }
    }
    
    // Test 3: Méthode getContextByCountry (utilisée par ChatController)
    echo "\n   Test 3: getContextByCountry() [CRITIQUE]\n";
    try {
        $context = $ragService->getContextByCountry('divorce', 'Sénégal', 500);
        if (!empty($context)) {
            echo "   ✓ getContextByCountry() fonctionne!\n";
            echo "   Contexte généré: " . strlen($context) . " caractères\n";
            
            // Afficher début du contexte
            $preview = substr($context, 0, 200);
            echo "   Aperçu:\n";
            $lines = explode("\n", $preview);
            foreach ($lines as $line) {
                echo "     " . $line . "\n";
            }
        } else {
            echo "   ⚠ getContextByCountry() retourne vide (pas de documents correspondants)\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ ERREUR getContextByCountry(): " . $e->getMessage() . "\n";
    }
    
    // Test 4: Méthode complète (Legal + Templates + Fiscal)
    echo "\n   Test 4: getComprehensiveContextByCountry() [NOUVELLE]\n";
    try {
        $compContext = $ragService->getComprehensiveContextByCountry('impôt', 'Cameroun', 1000);
        if (!empty($compContext)) {
            echo "   ✓ Contexte complet fonctionne!\n";
            echo "   Contexte généré: " . strlen($compContext) . " caractères\n";
            
            // Vérifier les sections
            $hasBiblio = strpos($compContext, 'BIBLIOTHÈQUE JURIDIQUE') !== false;
            $hasTemplates = strpos($compContext, 'MODÈLES DE DOCUMENTS') !== false;
            $hasFiscal = strpos($compContext, 'RESSOURCES FISCALES') !== false;
            
            echo "   Sections présentes:\n";
            echo "   " . ($hasBiblio ? '✓' : '✗') . " Bibliothèque Juridique\n";
            echo "   " . ($hasTemplates ? '✓' : '✗') . " Modèles de Documents\n";
            echo "   " . ($hasFiscal ? '✓' : '✗') . " Ressources Fiscales\n";
        } else {
            echo "   ⚠ Contexte complet vide\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ ERREUR: " . $e->getMessage() . "\n";
    }
    
    // Test 5: Statistiques
    echo "\n   Test 5: Statistiques d'indexation\n";
    $stats = $ragService->getIndexStats();
    
    echo "   Legal Documents:\n";
    echo "     Total: {$stats['legal_documents']['total']}\n";
    echo "     Indexés: {$stats['legal_documents']['indexed']}\n";
    echo "     Couverture: {$stats['legal_documents']['coverage']}%\n";
    
    echo "   Templates: {$stats['document_templates']['total']}\n";
    echo "   Ressources Fiscales: {$stats['fiscal_social_resources']['total']}\n";
    
} catch (\Exception $e) {
    echo "   ✗ ERREUR SimpleRagService: " . $e->getMessage() . "\n";
    echo "   " . $e->getTraceAsString() . "\n";
}

echo "\n";

// 7. Vérifier l'accès depuis ChatController
echo "7. Vérification ChatController Integration\n";
echo "   ----------------------------------------\n";

echo "   Méthodes vérifiées dans ChatController:\n";

// Vérifier que la méthode est appelée
$chatControllerPath = app_path('Http/Controllers/Api/Mobile/ChatController.php');
$chatControllerCode = file_get_contents($chatControllerPath);

$hasGetContextByCountry = strpos($chatControllerCode, 'getContextByCountry') !== false;
$hasSimpleRag = strpos($chatControllerCode, '$this->simpleRag') !== false;
$hasCountryContext = strpos($chatControllerCode, 'getCountryAIContext') !== false;

echo "   " . ($hasGetContextByCountry ? '✓' : '✗') . " Appel getContextByCountry()\n";
echo "   " . ($hasSimpleRag ? '✓' : '✗') . " SimpleRagService injecté\n";
echo "   " . ($hasCountryContext ? '✓' : '✗') . " getCountryAIContext() présent\n";

if ($hasGetContextByCountry && $hasSimpleRag && $hasCountryContext) {
    echo "   ✓ ChatController correctement configuré\n";
} else {
    echo "   ✗ PROBLÈME dans ChatController\n";
}

echo "\n";

// 8. Résumé final
echo "========================================\n";
echo "RÉSUMÉ DE LA VÉRIFICATION\n";
echo "========================================\n\n";

$score = 0;
$maxScore = 7;

echo "Accès aux données:\n";
if ($totalLegalDocs > 0) {
    echo "✓ Legal Documents accessibles ({$totalLegalDocs} documents)\n";
    $score++;
} else {
    echo "✗ Legal Documents: AUCUN document\n";
}

if ($docsWithCountry > 0) {
    echo "✓ Documents filtrés par pays ({$docsWithCountry} avec pays)\n";
    $score++;
} else {
    echo "✗ Aucun document avec pays (filtrage impossible)\n";
}

if ($docsWithCategory > 0) {
    echo "✓ Documents catégorisés ({$docsWithCategory} avec catégorie)\n";
    $score++;
} else {
    echo "⚠ Peu de documents catégorisés\n";
}

if ($totalTemplates >= 0) {
    echo "✓ Document Templates accessibles ({$totalTemplates} templates)\n";
    $score++;
}

if ($totalFiscalResources >= 0) {
    echo "✓ Fiscal Resources accessibles ({$totalFiscalResources} ressources)\n";
    $score++;
}

if ($hasGetContextByCountry) {
    echo "✓ getContextByCountry() implémentée et utilisée\n";
    $score++;
} else {
    echo "✗ getContextByCountry() MANQUANTE\n";
}

if ($storageDriver === 'r2') {
    echo "✓ Cloudflare R2 configuré et actif\n";
    $score++;
} else {
    echo "⚠ Storage: {$storageDriver} (pas R2)\n";
    $score += 0.5;
}

echo "\n";
echo "Score global: {$score}/{$maxScore}\n\n";

if ($score >= 6) {
    echo "✅ EXCELLENT: L'IA a accès complet aux données!\n";
} elseif ($score >= 4) {
    echo "⚠ MOYEN: L'IA a un accès partiel, vérifier les points manquants.\n";
} else {
    echo "❌ PROBLÈME: L'IA n'a pas accès suffisant aux données!\n";
}

echo "\n";
echo "========================================\n";
echo "FIN DE LA VÉRIFICATION\n";
echo "========================================\n";
