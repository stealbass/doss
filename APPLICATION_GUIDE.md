# 🔧 GUIDE D'APPLICATION - Extraction Synchrone

## Étape 1: Vérifier que SyncDocumentExtractionService existe
✅ **FAIT** - Fichier créé: `app/Services/SyncDocumentExtractionService.php`

## Étape 2: Ajouter l'import dans DocumentController
**Fichier**: `app/Http/Controllers/Api/Mobile/DocumentController.php`
**Ligne**: 12

Ajoute cette ligne:
```php
use App\Services\SyncDocumentExtractionService;
```

**Exemple** (après la ligne existante):
```php
use App\Services\AdvancedRagService;
use App\Services\SyncDocumentExtractionService;  // ← AJOUTER CETTE LIGNE
use Illuminate\Http\Request;
```

## Étape 3: Remplacer la logique d'upload
**Fichier**: `app/Http/Controllers/Api/Mobile/DocumentController.php`
**Lignes**: 169-172 (à vérifier, mais environ)

### AVANT (4 lignes à SUPPRIMER):
```php
            // Dispatch background job for text extraction + embedding + Pinecone indexing
            Log::debug('🔵 [UPLOAD] Dispatching ProcessDocumentForRAG job');
            ProcessDocumentForRAG::dispatch($document->id);
            Log::debug('✅ [UPLOAD] Job dispatched');
```

### APRÈS (30 lignes à AJOUTER):
```php
            // Step 1: Extract text SYNCHRONOUSLY for immediate availability
            // This allows users to query the document immediately after upload
            Log::info('Starting SYNCHRONOUS text extraction for document ' . $document->id);
            $extractionService = new SyncDocumentExtractionService();
            $extractionSuccess = $extractionService->extractText($document);
            
            // Reload document to check extraction result
            $document->refresh();
            Log::info('Text extraction completed', [
                'document_id' => $document->id,
                'success' => $extractionSuccess,
                'has_text' => !empty($document->extracted_text),
                'text_length' => $document->extracted_text_length ?? 0,
            ]);

            // Step 2: Dispatch background job ONLY for Pinecone indexing
            // Text is already extracted, so this job will focus on:
            // - Anonymization detection
            // - Pinecone vector embeddings + upsertion
            Log::info('Dispatching Pinecone indexing job for document ' . $document->id);
            ProcessDocumentForRAG::dispatch($document->id);

            Log::info('Document uploaded with extraction completed', [
                'document_id' => $document->id,
                'user_id' => $user->id,
                'extraction_success' => $extractionSuccess,
            ]);
```

## Étape 4: Aussi mettre à jour le log suivant
**Ligne environ 174** - Cette ligne peut être supprimée ou modifiée:

AVANT:
```php
            Log::info('Document uploaded, queued for RAG processing', [
```

APRÈS (DÉJÀ INCLUS DANS LE CODE CI-DESSUS):
```php
            Log::info('Document uploaded with extraction completed', [
```

## Résumé des Changements

✅ **Fichier créé**: `app/Services/SyncDocumentExtractionService.php`
- Service d'extraction synchrone
- 5 minutes timeout (au lieu de 15 min pour la queue)
- Retourne bool pour succès/échec

✅ **Fichier modifié**: `app/Http/Controllers/Api/Mobile/DocumentController.php`
- Ajouter import `SyncDocumentExtractionService`
- Appeler `extractText()` SYNCHRONEMENT après création du document
- Puis dispatcher le job pour Pinecone indexation

## Avantages

✅ **Utilisateur peut interroger immédiatement** - Pas d'attente queue
✅ **Extraction rapide** - 5 min timeout au lieu de 15 min
✅ **Pinecone en background** - N'impacte pas le temps de réponse HTTP
✅ **Failover gracieux** - Si extraction sync échoue, job peut réessayer

## Tests Après Application

1. **Charger un document**
2. **Immédiatement ouvrir le chat**
3. **Poser une question sur le document**
4. **Attendre la réponse** - Devrait avoir du contenu (pas "No relevant content")

## Problème Secondaire: Permissions
Les logs montrent aussi des erreurs de permissions sur les sessions:
```
Failed to open stream: Permission denied
storage/framework/sessions/
```

Sur le serveur, exécute:
```bash
chmod 777 /home/threesixty/yyy/Dossy/storage/framework/sessions
chmod 777 /home/threesixty/yyy/Dossy/storage/logs
```

## Questions?
Regarde les fichiers créés:
- `REPLACEMENT_CODE.php` - Code exact à copier
- `SYNC_EXTRACTION_MODIFICATION.md` - Guide détaillé
- `apply_sync_extraction.php` - Script d'application (si PHP disponible)
