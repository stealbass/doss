<?php

namespace App\Observers;

use App\Models\DocumentTemplate;
use App\Jobs\ProcessTemplateForRAG;

class DocumentTemplateObserver
{
    /**
     * Handle the DocumentTemplate "created" event.
     * Automatically index new templates in Pinecone.
     */
    public function created(DocumentTemplate $template): void
    {
        ProcessTemplateForRAG::dispatch($template->id);
    }

    /**
     * Handle the DocumentTemplate "updated" event.
     * Reindex when key fields change.
     */
    public function updated(DocumentTemplate $template): void
    {
        if ($template->isDirty(['title', 'description', 'country'])) {
            ProcessTemplateForRAG::dispatch($template->id);
        }
    }
}
