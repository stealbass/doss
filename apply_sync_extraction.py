#!/usr/bin/env python3
"""
Script de modification automatique pour l'extraction synchrone
Ajoute SyncDocumentExtractionService au DocumentController
"""

import os
import re

controller_path = r'app\Http\Controllers\Api\Mobile\DocumentController.php'

if not os.path.exists(controller_path):
    print(f"❌ Fichier non trouvé: {controller_path}")
    exit(1)

# Lire le fichier
with open(controller_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Étape 1: Ajouter l'import
import_line = 'use App\\Services\\SyncDocumentExtractionService;'
if import_line not in content:
    content = content.replace(
        'use App\\Services\\AdvancedRagService;',
        f'{import_line}\nuse App\\Services\\AdvancedRagService;'
    )
    print("✅ Import ajouté")
else:
    print("⏭️  Import déjà présent")

# Étape 2: Remplacer la logique d'upload
old_code = """            Log::debug('✅ [UPLOAD] Document record created', ['document_id' => $document->id]);

            // Dispatch background job for text extraction + embedding + Pinecone indexing
            Log::debug('🔵 [UPLOAD] Dispatching ProcessDocumentForRAG job');
            ProcessDocumentForRAG::dispatch($document->id);
            Log::debug('✅ [UPLOAD] Job dispatched');

            Log::info('Document uploaded, queued for RAG processing', [
                'document_id' => $document->id,
                'user_id' => $user->id,
            ]);"""

new_code = """            Log::debug('✅ [UPLOAD] Document record created', ['document_id' => $document->id]);

            // Step 1: Extract text SYNCHRONOUSLY for immediate availability
            // This allows users to query the document immediately after upload
            Log::debug('🔵 [UPLOAD] Starting SYNCHRONOUS text extraction');
            $extractionService = new SyncDocumentExtractionService();
            $extractionSuccess = $extractionService->extractText($document);
            
            // Reload document to check extraction result
            $document->refresh();
            Log::debug('✅ [UPLOAD] Text extraction completed', [
                'success' => $extractionSuccess,
                'has_text' => !empty($document->extracted_text),
                'text_length' => $document->extracted_text_length ?? 0,
                'processing_status' => $document->processing_status,
            ]);

            // Step 2: Dispatch background job ONLY for Pinecone indexing
            // Text is already extracted, so this job will focus on:
            // - Anonymization detection
            // - Pinecone vector embeddings + upsertion
            Log::debug('🔵 [UPLOAD] Dispatching Pinecone indexing job');
            ProcessDocumentForRAG::dispatch($document->id);
            Log::debug('✅ [UPLOAD] Pinecone indexing job dispatched');

            Log::info('Document uploaded, extraction completed, queuing for Pinecone indexing', [
                'document_id' => $document->id,
                'user_id' => $user->id,
                'extraction_success' => $extractionSuccess,
            ]);"""

if old_code in content:
    content = content.replace(old_code, new_code)
    print("✅ Logique d'extraction synchrone ajoutée")
    
    # Écrire le fichier modifié
    with open(controller_path, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print("\n✅ Fichier sauvegardé avec succès!")
    print("\n🎉 Modifications appliquées avec succès!")
    print("   L'extraction est maintenant synchrone lors du chargement.")
    print("   Les utilisateurs peuvent interroger le document immédiatement.")
    exit(0)
else:
    print("⚠️  Le code à remplacer n'a pas été trouvé.")
    print("   Le fichier a peut-être déjà été modifié ou le format est différent.")
    print("\n   Consulte SYNC_EXTRACTION_MODIFICATION.md pour les modifications manuelles.")
    exit(1)
