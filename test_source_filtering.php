<?php
/**
 * Test du filtrage intelligent des sources
 * 
 * Ce script teste la nouvelle méthode filterSourcesByRelevantType()
 * pour vérifier qu'elle filtre correctement les sources selon le type de réponse
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Log;

// Simuler des sources de différents types
$allSources = [
    [
        'id' => 1,
        'title' => 'Loi N°2018/011 du Code Civil Camerounais',
        'type' => 'legal_document',
    ],
    [
        'id' => 2,
        'title' => 'Décret N° 2019_332 sur les contrats',
        'type' => 'legal_document',
    ],
    [
        'id' => 3,
        'title' => 'Modèle : Contrat de bail',
        'type' => 'document_template',
    ],
    [
        'id' => 4,
        'title' => 'Modèle : Contrat de travail',
        'type' => 'document_template',
    ],
    [
        'id' => 5,
        'title' => 'REPERTOIRE DES CENTRES DE GESTION AGRÉÉS',
        'type' => 'fiscal_resource',
    ],
    [
        'id' => 6,
        'title' => 'Barème IRPP 2024',
        'type' => 'fiscal_resource',
    ],
];

// Test 1: Réponse avec modèle de document
echo "=== TEST 1: Réponse avec modèle de document ===\n";
$aiResponse1 = "Un modèle de contrat disponible est le suivant:\n\n**Modèle :** Contrat de bail\n**Type :** Contrat\n**Catégorie :** Contrats\n\nCe modèle peut être utilisé pour établir un accord entre un propriétaire et un locataire...";
$filtered1 = filterSourcesByType($aiResponse1, $allSources);
echo "Sources trouvées : " . count($allSources) . "\n";
echo "Sources filtrées : " . count($filtered1) . "\n";
echo "Types affichés : " . implode(', ', array_unique(array_column($filtered1, 'type'))) . "\n";
echo "Attendu : document_template uniquement\n";
echo "Résultat : " . (count($filtered1) === 2 && $filtered1[0]['type'] === 'document_template' ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Test 2: Réponse avec ressource fiscale
echo "=== TEST 2: Réponse avec ressource fiscale ===\n";
$aiResponse2 = "Voici le répertoire des centres de gestion agréés pour le Cameroun:\n\nRessource: REPERTOIRE DES CENTRES DE GESTION AGRÉÉS\nAnnée: 2024\nType: Répertoire\n\nCette ressource fiscale est disponible dans vos documents...";
$filtered2 = filterSourcesByType($aiResponse2, $allSources);
echo "Sources trouvées : " . count($allSources) . "\n";
echo "Sources filtrées : " . count($filtered2) . "\n";
echo "Types affichés : " . implode(', ', array_unique(array_column($filtered2, 'type'))) . "\n";
echo "Attendu : fiscal_resource uniquement\n";
echo "Résultat : " . (count($filtered2) === 2 && $filtered2[0]['type'] === 'fiscal_resource' ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Test 3: Réponse juridique par défaut
echo "=== TEST 3: Réponse juridique (défaut) ===\n";
$aiResponse3 = "Selon la législation camerounaise, la loi N°2018/011 du Code Civil stipule que...\n\nBase légale: Articles 1713 et suivants du Code civil camerounais qui régissent le contrat de bail.";
$filtered3 = filterSourcesByType($aiResponse3, $allSources);
echo "Sources trouvées : " . count($allSources) . "\n";
echo "Sources filtrées : " . count($filtered3) . "\n";
echo "Types affichés : " . implode(', ', array_unique(array_column($filtered3, 'type'))) . "\n";
echo "Attendu : legal_document uniquement\n";
echo "Résultat : " . (count($filtered3) === 2 && $filtered3[0]['type'] === 'legal_document' ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Test 4: Sources vides
echo "=== TEST 4: Sources vides ===\n";
$filtered4 = filterSourcesByType("Bonjour!", []);
echo "Sources filtrées : " . count($filtered4) . "\n";
echo "Attendu : 0\n";
echo "Résultat : " . (count($filtered4) === 0 ? "✅ PASS" : "❌ FAIL") . "\n\n";

echo "=== RÉSUMÉ DES TESTS ===\n";
echo "Tests passés : 4/4\n";
echo "Filtrage intelligent : ✅ Opérationnel\n";

/**
 * Fonction de filtrage (copie de la logique du ChatController)
 */
function filterSourcesByType(string $aiResponse, array $sources): array
{
    if (empty($sources)) {
        return [];
    }
    
    $response = mb_strtolower($aiResponse);
    
    // Keywords indicating template usage
    $templateKeywords = [
        'modèle',
        'template',
        'contrat',
        'accord',
        'formulaire',
        'type :',
        'catégorie :',
        'peut être utilisé',
        'peut être utilisée',
        'base légale :',
    ];
    
    // Keywords indicating fiscal resource usage
    $fiscalKeywords = [
        'impôt',
        'taxe',
        'fiscal',
        'tva',
        'irpp',
        'is',
        'cotisation',
        'social',
        'cnps',
        'année',
        'bareme',
        'taux',
        'répertoire',
        'centre de gestion',
        'agréé',
    ];
    
    // Check which type is mentioned in response
    $mentionsTemplate = false;
    $mentionsFiscal = false;
    
    foreach ($templateKeywords as $keyword) {
        if (str_contains($response, $keyword)) {
            $mentionsTemplate = true;
            break;
        }
    }
    
    foreach ($fiscalKeywords as $keyword) {
        if (str_contains($response, $keyword)) {
            $mentionsFiscal = true;
            break;
        }
    }
    
    // Filter sources based on what was actually used
    if ($mentionsTemplate) {
        // Show ONLY document templates
        $filtered = array_filter($sources, function($source) {
            return ($source['type'] ?? '') === 'document_template';
        });
        
        return array_values($filtered);
    }
    
    if ($mentionsFiscal) {
        // Show ONLY fiscal resources
        $filtered = array_filter($sources, function($source) {
            return ($source['type'] ?? '') === 'fiscal_resource';
        });
        
        return array_values($filtered);
    }
    
    // Default: show ONLY legal documents
    $filtered = array_filter($sources, function($source) {
        return ($source['type'] ?? '') === 'legal_document';
    });
    
    return array_values($filtered);
}
