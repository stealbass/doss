# 🔧 GUIDE DE DÉPANNAGE - EXTRACTION DE DOCUMENTS

## ❌ Problème : L'IA n'arrive pas à lire les documents (PDF, Word, Excel)

### 🔍 Diagnostic

Le problème vient probablement d'une **extraction de contenu qui échoue silencieusement**.

### 1️⃣ Exécuter le test de diagnostic

**Sur le serveur**, exécute le script de test :

```bash
cd /home/threesixty/yyy/Dossy/legalnew
php test_extraction.php
```

Ce script te montrera :
- ✅/❌ Les packages PHP installés (Smalot, PhpOffice)
- ✅/❌ Les commandes Linux disponibles (pdftotext, libreoffice)
- 📋 Les derniers documents uploadés
- 🚀 Test d'extraction sur un vrai document

### 2️⃣ Vérifier les logs en temps réel

```bash
# Terminal 1 : Voir les logs en direct
tail -f /home/threesixty/yyy/Dossy/legalnew/storage/logs/laravel.log

# Terminal 2 : Envoyer un message dans le chat avec un document sélectionné
# (Depuis l'app mobile ou test API)
```

**Cherche ces messages dans les logs :**

✅ Succès :
```
[timestamp] production.INFO: 🔍 Extraction à la volée du document 13
[timestamp] production.INFO: ✅ Texte extrait avec succès
[timestamp] production.INFO: 💾 Texte sauvegardé en BDD
```

❌ Échec :
```
[timestamp] production.WARNING: 🔍 Extraction à la volée du document 13
[timestamp] production.WARNING: ⚠️ PDF extraction non disponible - packages manquants ou pdftotext non installé
```

### 3️⃣ Solutions selon les logs

#### **Solution 1 : Packages PHP manquants**

Si les logs montrent `packages manquants` :

```bash
cd /home/threesixty/yyy/Dossy/legalnew

# Installer les packages manquants
composer require smalot/pdfparser:^2.10 phpoffice/phpword:^1.2 phpoffice/phpspreadsheet:^2.0

# Vérifier l'installation
composer show smalot/pdfparser phpoffice/phpword phpoffice/phpspreadsheet
```

#### **Solution 2 : Commandes système Linux manquantes**

Si tu vois `pdftotext non installé` et `libreoffice non disponible` :

```bash
# Sur serveur Linux/AlwaysData :
# Installer pdftotext (pour PDF)
apt-get install poppler-utils

# Installer libreoffice (pour Word/Excel/PowerPoint)
apt-get install libreoffice

# Vérifier
which pdftotext
which libreoffice
```

⚠️ **Note pour AlwaysData** : Il faut une offre Premium pour installer les packages système. 
Contacte le support AlwaysData ou utilise un serveur externe.

#### **Solution 3 : R2 Storage mal configurée**

Si le document existe mais ne peut pas être téléchargé de R2 :

```bash
# Vérifier les credentials R2 dans .env
grep R2 /home/threesixty/yyy/Dossy/legalnew/.env

# Les variables doivent être présentes :
R2_KEY=xxx
R2_SECRET=xxx
R2_BUCKET=xxx
R2_ENDPOINT=https://xxx.r2.cloudflarestorage.com
R2_REGION=auto
R2_URL=https://xxx.r2.cloudflarestorage.com
```

### 4️⃣ Tester l'extraction directement

```bash
cd /home/threesixty/yyy/Dossy/legalnew
php artisan tinker

# Importer le service
use App\Services\DocumentContentExtractor;
use App\Models\SubmittedDocument;

# Charger un document
$doc = SubmittedDocument::find(13); // Remplace 13 par l'ID de ton document

# Tester l'extraction
$extractor = new DocumentContentExtractor();
$text = $extractor->extractForChat($doc);

# Voir le résultat
echo strlen($text) . " caractères extraits\n";
echo substr($text, 0, 100) . "...\n";

# Quitter
exit
```

### 5️⃣ Forcer une réextraction

Si un document n'a pas d'`extracted_text` en base de données :

```bash
cd /home/threesixty/yyy/Dossy/legalnew
php artisan tinker

use App\Services\DocumentContentExtractor;
use App\Models\SubmittedDocument;

$doc = SubmittedDocument::find(13);
$extractor = new DocumentContentExtractor();
$text = $extractor->extractForChat($doc);

# Le texte est maintenant sauvegardé en BDD
# Vérifier
$doc->refresh();
echo $doc->extracted_text; // Doit montrer le texte extrait
```

---

## 📋 Checklist de vérification

- [ ] Les packages PHP sont installés : `composer show`
- [ ] Les commandes Linux sont disponibles : `which pdftotext` et `which libreoffice`
- [ ] Les credentials R2 sont correctes dans `.env`
- [ ] Le fichier du document existe dans R2
- [ ] Les logs montrent l'extraction en cours : `tail -f storage/logs/laravel.log`
- [ ] La BDD a un `extracted_text` non-null pour le document
- [ ] Le ChatController importe `DocumentContentExtractor` : `use App\Services\DocumentContentExtractor;`
- [ ] La méthode `getSpecificDocumentsContext()` est appelée avec `document_ids`

---

## 🚀 Pour forcer une solution rapide

Si tu n'as pas accès aux commandes système Linux (serveur basique sans pdftotext/libreoffice) :

**Option 1 : Utiliser un service d'extraction externe**
- Envoyer le fichier à une API externe (comme cloudinary, AWS Textract, etc.)
- Récupérer le texte extrait
- Inclure dans le contexte de l'IA

**Option 2 : Demander à l'utilisateur**
- Afficher un message : "Veuillez copier-coller le contenu du document dans le chat"
- Améliorer le UX avec un bouton "Importer depuis document"

**Option 3 : Améliorer le serveur**
- Upgrade la formule AlwaysData vers Premium
- Ou utiliser un VPS (Linode, DigitalOcean, etc.)

---

## 📞 Support

Si tu as besoin d'aide :
1. Exécute `test_extraction.php`
2. Copie les résultats
3. Fournis les derniers logs : `tail -n 100 storage/logs/laravel.log`
4. Indique quel format de document pose problème (PDF, Word, Excel)
