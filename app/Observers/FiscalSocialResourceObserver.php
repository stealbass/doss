<?php

namespace App\Observers;

use App\Models\FiscalSocialResource;
use App\Jobs\ProcessFiscalResourceForRAG;

class FiscalSocialResourceObserver
{
    /**
     * Handle the FiscalSocialResource "created" event.
     * Automatically index new resources in Pinecone
     */
    public function created(FiscalSocialResource $resource): void
    {
        // Dispatch reindexing job for new resources
        if (class_exists(ProcessFiscalResourceForRAG::class)) {
            ProcessFiscalResourceForRAG::dispatch($resource->id);
        }
    }

    /**
     * Handle the FiscalSocialResource "updated" event.
     * Reindex when resource is updated
     */
    public function updated(FiscalSocialResource $resource): void
    {
        // Only reindex if important fields changed
        if ($resource->isDirty(['title', 'description', 'country', 'year'])) {
            if (class_exists(ProcessFiscalResourceForRAG::class)) {
                ProcessFiscalResourceForRAG::dispatch($resource->id);
            }
        }
    }
}
