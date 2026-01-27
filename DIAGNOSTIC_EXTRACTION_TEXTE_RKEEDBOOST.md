# 🔧 DIAGNOSTIC : Extraction de Texte Échouée

**Date :** 2025-01-14  
**Document :** rkeedboost.pdf (ID: 12)  
**Problème :** `extracted_text` = NULL, impossible pour le chat de lire le contenu

---

## 🔍 Cause Probable

Le document **rkeedboost.pdf** est probablement un **PDF scanné** (image-only) sans couche de texte sélectionnable.

### Preuve dans la BDD
```
- extracted_text: NULL
- extracted_text_length: 0
- processing_status: completed
- processing_error: "No text content found. This may be an image, unsup..."
```

---

## 🧪 Diagnostic à Exécuter

### Étape 1 : Test Manuel d'Extraction
Exécutez : **[test_extract_rkeedboost.bat](test_extract_rkeedboost.bat)**

Ce script va :
1. ✅ Vérifier si le fichier existe sur Cloudflare R2
2. 📥 Télécharger le PDF
3. 🔧 Tenter l'extraction avec PdfParser
4. 📊 Afficher les métadonnées du PDF
5. 💾 Mettre à jour la BDD si extraction réussie

---

## 💡 Solutions Possibles

### Solution 1 : PDF avec Texte Natif (Simple)
Si le test d'extraction **réussit**, le problème vient du job qui n'a pas pu télécharger ou parser le fichier.

**Action :** Réexécuter le job manuellement :
```bash
php artisan queue:work --once
```

---

### Solution 2 : PDF Scanné (Nécessite OCR)

Si le test montre **"NO TEXT extracted"**, c'est un PDF scanné.

#### Option A : Tesseract OCR (Gratuit, Open Source)
1. Installer Tesseract : https://github.com/tesseract-ocr/tesseract
2. Ajouter au code PHP :
```php
// Dans ProcessDocumentForRAG.php
use thiagoalessio\TesseractOCR\TesseractOCR;

// Si PDF sans texte, essayer OCR
if (empty($text) && $mimeType === 'application/pdf') {
    Log::info("PDF has no text, attempting OCR...");
    
    // Convertir PDF en images avec Imagick
    $imagick = new \Imagick($tempPath);
    $imagick->setResolution(300, 300);
    $imagick->setImageFormat('png');
    
    $ocrText = '';
    foreach ($imagick as $index => $page) {
        $imagePath = $tempPath . "_page_{$index}.png";
        $page->writeImage($imagePath);
        
        // OCR sur chaque page
        $pageText = (new TesseractOCR($imagePath))
            ->lang('fra')
            ->run();
        $ocrText .= $pageText . "\n\n";
        
        unlink($imagePath);
    }
    
    $text = $ocrText;
}
```

#### Option B : Google Cloud Vision API (Payant, Plus Précis)
```php
use Google\Cloud\Vision\V1\ImageAnnotatorClient;

$imageAnnotator = new ImageAnnotatorClient();
$response = $imageAnnotator->documentTextDetection(file_get_contents($imagePath));
$text = $response->getFullTextAnnotation()->getText();
```

---

### Solution 3 : Demander à l'Utilisateur de Re-upload

Si l'utilisateur a le PDF original en format texte (non scanné), lui demander de :
1. Ouvrir le PDF dans Adobe Acrobat
2. Exporter en PDF avec texte sélectionnable
3. Re-upload le document

---

## 🚀 Solution Rapide (Sans OCR)

Si vous ne pouvez pas installer OCR maintenant, **solution de contournement** :

### Permettre la Saisie Manuelle
Ajouter une fonctionnalité dans l'app mobile pour que l'utilisateur puisse :
1. Voir qu'un document n'a pas de texte extrait
2. Saisir manuellement le contenu ou un résumé
3. Le système utilise ce texte pour le RAG

---

## 📝 Prochaines Actions

1. **IMMÉDIAT** : Exécuter [test_extract_rkeedboost.bat](test_extract_rkeedboost.bat)
2. **Si texte extrait** : Problème résolu, tester le chat
3. **Si aucun texte** : Choisir entre OCR (Solution 2) ou re-upload (Solution 3)

---

## 🔗 Fichiers Créés

- `test_extract_rkeedboost.php` - Script de diagnostic
- `test_extract_rkeedboost.bat` - Lanceur Windows
- `check_rkeedboost_document.sql` - Requête SQL de vérification

---

**Note :** Ma correction de priorité des documents fonctionne **UNIQUEMENT si le document a du texte extrait**. C'est pourquoi le chat ne peut toujours pas lire le document.
