<?php

namespace App\Console\Commands;

use App\Models\SubmittedDocument;
use App\Jobs\ProcessDocumentForRAG;
use Illuminate\Console\Command;

class RetryDocumentExtraction extends Command
{
    protected $signature = 'document:retry-extraction {document_id}';
    protected $description = 'Retry text extraction for a specific document';

    public function handle()
    {
        $documentId = $this->argument('document_id');
        
        $document = SubmittedDocument::find($documentId);
        
        if (!$document) {
            $this->error("❌ Document ID {$documentId} not found");
            return 1;
        }
        
        $this->info("📄 Document found:");
        $this->info("   ID: {$document->id}");
        $this->info("   File: {$document->original_filename}");
        $this->info("   Current Status: {$document->processing_status}");
        $this->info("   Current Text Length: " . ($document->extracted_text_length ?? 0));
        
        if ($this->confirm('Do you want to retry extraction for this document?', true)) {
            $this->info("\n🔄 Dispatching ProcessDocumentForRAG job...");
            
            // Reset status
            $document->update([
                'processing_status' => 'pending',
                'processing_error' => null,
            ]);
            
            // Dispatch job
            ProcessDocumentForRAG::dispatch($document->id);
            
            $this->info("✅ Job dispatched successfully!");
            $this->info("\nRun: php artisan queue:work --once");
            $this->info("to process the job immediately.");
        }
        
        return 0;
    }
}
