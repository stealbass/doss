<?php

namespace App\Observers;

use App\Models\LegalDocument;
use App\Jobs\ProcessLegalDocumentForRAG;

class LegalDocumentObserver
{
    /**
     * Handle the LegalDocument "created" event.
     * Automatically index new documents in Pinecone
     */
    public function created(LegalDocument $document): void
    {
        // Dispatch reindexing job for new documents
        ProcessLegalDocumentForRAG::dispatch($document->id);
    }

    /**
     * Handle the LegalDocument "updated" event.
     * Reindex when document is updated (e.g., country changed)
     */
    public function updated(LegalDocument $document): void
    {
        // Only reindex if important fields changed
        if ($document->isDirty(['title', 'description', 'country'])) {
            ProcessLegalDocumentForRAG::dispatch($document->id);
        }
    }

    /**
     * Handle the LegalDocument "deleted" event.
     * Remove from Pinecone when deleted
     */
    public function deleted(LegalDocument $document): void
    {
        // Optional: Remove from Pinecone index
        // $ragService = app(AdvancedRagService::class);
        // $ragService->deleteFromPinecone('legal_document', $document->id);
    }
}
