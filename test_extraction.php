<?php
/**
 * SCRIPT DE TEST - Vérifier que l'extraction de documents fonctionne
 * 
 * Exécuter sur le serveur :
 * cd /home/threesixty/yyy/Dossy/legalnew
 * php test_extraction.php
 */

require __DIR__ . '/vendor/autoload.php';

// Charger l'app sans bootstrapper tous les providers (évite erreurs sur Barryvdh)
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Services\DocumentContentExtractor;
use App\Models\SubmittedDocument;
use Illuminate\Support\Facades\DB;

echo "🔍 TEST D'EXTRACTION DE DOCUMENTS\n";
echo "==================================\n\n";

// Test 1: Vérifier les packages installés
echo "1️⃣ VÉRIFICATION DES PACKAGES INSTALLÉS\n";
echo "----------------------------------------\n";

$packages = [
    'Smalot\PdfParser\Parser' => 'smalot/pdfparser',
    'PhpOffice\PhpWord\IOFactory' => 'phpoffice/phpword',
    'PhpOffice\PhpSpreadsheet\IOFactory' => 'phpoffice/phpspreadsheet',
];

foreach ($packages as $class => $package) {
    $exists = class_exists($class);
    $status = $exists ? '✅ INSTALLÉ' : '❌ MANQUANT';
    echo "$package : $status\n";
}

// Test 2: Vérifier les commandes système
echo "\n2️⃣ VÉRIFICATION DES COMMANDES SYSTÈME LINUX\n";
echo "-------------------------------------------\n";

$commands = ['pdftotext', 'libreoffice', 'unoconv'];
foreach ($commands as $cmd) {
    $exists = shell_exec("which $cmd 2>/dev/null") ? true : false;
    $status = $exists ? '✅ DISPONIBLE' : '❌ MANQUANT';
    echo "$cmd : $status\n";
}

// Test 3: Lister les derniers documents uploadés
echo "\n3️⃣ DERNIERS DOCUMENTS UPLOADÉS\n";
echo "--------------------------------\n";

try {
    $docs = SubmittedDocument::latest()->limit(5)->get();
    
    if ($docs->isEmpty()) {
        echo "❌ Aucun document trouvé en base de données\n";
    } else {
        foreach ($docs as $doc) {
            echo "\nDocument ID {$doc->id} :\n";
            echo "  - Fichier : {$doc->file_name}\n";
            echo "  - Type : {$doc->mime_type}\n";
            echo "  - Taille : " . number_format($doc->file_size / 1024, 2) . " KB\n";
            echo "  - Chemin : {$doc->storage_path}\n";
            echo "  - Texte extrait : " . (strlen($doc->extracted_text ?? '') > 0 ? '✅ OUI (' . strlen($doc->extracted_text) . ' chars)' : '❌ NON') . "\n";
            echo "  - Date upload : {$doc->created_at->format('Y-m-d H:i:s')}\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Erreur lecture BDD : {$e->getMessage()}\n";
}

// Test 4: Tester l'extraction sur un document
echo "\n4️⃣ TEST D'EXTRACTION SUR UN DOCUMENT\n";
echo "------------------------------------\n";

try {
    $doc = SubmittedDocument::latest()->first();
    
    if (!$doc) {
        echo "❌ Aucun document pour tester\n";
    } else {
        echo "Test sur : {$doc->file_name}\n";
        
        $extractor = new DocumentContentExtractor();
        echo "🚀 Lancement de l'extraction...\n";
        
        $text = $extractor->extractForChat($doc);
        
        if (empty($text)) {
            echo "❌ EXTRACTION ÉCHOUÉE (texte vide)\n";
        } else if (str_starts_with($text, '⚠️')) {
            echo "⚠️  ERREUR D'EXTRACTION :\n";
            echo $text . "\n";
        } else {
            echo "✅ EXTRACTION RÉUSSIE\n";
            echo "Longueur du texte : " . strlen($text) . " caractères\n";
            echo "Aperçu (premiers 200 caractères) :\n";
            echo substr($text, 0, 200) . "...\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Erreur test extraction : {$e->getMessage()}\n";
    echo "Stack trace : {$e->getTraceAsString()}\n";
}

echo "\n✅ FIN DU TEST\n";
