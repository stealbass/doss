# 🔧 GUIDE DE CONFIGURATION PINECONE

## 📌 Étapes de Configuration

### 1. Créer un compte Pinecone

1. Allez sur https://www.pinecone.io/
2. Cliquez sur "Sign Up" → Utilisez Google/GitHub
3. Choisissez le plan **Starter** (gratuit)
   - 1 projet
   - 1 index
   - 100K vectors
   - Suffisant pour ~200 documents de 50 pages

### 2. Créer l'index Pinecone

Dans le dashboard Pinecone:

```yaml
Index Name: dossy-legal-docs
Dimensions: 1536
Metric: cosine
Environment: gcp-starter
Plan: Starter (Free)
```

**Commande via API (optionnel):**
```python
import pinecone

pinecone.init(api_key="YOUR_API_KEY", environment="gcp-starter")

pinecone.create_index(
    name="dossy-legal-docs",
    dimension=1536,
    metric="cosine"
)
```

### 3. Récupérer les credentials

Dans le dashboard Pinecone → **API Keys**:
- **API Key**: `pcsk_...` (commence par pcsk_)
- **Environment**: `gcp-starter` (ou autre selon région)
- **Index Name**: `dossy-legal-docs`

### 4. Configurer dans la base de données

#### Option A: Via SQL direct

```sql
-- Insérer/mettre à jour les paramètres
INSERT INTO mobile_app_settings (
    openai_api_key,
    pinecone_api_key,
    pinecone_environment,
    pinecone_index_name,
    created_at,
    updated_at
) VALUES (
    'sk-...', -- Votre clé OpenAI
    'pcsk_...', -- Votre clé Pinecone
    'gcp-starter', -- Environnement Pinecone
    'dossy-legal-docs', -- Nom de l'index
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    pinecone_api_key = VALUES(pinecone_api_key),
    pinecone_environment = VALUES(pinecone_environment),
    pinecone_index_name = VALUES(pinecone_index_name),
    updated_at = NOW();
```

#### Option B: Via Laravel Tinker

```bash
php artisan tinker
```

```php
$settings = \App\Models\MobileAppSetting::first();

if (!$settings) {
    $settings = new \App\Models\MobileAppSetting();
}

$settings->openai_api_key = 'sk-...';
$settings->pinecone_api_key = 'pcsk_...';
$settings->pinecone_environment = 'gcp-starter';
$settings->pinecone_index_name = 'dossy-legal-docs';
$settings->save();
```

#### Option C: Via Interface Admin (si disponible)

Allez dans Admin → Paramètres → Mobile App Settings

---

## ✅ Vérification de la Configuration

### Test 1: Vérifier en base de données

```sql
SELECT 
    SUBSTRING(openai_api_key, 1, 10) as openai_key_prefix,
    SUBSTRING(pinecone_api_key, 1, 10) as pinecone_key_prefix,
    pinecone_environment,
    pinecone_index_name
FROM mobile_app_settings;
```

**Résultat attendu:**
```
openai_key_prefix: sk-proj-...
pinecone_key_prefix: pcsk_...
pinecone_environment: gcp-starter
pinecone_index_name: dossy-legal-docs
```

### Test 2: Test de connexion Pinecone

```bash
php artisan tinker
```

```php
$service = new \App\Services\AdvancedRagService();

// Test simple
$doc = \App\Models\SubmittedDocument::whereNotNull('extracted_text')->first();

if ($doc) {
    $result = $service->indexDocument($doc);
    echo $result ? "✅ Connexion OK" : "❌ Erreur connexion";
}
```

### Test 3: Script automatique

```bash
php test_semantic_search_system.php
```

---

## 🔍 Structure de l'Index Pinecone

### Exemple de vector stocké

```json
{
  "id": "doc_123_chunk_0",
  "values": [0.123, -0.456, 0.789, ...], // 1536 dimensions
  "metadata": {
    "document_id": 123,
    "user_id": 456,
    "chunk_index": 0,
    "text": "Ceci est le contenu complet du premier chunk du document...",
    "file_name": "contrat.pdf",
    "created_at": "2026-01-16T10:00:00Z"
  }
}
```

### Requête de recherche

```json
POST /query
{
  "vector": [0.234, -0.567, 0.890, ...],
  "topK": 10,
  "includeMetadata": true,
  "filter": {
    "$and": [
      {"user_id": {"$eq": 456}},
      {"document_id": {"$in": [123, 124, 125]}}
    ]
  }
}
```

---

## 📊 Monitoring Pinecone

