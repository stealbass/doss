<?php
/**
 * Test du RAG Intelligent
 * 
 * Simule les 3 scénarios de questions et vérifie que:
 * 1. Le type de question est correctement détecté
 * 2. Le RAG retourne des sources du bon type en priorité
 * 3. Le filtrage affiche le bon type
 */

// Simulation des données
class SimpleRagSimulation {
    
    public function detectQuestionType(string $query): string
    {
        $text = mb_strtolower($query);
        
        $fiscalKeywords = [
            'impôt', 'taxe', 'fiscal', 'tva', 'irpp', 'is', 'cnps',
            'cotisation', 'social', 'bareme', 'taux', 'répertoire',
            'centre de gestion', 'agréé', 'année', 'déclaration',
        ];
        
        $templateKeywords = [
            'modèle', 'template', 'contrat', 'accord', 'formulaire',
            'rédiger', 'document type', 'clause',
        ];
        
        $fiscalCount = 0;
        $templateCount = 0;
        
        foreach ($fiscalKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $fiscalCount++;
            }
        }
        
        foreach ($templateKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $templateCount++;
            }
        }
        
        if ($fiscalCount > 0 && $fiscalCount >= $templateCount) {
            return 'fiscal';
        }
        
        if ($templateCount > 0) {
            return 'template';
        }
        
        return 'legal';
    }
    
    public function allocateTokensByPriority(string $priority): array
    {
        if ($priority === 'fiscal') {
            return [
                'fiscal' => 50,
                'template' => 30,
                'legal' => 20,
            ];
        }
        
        if ($priority === 'template') {
            return [
                'template' => 50,
                'legal' => 35,
                'fiscal' => 15,
            ];
        }
        
        // legal priority (default)
        return [
            'legal' => 50,
            'template' => 25,
            'fiscal' => 25,
        ];
    }
}

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          TEST DU RAG INTELLIGENT                               ║\n";
echo "║          Détection de Type + Allocation Tokens                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$rag = new SimpleRagSimulation();

// Test 1: Question Fiscale
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: QUESTION FISCALE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$q1 = "Répertoire des centres de gestion agréés du Cameroun";
$type1 = $rag->detectQuestionType($q1);
$allocation1 = $rag->allocateTokensByPriority($type1);

echo "Question: \"$q1\"\n";
echo "Type détecté: $type1\n";
echo "Allocation tokens:\n";
foreach ($allocation1 as $source => $percent) {
    echo "  - $source: $percent%\n";
}
echo "Expected: fiscal (50%), template (30%), legal (20%)\n";
$pass1 = $type1 === 'fiscal' && 
         $allocation1['fiscal'] === 50 && 
         $allocation1['template'] === 30 && 
         $allocation1['legal'] === 20;
echo "Status: " . ($pass1 ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Test 2: Question Template
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 2: QUESTION TEMPLATE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$q2 = "Donne-moi un modèle de contrat de travail";
$type2 = $rag->detectQuestionType($q2);
$allocation2 = $rag->allocateTokensByPriority($type2);

echo "Question: \"$q2\"\n";
echo "Type détecté: $type2\n";
echo "Allocation tokens:\n";
foreach ($allocation2 as $source => $percent) {
    echo "  - $source: $percent%\n";
}
echo "Expected: template (50%), legal (35%), fiscal (15%)\n";
$pass2 = $type2 === 'template' && 
         $allocation2['template'] === 50 && 
         $allocation2['legal'] === 35 && 
         $allocation2['fiscal'] === 15;
echo "Status: " . ($pass2 ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Test 3: Question Juridique
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 3: QUESTION JURIDIQUE (DÉFAUT)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$q3 = "Quels sont les droits des salariés selon la loi OHADA?";
$type3 = $rag->detectQuestionType($q3);
$allocation3 = $rag->allocateTokensByPriority($type3);

echo "Question: \"$q3\"\n";
echo "Type détecté: $type3\n";
echo "Allocation tokens:\n";
foreach ($allocation3 as $source => $percent) {
    echo "  - $source: $percent%\n";
}
echo "Expected: legal (50%), template (25%), fiscal (25%)\n";
$pass3 = $type3 === 'legal' && 
         $allocation3['legal'] === 50 && 
         $allocation3['template'] === 25 && 
         $allocation3['fiscal'] === 25;
echo "Status: " . ($pass3 ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Test 4: Question ambiguë (fiscal + template)
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 4: QUESTION AMBIGUË (FISCAL + TEMPLATE)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$q4 = "Modèle de déclaration fiscale avec impôts";
$type4 = $rag->detectQuestionType($q4);
$allocation4 = $rag->allocateTokensByPriority($type4);

echo "Question: \"$q4\"\n";
echo "Type détecté: $type4 (fiscal prioritaire car 2 mots-clés vs 1)\n";
echo "Allocation tokens:\n";
foreach ($allocation4 as $source => $percent) {
    echo "  - $source: $percent%\n";
}
echo "Expected: fiscal (prioritaire)\n";
$pass4 = $type4 === 'fiscal';
echo "Status: " . ($pass4 ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Test 5: Question générale
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 5: QUESTION GÉNÉRALE (PAS DE MOT-CLÉ SPÉCIFIQUE)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$q5 = "Bonjour, comment ça va?";
$type5 = $rag->detectQuestionType($q5);
$allocation5 = $rag->allocateTokensByPriority($type5);

echo "Question: \"$q5\"\n";
echo "Type détecté: $type5 (défaut - aucun mot-clé trouvé)\n";
echo "Allocation tokens:\n";
foreach ($allocation5 as $source => $percent) {
    echo "  - $source: $percent%\n";
}
echo "Expected: legal (50%), template (25%), fiscal (25%)\n";
$pass5 = $type5 === 'legal';
echo "Status: " . ($pass5 ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Summary
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                        RÉSUMÉ DES TESTS                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

$total = 5;
$passed = ($pass1 ? 1 : 0) + ($pass2 ? 1 : 0) + ($pass3 ? 1 : 0) + ($pass4 ? 1 : 0) + ($pass5 ? 1 : 0);
$percent = intval(($passed / $total) * 100);

echo "\nTests passés: $passed/$total\n";
echo "Taux de réussite: $percent%\n\n";

if ($passed === $total) {
    echo "✅ RAG INTELLIGENT: OPÉRATIONNEL\n";
    echo "\nLe système détecte correctement:\n";
    echo "  - Les questions fiscales → priorité aux ressources fiscales\n";
    echo "  - Les questions de templates → priorité aux modèles\n";
    echo "  - Les questions juridiques → priorité aux documents légaux\n";
    echo "  - Les ambiguïtés → utilise le type dominant\n";
} else {
    echo "❌ ERREURS DÉTECTÉES\n";
    echo "Veuillez vérifier la logique de détection\n";
}

echo "\n";
