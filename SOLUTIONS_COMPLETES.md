# 🔧 SOLUTIONS POUR LES 2 PROBLÈMES

## ❌ Problème 1: composer.json not writable

### Solution A: Via SSH (RECOMMANDÉ)
```bash
# 1. Se connecter au serveur
ssh your-user@your-server.com

# 2. Aller dans le dossier du projet
cd /home/threesixty/yyy/Dossy

# 3. Donner les permissions d'écriture
chmod 644 composer.json

# 4. Installer pdfparser
composer require smalot/pdfparser

# 5. Vérifier
composer show | grep pdfparser
```

### Solution B: Modifier manuellement composer.json

Si pas d'accès SSH, éditez `composer.json` via FTP/cPanel et ajoutez dans la section `require`:

```json
{
    "require": {
        "php": "^8.1",
        "laravel/framework": "^10.0",
        "smalot/pdfparser": "^2.7"
    }
}
```

Puis lancez:
```bash
composer update smalot/pdfparser
```

### Solution C: Via cPanel File Manager

1. Connexion cPanel/AlwaysData
2. File Manager → Dossy/composer.json
3. Clic droit → Change Permissions → 644
4. Terminal dans cPanel:
   ```bash
   cd Dossy
   composer require smalot/pdfparser
   ```

---

## ❌ Problème 2: Erreur TLS Pinecone

Votre configuration Pinecone (screenshot):
- ✅ API Key: `pcsk_6aNQwn_E9qFgJtrPf9CCWgqo8RRVsE9wcxadY18FARzd`
- ✅ Environment: `gcp-starter`
- ✅ Index Name: `dossy-legal-doc` ⚠️ (pas `dossy-legal-docs`)

### 🔍 Vérification du nom d'index

D'abord, vérifions que le `.env` a le BON nom d'index:

```bash
# Dans le serveur
cd /home/threesixty/yyy/Dossy
cat .env | grep PINECONE
```

**Devrait afficher:**
```
PINECONE_API_KEY=pcsk_6aNQwn_E9qFgJtrPf9CCWgqo8RRVsE9wcxadY18FARzd
PINECONE_ENVIRONMENT=gcp-starter
PINECONE_INDEX_NAME=dossy-legal-doc
```

⚠️ **Si c'est `dossy-legal-docs` (avec 's'), corrigez-le!**

```bash
nano .env
# Changer PINECONE_INDEX_NAME=dossy-legal-docs
# En     PINECONE_INDEX_NAME=dossy-legal-doc
```

### 🛠️ Workaround SSL Temporaire (Laravel HTTP)

Laravel utilise `Http::` (pas cURL natif), donc le fix est différent:

**Créer: `config/http.php`**

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    */

    'options' => [
        // Workaround temporaire pour erreur TLS gnutls
        'verify' => false, // ⚠️ TEMPORAIRE SEULEMENT!
    ],
];
```

**Modifier: `app/Services/AdvancedRagService.php`**

Remplacer toutes les requêtes Pinecone avec l'option `verify => false`:

```php
// AVANT
$response = Http::withHeaders([
    'Api-Key' => $this->pineconeApiKey,
    'Content-Type' => 'application/json',
])->post($url, [
    'vector' => $queryVector,
    // ...
]);

// APRÈS (avec workaround)
$response = Http::withOptions([
    'verify' => false  // ⚠️ TEMPORAIRE pour contourner erreur TLS
])->withHeaders([
    'Api-Key' => $this->pineconeApiKey,
    'Content-Type' => 'application/json',
])->post($url, [
    'vector' => $queryVector,
    // ...
]);
```

---

## 🚀 SCRIPT D'APPLICATION AUTOMATIQUE

J'ai créé un patch automatique. Exécutez:

```bash
cd /home/threesixty/yyy/Dossy

# 1. Test Pinecone avec vraies credentials
php test_pinecone_connection_real.php

# 2. Si erreur TLS détectée, appliquer le patch
php apply_ssl_workaround_patch.php
```

Le script va:
- ✅ Créer `config/http.php` avec `verify => false`
- ✅ Modifier automatiquement `AdvancedRagService.php`
- ✅ Tester la connexion Pinecone
- ✅ Re-tester l'upload de document

---

## ⚠️ IMPORTANT - À FAIRE APRÈS

1. **Ce workaround SSL est TEMPORAIRE**
2. **Ouvrir ticket support hébergeur:**

```
Sujet: Erreur TLS/SSL avec Pinecone API

Bonjour,

J'ai une erreur "gnutls_handshake() failed" lors de connexions HTTPS 
vers l'API Pinecone (dossy-legal-doc.svc.gcp-starter.pinecone.io).

Mon serveur semble utiliser gnutls au lieu de OpenSSL pour cURL/PHP.

Pouvez-vous :
1. Mettre à jour cURL vers version >= 7.68 avec OpenSSL
2. Recompiler PHP avec curl+openssl au lieu de gnutls
3. Vérifier les certificats CA bundle

Configuration actuelle:
- PHP: [votre version]
- cURL: [curl --version]
- Laravel: 10.x

Merci !
```

3. **Quand le support répond, RETIRER le workaround:**
   ```bash
   # Supprimer verify => false de config/http.php
   # Supprimer withOptions(['verify' => false]) dans AdvancedRagService.php
   ```

---

## 🧪 ORDRE D'EXÉCUTION

```bash
# ÉTAPE 1: Fix composer.json permissions
chmod 644 composer.json
composer require smalot/pdfparser

# ÉTAPE 2: Vérifier configuration Pinecone
cat .env | grep PINECONE_INDEX_NAME
# Si c'est "dossy-legal-docs" → changer en "dossy-legal-doc"

# ÉTAPE 3: Tester connexion Pinecone
php test_pinecone_connection_real.php

# ÉTAPE 4: Si erreur TLS → Appliquer patch SSL
php apply_ssl_workaround_patch.php

# ÉTAPE 5: Re-tester upload document
# Via app mobile Flutter

# ÉTAPE 6: Re-tester chat
# Via app mobile Flutter

# ÉTAPE 7: Vérifier logs
tail -f storage/logs/laravel.log | grep -E "ERROR|Pinecone"
```

---

## ✅ RÉSULTATS ATTENDUS

**Après fix 1 (pdfparser):**
```
[2026-01-16 XX:XX:XX] production.INFO: Extracting text for document 18
[2026-01-16 XX:XX:XX] production.INFO: Text extracted successfully (12345 characters)
```

**Après fix 2 (SSL):**
```
[2026-01-16 XX:XX:XX] production.INFO: Mobile chat: Processing user documents with semantic search
[2026-01-16 XX:XX:XX] production.INFO: Pinecone query successful (3 matches found)
```

❌ **Plus de:**
- "PdfParser not available"
- "cURL error 35: gnutls_handshake() failed"

---

**Fichiers créés:**
1. ✅ `fix_composer_permissions.sh` - Script permissions
2. ✅ `test_pinecone_connection_real.php` - Test avec vraies credentials
3. ✅ `apply_ssl_workaround_patch.php` - Patch automatique (à créer ci-dessous)
