# 🚨 FIX URGENT - 2 Erreurs Identifiées

## 📋 Résumé des Problèmes

### **Problème 1: Upload de Document** ❌
**Erreur**: `PdfParser not available` + `Failed to extract text from document`

**Cause**: Bibliothèque PDF non installée sur le serveur

**Impact**: Les documents PDF ne peuvent pas être traités


### **Problème 2: Chat avec Document** ❌
**Erreur**: `cURL error 35: gnutls_handshake() failed: The TLS connection was non-properly terminated`

**Cause**: Problème de connexion TLS entre le serveur et Pinecone

**Impact**: La recherche sémantique échoue, le système utilise un fallback basique


---

## ✅ SOLUTIONS IMMÉDIATES

### 🔧 Solution 1: Installer smalot/pdfparser

```bash
# 1. Se connecter au serveur via SSH
ssh threesixty@your-server.com

# 2. Aller dans le dossier du projet
cd /home/threesixty/yyy/Dossy

# 3. Installer la bibliothèque PDF
composer require smalot/pdfparser

# 4. Vérifier l'installation
composer show | grep pdfparser
```

**Résultat attendu**: `smalot/pdfparser` s'affiche dans la liste


### 🔧 Solution 2: Résoudre le problème TLS avec Pinecone

#### **Option A: Mettre à jour cURL/OpenSSL (RECOMMANDÉ)**

```bash
# Sur le serveur AlwaysData ou votre hébergeur

# 1. Vérifier les versions actuelles
curl --version
php -i | grep -i openssl

# 2. Si curl < 7.68, contacter le support pour mise à jour
# 3. Vérifier que PHP utilise curl avec OpenSSL (pas gnutls)
```

Si vous êtes sur **AlwaysData**, ouvrez un ticket support:
```
Sujet: Erreur TLS cURL avec Pinecone
Message: 
Bonjour,
J'ai une erreur "cURL error 35: gnutls_handshake() failed" 
lors de connexions HTTPS vers Pinecone (dossy-legal-doc.svc.gcp-starter.pinecone.io).
Pouvez-vous :
1. Mettre à jour cURL vers version >= 7.68
2. S'assurer que PHP utilise curl compilé avec OpenSSL (pas gnutls)
3. Vérifier les certificats SSL/TLS

Merci !
```


#### **Option B: Workaround temporaire - Désactiver vérification SSL** ⚠️

**ATTENTION**: À utiliser UNIQUEMENT en développement, JAMAIS en production !

Modifier temporairement `app/Services/AdvancedRagService.php`:

```php
// Dans queryPinecone() et queryPineconeWithDocuments()
// Ajouter CURLOPT_SSL_VERIFYPEER => false

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_SSL_VERIFYPEER => false,  // ⚠️ TEMPORAIRE SEULEMENT
    CURLOPT_SSL_VERIFYHOST => false,  // ⚠️ TEMPORAIRE SEULEMENT
]);
```


#### **Option C: Utiliser Guzzle au lieu de cURL natif**

```bash
# 1. Installer Guzzle
composer require guzzlehttp/guzzle

# 2. Modifier AdvancedRagService.php pour utiliser Guzzle
```


---

## 🧪 TESTS APRÈS CORRECTIFS

### Test 1: Vérifier que PdfParser fonctionne

```php
// Créer: test_pdf_parser.php

<?php
require 'vendor/autoload.php';

use Smalot\PdfParser\Parser;

try {
    $parser = new Parser();
    echo "✅ PdfParser installé et fonctionnel\n";
    
    // Test avec un PDF existant
    $pdf = $parser->parseFile('/home/threesixty/yyy/Dossy/storage/app/public/documents/test.pdf');
    $text = $pdf->getText();
    echo "✅ Extraction de texte fonctionne\n";
    echo "Extrait: " . substr($text, 0, 200) . "...\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
```

```bash
php test_pdf_parser.php
```


### Test 2: Vérifier la connexion Pinecone

```php
// Créer: test_pinecone_connection.php

<?php
require 'vendor/autoload.php';

$apiKey = 'YOUR_PINECONE_API_KEY';
$indexHost = 'dossy-legal-doc.svc.gcp-starter.pinecone.io';

// Test de connexion basique
$url = "https://{$indexHost}/describe_index_stats";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Api-Key: {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => true,
]);

echo "🔌 Test de connexion à Pinecone...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "❌ Erreur cURL: {$error}\n";
    echo "Code HTTP: {$httpCode}\n";
} else {
    echo "✅ Connexion réussie!\n";
    echo "Code HTTP: {$httpCode}\n";
    echo "Réponse: {$response}\n";
}

curl_close($ch);
```

```bash
php test_pinecone_connection.php
```


### Test 3: Re-tester l'upload de document

1. Ouvrir l'app mobile Flutter
2. Aller dans "Documents"
3. Uploader un PDF
4. Vérifier les logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "Extracting text"
   ```
5. ✅ Attendu: "Text extracted successfully" (pas "PdfParser not available")


### Test 4: Re-tester le chat avec document

1. Ouvrir le chat
2. Sélectionner un document
3. Poser une question
4. Vérifier les logs:
   ```bash
   tail -f storage/logs/laravel.log | grep -E "Pinecone|semantic search"
   ```
5. ✅ Attendu: Pas d'erreur "cURL error 35"


---

## 📊 COMMANDES DE DIAGNOSTIC

```bash
# 1. Vérifier composer.json contient pdfparser
cat composer.json | grep pdfparser

# 2. Vérifier les packages installés
composer show | grep -E "pdfparser|guzzle"

# 3. Tester cURL manuellement
curl -v https://dossy-legal-doc.svc.gcp-starter.pinecone.io

# 4. Voir les dernières erreurs
tail -100 storage/logs/laravel.log | grep ERROR

# 5. Vérifier version PHP et extensions
php -v
php -m | grep -E "curl|openssl"
```


---

## 🎯 ORDRE D'EXÉCUTION RECOMMANDÉ

1. **Installer smalot/pdfparser** (5 minutes)
   ```bash
   composer require smalot/pdfparser
   ```

2. **Contacter support hébergeur** pour TLS (1-2 jours)
   - Demander mise à jour cURL
   - Demander vérification OpenSSL

3. **En attendant**: Workaround temporaire SSL (10 minutes)
   - Ajouter CURLOPT_SSL_VERIFYPEER => false
   - **RETIRER DÈS QUE LE SUPPORT RÉPOND**

4. **Re-tester** après chaque étape


---

## ⚠️ IMPORTANT

- **Ne jamais désactiver SSL en production** (Option B)
- **Supprimer le workaround SSL** dès que l'hébergeur corrige le problème
- **Documenter toutes les modifications** dans CHANGELOG


---

## 📞 SUPPORT

Si les problèmes persistent après ces étapes:

1. Vérifier que le serveur a accès internet sortant (port 443)
2. Vérifier qu'il n'y a pas de firewall bloquant Pinecone
3. Vérifier les variables d'environnement `.env`:
   ```
   PINECONE_API_KEY=pcsk_...
   PINECONE_ENVIRONMENT=gcp-starter
   PINECONE_INDEX_NAME=dossy-legal-docs
   ```
4. Partager les logs complets de `test_pinecone_connection.php`


---

**Date**: 16 janvier 2026, 19h01  
**Logs analysés**: Lines 71500-72008 de laravel.log  
**Erreurs identifiées**: 2 (Document upload + Pinecone TLS)
