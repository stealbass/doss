<?php
/**
 * Script de test pour vérifier la détection des questions légales
 * Place-le dans laravel-app/ root et exécute : php test_question_detection.php
 */

// Charger Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Importer le controller
$controller = new \App\Http\Controllers\Api\Mobile\ChatController();

// Questions de test
$testQuestions = [
    // Légal
    "Quels sont les taux de TVA au Cameroun?" => true,
    "Comment rédiger un contrat de travail?" => true,
    "Quelle est la loi sur le travail?" => true,
    "Quel article du code civil?" => true,
    "Où trouver les décrets d'application?" => true,
    
    // Fiscal
    "Comment calculer les cotisations CNPS?" => true,
    "Quels sont les impôts sur le revenu?" => true,
    "Comment remplir une déclaration fiscale?" => true,
    "TVA au Cameroun, c'est combien?" => true,
    
    // Template
    "Avez-vous un modèle de contrat?" => true,
    "Où trouver un formulaire de bail?" => true,
    "Donnez-moi un template de document" => true,
    
    // Général (pas besoin de RAG)
    "Salut, comment vas-tu?" => false,
    "Ça va bien?" => false,
    "Quelle heure est-il?" => false,
    "Qui es-tu?" => false,
    "C'est quoi ta spécialité?" => false,
    
    // Ambigu (devrait retourner true)
    "Comment puis-je?" => false, // Pas assez spécifique
    "Peux-tu m'aider?" => false, // Général
];

echo "=== TEST DÉTECTION DE QUESTIONS ===\n\n";

$correctCount = 0;
$failCount = 0;

foreach ($testQuestions as $question => $expectedNeedsRag) {
    // Utiliser la méthode privée via réflexion
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('questionNeedsDocuments');
    $method->setAccessible(true);
    
    $result = $method->invoke($controller, $question);
    $expected = $expectedNeedsRag ? "OUI" : "NON";
    $actual = $result ? "OUI" : "NON";
    $status = ($result === $expectedNeedsRag) ? "✓" : "✗";
    
    echo "$status Question: \"$question\"\n";
    echo "   Attendu: $expected | Obtenu: $actual\n\n";
    
    if ($result === $expectedNeedsRag) {
        $correctCount++;
    } else {
        $failCount++;
    }
}

echo "=== RÉSULTATS ===\n";
echo "Correct: $correctCount / " . count($testQuestions) . "\n";
echo "Échoué: $failCount\n";
echo "Taux de succès: " . round(($correctCount / count($testQuestions)) * 100) . "%\n";
