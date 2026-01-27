# 📦 Installation des Dépendances - Phase 2

## Nouvelles Dépendances Ajoutées

Les packages suivants ont été ajoutés à `composer.json` pour supporter la Phase 2 (RAG Services + Mobile API) :

### 1. **OpenAI PHP Client** (`openai-php/client`)
```json
"openai-php/client": "^0.10.1"
```
**Usage** : Client officiel OpenAI pour PHP
- Chat completions (GPT-3.5, GPT-4, GPT-4-turbo)
- Embeddings (text-embedding-3-small, text-embedding-ada-002)
- Token counting
- Stream support

**Documentation** : https://github.com/openai-php/client

---

### 2. **Pinecone PHP SDK** (`probots-io/pinecone-php`)
```json
"probots-io/pinecone-php": "^1.1"
```
**Usage** : Client Pinecone pour vector database
- Upsert vectors (embeddings)
- Query similarity search
- Delete vectors
- Index management

**Documentation** : https://github.com/probots-io/pinecone-php

---

### 3. **PDF Parser** (`smalot/pdfparser`)
```json
"smalot/pdfparser": "^2.10"
```
**Usage** : Extraction de texte depuis PDF
- Parse PDF files
- Extract text content
- Get PDF metadata
- Pages management

**Documentation** : https://github.com/smalot/pdfparser

---

## 🚀 Instructions d'Installation

### Sur le Serveur AlwaysData (Production)

#### Étape 1 : Connexion SSH
```bash
ssh utilisateur@ssh-utilisateur.alwaysdata.net
cd ~/www/
```

#### Étape 2 : Pull Latest Code
```bash
git pull origin main
```

#### Étape 3 : Installer les Dépendances
```bash
composer install --optimize-autoloader --no-dev
```

**OU** si vous voulez mettre à jour toutes les dépendances :
```bash
composer update --optimize-autoloader --no-dev
```

**Note** : L'option `--optimize-autoloader` optimise l'autoloader Composer pour de meilleures performances en production.

---

### En Développement Local (Optionnel)

#### Avec Dépendances de Développement
```bash
composer install
```

#### Mise à Jour Complète
```bash
composer update
```

---

## 📊 Vérification de l'Installation

### Vérifier que les Packages sont Installés

```bash
composer show | grep -E "openai-php|pinecone|pdfparser"
```

**Résultat attendu** :
```
openai-php/client         v0.10.1    OpenAI PHP API client
probots-io/pinecone-php   v1.1.x     Pinecone PHP SDK
smalot/pdfparser          v2.10.x    PDF Parser library
```

---

### Tester OpenAI Client

```bash
php artisan tinker
```

```php
// Test OpenAI client
$client = \OpenAI::client(env('OPENAI_API_KEY'));
$response = $client->chat()->create([
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello!']
    ],
]);
echo $response->choices[0]->message->content;
```

---

### Tester PDF Parser

```bash
php artisan tinker
```

```php
// Test PDF Parser
$parser = new \Smalot\PdfParser\Parser();
// Tester avec un fichier PDF existant
$pdf = $parser->parseFile(storage_path('app/public/legal_documents/test.pdf'));
$text = $pdf->getText();
echo substr($text, 0, 200);
```

---

### Tester Pinecone Client

```bash
php artisan tinker
```

```php
// Test Pinecone (après configuration)
use Probots\Pinecone\Client as PineconeClient;

$pinecone = new PineconeClient(
    env('PINECONE_API_KEY'),
    env('PINECONE_ENVIRONMENT')
);

// Lister les indexes
$indexes = $pinecone->listIndexes();
print_r($indexes);
```

---

## 🔧 Configuration Requise

### Variables d'Environnement

Ajouter dans `.env` :

```env
# OpenAI (OBLIGATOIRE)
OPENAI_API_KEY=sk-proj-YOUR_KEY_HERE

# Pinecone (OBLIGATOIRE)
PINECONE_API_KEY=YOUR_PINECONE_KEY
PINECONE_ENVIRONMENT=us-east-1
PINECONE_INDEX=dossy-documents
```

---

## 📝 Packages Déjà Installés (Réutilisés)

Ces packages étaient déjà dans `composer.json` et sont utilisés par la Phase 2 :

### Laravel Sanctum
```json
"laravel/sanctum": "^4.0"
```
**Usage** : API authentication avec tokens bearer

### Guzzle HTTP
```json
"guzzlehttp/guzzle": "^7.9"
```
**Usage** : HTTP client pour requêtes API (Pinecone, OpenAI)

### League Flysystem S3
```json
"league/flysystem-aws-s3-v3": "^3.28"
```
**Usage** : Cloudflare R2 storage (compatible S3)

### OpenAI Legacy (orhanerday/open-ai)
```json
"orhanerday/open-ai": "^5.2"
```
**Usage** : Alternative OpenAI client (déjà présent, peut être utilisé aussi)

---

## 🐛 Troubleshooting

### Erreur : "Class 'OpenAI' not found"

**Solution** :
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

---

### Erreur : "Your requirements could not be resolved"

**Solution** :
```bash
# Mettre à jour Composer
composer self-update

# Essayer avec --ignore-platform-reqs (si PHP version mismatch)
composer install --ignore-platform-reqs --optimize-autoloader --no-dev
```

---

### Erreur : Memory limit exceeded

**Solution** :
```bash
# Augmenter memory limit temporairement
php -d memory_limit=512M /usr/local/bin/composer install
```

---

### Vérifier Version PHP

```bash
php -v
```

**Requis** : PHP 8.2 ou supérieur

---

## 📦 Liste Complète des Dépendances Phase 2

| Package | Version | Usage |
|---------|---------|-------|
| `openai-php/client` | ^0.10.1 | OpenAI API (Chat, Embeddings) |
| `probots-io/pinecone-php` | ^1.1 | Pinecone vector database |
| `smalot/pdfparser` | ^2.10 | PDF text extraction |
| `laravel/sanctum` | ^4.0 | API authentication |
| `guzzlehttp/guzzle` | ^7.9 | HTTP client |
| `league/flysystem-aws-s3-v3` | ^3.28 | R2 storage |

---

## ✅ Checklist Installation

- [ ] SSH sur AlwaysData
- [ ] `git pull origin main`
- [ ] `composer install --optimize-autoloader --no-dev`
- [ ] Vérifier packages installés (`composer show`)
- [ ] Configurer `.env` (OPENAI_API_KEY, PINECONE_*)
- [ ] `php artisan config:clear`
- [ ] `php artisan cache:clear`
- [ ] Tester OpenAI client (tinker)
- [ ] Tester PDF parser (tinker)
- [ ] Tester Pinecone client (tinker)

---

## 🎯 Après Installation

### Tester l'API Mobile

```bash
# Tester Register
curl -X POST https://dossy.alwaysdata.net/api/mobile/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+237670000000"
  }'
```

Si le register fonctionne, toutes les dépendances sont correctement installées ! ✅

---

## 📞 Support

**Issues GitHub** : https://github.com/stealbass/doss/issues  
**Pull Request** : https://github.com/stealbass/doss/pull/10

---

**Date** : 2025-11-27  
**Phase** : 2 - RAG Services + Mobile API  
**Status** : Dépendances documentées et prêtes à l'installation
