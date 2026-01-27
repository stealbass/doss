<?php
/**
 * SCRIPT DE MODIFICATION AUTOMATIQUE
 * Ajoute l'extraction synchrone au DocumentController
 * 
 * Usage: php apply_sync_extraction.php
 */

$controllerPath = __DIR__ . '/app/Http/Controllers/Api/Mobile/DocumentController.php';

if (!file_exists($controllerPath)) {
    echo "❌ Fichier non trouvé: {$controllerPath}\n";
    exit(1);
}

// Lire le fichier
$content = file_get_contents($controllerPath);

// Étape 1: Ajouter l'import si absent
$importLine = 'use App\Services\SyncDocumentExtractionService;';
if (strpos($content, $importLine) === false) {
    $content = str_replace(
        'use App\Services\AdvancedRagService;',
        'use App\Services\SyncDocumentExtractionService;' . PHP_EOL . 'use App\Services\AdvancedRagService;',
        $content
    );
    echo "✅ Import ajouté\n";
} else {
    echo "⏭️  Import déjà présent\n";
}

// Étape 2: Remplacer la logique d'upload (dispatchasync -> extraction sync + dispatch)
$oldCode = <<<'EOD'
            Log::debug('✅ [UPLOAD] Document record created', ['document_id' => $document->id]);

            // Dispatch background job for text extraction + embedding + Pinecone indexing
            Log::debug('🔵 [UPLOAD] Dispatching ProcessDocumentForRAG job');
            ProcessDocumentForRAG::dispatch($document->id);
            Log::debug('✅ [UPLOAD] Job dispatched');

            Log::info('Document uploaded, queued for RAG processing', [
                'document_id' => $document->id,
                'user_id' => $user->id,
            ]);
EOD;

$newCode = <<<'EOD'
            Log::debug('✅ [UPLOAD] Document record created', ['document_id' => $document->id]);

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
            ]);
EOD;

if (strpos($content, $oldCode) !== false) {
    $content = str_replace($oldCode, $newCode, $content);
    echo "✅ Logique d'extraction synchrone ajoutée\n";
    
    // Écrire le fichier modifié
    if (file_put_contents($controllerPath, $content)) {
        echo "✅ Fichier sauvegardé avec succès!\n";
        echo "\n🎉 Modifications appliquées avec succès!\n";
        echo "   L'extraction est maintenant synchrone lors du chargement.\n";
        echo "   Les utilisateurs peuvent interroger le document immédiatement.\n";
        exit(0);
    } else {
        echo "❌ Erreur lors de la sauvegarde du fichier\n";
        exit(1);
    }
} else {
    echo "⚠️  Le code à remplacer n'a pas été trouvé.\n";
    echo "   Le fichier a peut-être déjà été modifié ou le format est différent.\n";
    echo "\n   Applique manuellement les modifications décrites dans SYNC_EXTRACTION_MODIFICATION.md\n";
    exit(1);
}