### Dashboard Pinecone

Accédez à: https://app.pinecone.io/

**Métriques à surveiller:**
- **Vector Count**: Nombre total de chunks indexés
- **Storage Used**: Espace utilisé (gratuit: 10GB)
- **Queries/day**: Nombre de recherches (gratuit: illimité)

### Calcul du nombre de vectors

```
Nombre de vectors = Σ(documents) × (pages/doc × mots/page ÷ 500)

Exemple:
- 100 documents
- 20 pages/document en moyenne
- 300 mots/page
- 500 mots/chunk
= 100 × (20 × 300 ÷ 500)
= 100 × 12
= 1,200 vectors
```

### Limites du plan gratuit

| Ressource | Limite Starter | Limite Pro |
|---|---|---|
| Indexes | 1 | 5+ |
| Vectors | 100,000 | Illimité |
| Storage | 10 GB | Illimité |
| Queries | Illimité | Illimité |
| Latence | ~300ms | ~100ms |

---

## 🚨 Résolution de Problèmes

### Erreur: "Index not found"

**Cause:** Index pas créé ou mauvais nom

**Solution:**
```bash
# Vérifier l'index existe
curl -X GET "https://controller.gcp-starter.pinecone.io/indexes" \
  -H "Api-Key: pcsk_..."

# Créer l'index si nécessaire
curl -X POST "https://controller.gcp-starter.pinecone.io/indexes" \
  -H "Api-Key: pcsk_..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "dossy-legal-docs",
    "dimension": 1536,
    "metric": "cosine"
  }'
```

### Erreur: "Dimension mismatch"

**Cause:** Embeddings de taille différente de l'index

**Solution:**
- Index créé avec `dimension: 1536`
- AdvancedRagService utilise `text-embedding-3-small` (1536 dimensions)
- Vérifier cohérence modèle OpenAI

### Erreur: "Quota exceeded"

**Cause:** Plan gratuit dépassé (100K vectors)

**Solution:**
1. Supprimer anciens documents
2. Optimiser chunking (augmenter taille chunks)
3. Upgrade vers plan Pro

### Erreur: "Authentication failed"

**Cause:** Clé API invalide

**Solution:**
```sql
-- Vérifier clé stockée
SELECT pinecone_api_key FROM mobile_app_settings;

-- Mettre à jour
UPDATE mobile_app_settings 
SET pinecone_api_key = 'pcsk_...nouvelle_clé...'
WHERE id = 1;
```

---

## 🔄 Migration depuis SimpleRag

Si vous utilisiez SimpleRag (FULLTEXT) avant:

### Étape 1: Réindexer les documents existants

```bash
php artisan tinker
```

```php
// Réindexer tous les documents avec extracted_text
$documents = \App\Models\SubmittedDocument::whereNotNull('extracted_text')
    ->where('processing_status', 'completed')
    ->get();

foreach ($documents as $doc) {
    \App\Jobs\ProcessDocumentForRAG::dispatch($doc->id);
    echo "Queued document {$doc->id}\n";
}
```

### Étape 2: Lancer le queue worker

```bash
php artisan queue:work --queue=default --timeout=300
```

### Étape 3: Vérifier l'indexation

```bash
php test_semantic_search_system.php
```

---

## 📖 Resources Utiles

### Documentation officielle
- Pinecone Docs: https://docs.pinecone.io/
- OpenAI Embeddings: https://platform.openai.com/docs/guides/embeddings
- Laravel Queues: https://laravel.com/docs/queues

### Tutoriels
- Pinecone Quickstart: https://docs.pinecone.io/docs/quickstart
- Building RAG: https://docs.pinecone.io/docs/rag

### Limites et Prix
- Pinecone Pricing: https://www.pinecone.io/pricing/
- OpenAI Pricing: https://openai.com/pricing

---

## ✅ Checklist finale

- [ ] Compte Pinecone créé
- [ ] Index `dossy-legal-docs` créé (1536 dimensions, cosine)
- [ ] API Key récupérée (commence par `pcsk_`)
- [ ] Configuration enregistrée dans `mobile_app_settings`
- [ ] Test de connexion réussi
- [ ] Premier document indexé avec succès
- [ ] Première recherche sémantique fonctionnelle
- [ ] Logs vérifiés (pas d'erreurs)
- [ ] Queue worker lancé
- [ ] Application mobile testée

---

**Date:** 2026-01-16  
**Statut:** Configuration complète  
**Support:** Logs dans `storage/logs/laravel.log`
