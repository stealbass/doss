<?php
/**
 * Exact Sync Extraction Modification
 * Place this code AFTER line 167 (after Log::debug('✅ [UPLOAD] Document record created', ...);)
 * 
 * REMOVE THESE 4 LINES (lines 169-172):
 * ---
 *             // Dispatch background job for text extraction + embedding + Pinecone indexing
 *             Log::debug('🔵 [UPLOAD] Dispatching ProcessDocumentForRAG job');
 *             ProcessDocumentForRAG::dispatch($document->id);
 *             Log::debug('✅ [UPLOAD] Job dispatched');
 * ---
 * 
 * REPLACE WITH THIS:
 */
?>

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
