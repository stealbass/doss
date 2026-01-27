<?php
/**
 * Diagnostic: Vérifier le code de filtrage des sources
 * Exécuter via: php /chemin/vers/diagnostic_sources.php
 */

$chatControllerPath = '/home/threesixty/yyy/Dossy/app/Http/Controllers/Api/Mobile/ChatController.php';
$advancedRagPath = '/home/threesixty/yyy/Dossy/app/Services/AdvancedRagService.php';

echo "================================================\n";
echo "DIAGNOSTIC - Filtrage des sources\n";
echo "================================================\n\n";

// 1. Vérifier ChatController
echo "[1] Vérification ChatController.php\n";
echo "Chemin: $chatControllerPath\n";

if (!file_exists($chatControllerPath)) {
    echo "❌ FICHIER NON TROUVÉ\n\n";
} else {
    $content = file_get_contents($chatControllerPath);
    
    // Chercher la fonction isType
    if (strpos($content, '$isType = function') !== false) {
        echo "✅ Fonction isType TROUVÉE\n";
    } else {
        echo "❌ Fonction isType NON TROUVÉE - Le code n'a pas été déployé!\n";
    }
    
    // Chercher le filtre fiscal
    if (preg_match('/fiscal_resource.*fiscal/', $content)) {
        echo "✅ Filtre fiscal CORRECT\n";
    } else {
        echo "❌ Filtre fiscal INCORRECT\n";
    }
    
    // Chercher le filtre legal
    if (preg_match('/legal_document.*legal/', $content)) {
        echo "✅ Filtre legal CORRECT\n";
    } else {
        echo "❌ Filtre legal INCORRECT\n";
    }
    
    echo "\n";
}

// 2. Vérifier AdvancedRagService
echo "[2] Vérification AdvancedRagService.php\n";
echo "Chemin: $advancedRagPath\n";

if (!file_exists($advancedRagPath)) {
    echo "❌ FICHIER NON TROUVÉ\n\n";
} else {
    $content = file_get_contents($advancedRagPath);
    
    // Chercher le typeMap
    if (strpos($content, "'legal' => 'legal_document'") !== false) {
        echo "✅ TypeMap legal TROUVÉ\n";
    } else {
        echo "❌ TypeMap legal NON TROUVÉ - Le code n'a pas été déployé!\n";
    }
    
    if (strpos($content, "'fiscal' => 'fiscal_resource'") !== false) {
        echo "✅ TypeMap fiscal TROUVÉ\n";
    } else {
        echo "❌ TypeMap fiscal NON TROUVÉ\n";
    }
    
    echo "\n";
}

// 3. Actions recommandées
echo "[3] Actions recommandées\n";
echo "================================================\n\n";

echo "Si les fichiers ne sont pas à jour:\n\n";
echo "sudo bash /home/threesixty/yyy/Dossy/redeploy_fix_sources.sh\n\n";

echo "Puis tester avec:\n\n";
echo "tail -f /home/threesixty/yyy/Dossy/storage/logs/laravel.log | grep 'Filtering sources'\n\n";

echo "================================================\n";
echo "Diagnostic terminé\n";
echo "================================================\n";
