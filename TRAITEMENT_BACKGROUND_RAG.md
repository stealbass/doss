# Documentation - Traitement Background Documents RAG

## ✅ Vérification Complète du Processus

### 1. **Extraction Texte PDF en Background** ✅
- **Fichier**: `app/Jobs/ProcessDocumentForRAG.php` (lignes 94-162)
- **Méthode**: `extractTextFromDocument()`
- **Support**:
  - ✅ PDF via `smalot/pdfparser`
  - ✅ TXT files
  - ⚠️ DOC/DOCX (TODO: nécessite PhpWord)
- **Fonctionnalités**:
  - Télécharge le fichier depuis R2 ou local storage
  - Crée un fichier temporaire pour le traitement
  - Nettoie le texte extrait
  - Supprime le fichier temporaire après traitement

### 2. **Génération Embeddings** ✅
- **Fichier**: `app/Services/AdvancedRagService.php` (lignes 52-103, 195-217)
- **Méthode**: `indexDocument()` + `generateEmbedding()`
- **Processus**:
  1. Découpe le texte en chunks de 500 tokens
  2. Pour chaque chunk, génère un embedding via OpenAI API
  3. Utilise le modèle `text-embedding-3-small`
  4. Chaque embedding est un vecteur de dimensions N

### 3. **Upsert dans Pinecone** ✅
- **Fichier**: `app/Services/AdvancedRagService.php` (lignes 218-250)
- **Méthode**: `upsertVectors()`
- **Structure des vecteurs**:
```json
{
  "id": "doc_{document_id}_chunk_{index}",
  "values": [embedding_vector],
  "metadata": {
    "document_id": 123,
    "user_id": 456,
    "chunk_index": 0,
    "text": "contenu du chunk",
    "file_name": "document.pdf",
    "created_at": "2026-01-06T..."
  }
}
```

### 4. **Mise à Jour SubmittedDocument** ✅
- **Fichier**: `app/Jobs/ProcessDocumentForRAG.php` (lignes 32-79)
- **Statuts**:
  - `pending`: Document uploadé, en attente de traitement
  - `processing`: Extraction/indexation en cours
  - `completed`: Succès total (texte extrait + indexé dans Pinecone)
  - `failed`: Échec du traitement
- **Champs mis à jour**:
  - `extracted_text`: Texte extrait du document
  - `extracted_text_length`: Nombre de caractères
  - `processing_status`: État actuel
  - `processed_at`: Date/heure de fin de traitement
  - `processing_error`: Message d'erreur si échec

## 🔄 Flux Complet

```
1. Upload Document (API Mobile)
   ↓
2. Stockage R2/Local + Création DB (status: pending)
   ↓
3. Dispatch Job Background → ProcessDocumentForRAG
   ↓
4. [Job Queue] Update status: processing
   ↓
5. [Job Queue] Extraction texte PDF
   ↓
6. [Job Queue] Mise à jour extracted_text dans DB
   ↓
7. [Job Queue] Découpage en chunks (500 tokens)
   ↓
8. [Job Queue] Pour chaque chunk:
       - Génération embedding OpenAI
       - Préparation vecteur Pinecone
   ↓
9. [Job Queue] Upsert batch vers Pinecone
   ↓
10. [Job Queue] Update status: completed + processed_at
    ↓
11. ✅ Document prêt pour recherche sémantique
```

## ⚙️ Configuration Requise

### Queue Worker
Pour que les jobs background fonctionnent, le queue worker doit être actif:
```bash
php artisan queue:work --tries=3 --timeout=300
```

### Variables d'environnement
```env
QUEUE_CONNECTION=database  # ou redis, sqs, etc.
OPENAI_API_KEY=sk-...
PINECONE_API_KEY=...
PINECONE_ENVIRONMENT=gcp-starter
PINECONE_INDEX=dossy-legal-docs
```

### Alternative: MobileAppSettings (priorité sur .env)
Les clés peuvent être configurées dans la table `mobile_app_settings`:
- `openai_api_key`
- `pinecone_api_key`
- `pinecone_environment`
- `pinecone_index_name`

## 🔍 Monitoring du Statut

### API Endpoint
`GET /api/mobile/documents` retourne:
```json
{
  "id": 123,
  "file_name": "contrat.pdf",
  "processing_status": "completed",  // pending|processing|completed|failed
  "processing_error": null,
  "uploaded_at": "2026-01-06 14:30:00"
}
```

### Flutter peut:
1. Afficher un loader tant que `status == 'pending'` ou `'processing'`
2. Afficher une erreur si `status == 'failed'` avec `processing_error`
3. Activer la recherche sémantique si `status == 'completed'`

## 🔧 Retry & Resilience

Le Job `ProcessDocumentForRAG` inclut:
- **Timeout**: 5 minutes (300s)
- **Retries**: 3 tentatives automatiques
- **Backoff**: 60 secondes entre chaque retry
- **Failed Job Handler**: Mise à jour du status en `failed` avec message d'erreur

## 📊 Logs

Tous les événements sont loggés:
```php
Log::info('Document uploaded, queued for RAG processing');
Log::info('Text extracted successfully for document X');
Log::info('Document X indexed successfully');
Log::error('Error processing document X: ...');
```

## 🚀 Migration

Exécuter la migration pour mettre à jour le défaut de `processing_status`:
```bash
php artisan migrate
```

Cela change le défaut de `'completed'` à `'pending'` pour les nouveaux documents.
