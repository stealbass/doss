<?php

namespace App\Jobs;

use App\Models\SubmittedDocument;
use App\Services\AdvancedRagService;
use App\Services\AnonymizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ProcessDocumentForRAG implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900; // 15 minutes timeout (for 30MB files + OCR)
    
    /**
     * The document ID to process
     */
    public int $documentId;

    public function __construct(int $documentId)
    {
        $this->documentId = $documentId;
    }

    public function handle(AdvancedRagService $ragService): void
    {
        $document = SubmittedDocument::find($this->documentId);

        if (!$document) {
            Log::error("Document {$this->documentId} not found for RAG processing");
            return;
        }

        try {
            // Step 1: Update status to processing
            $document->update(['processing_status' => 'processing']);

            Log::info("Processing document {$document->id}", [
                'file_name' => $document->original_filename,
                'stored_filename' => $document->stored_filename,
                'storage_path' => $document->storage_path,
                'mime_type' => $document->mime_type,
                'size' => $document->file_size,
            ]);

            // Step 2: Extract text using Python script (external process)
            if (empty($document->extracted_text) || $document->processing_status === 'failed') {
                Log::info("[EXTRACTION] Starting text extraction for document {$document->id}");
                
                // Call Python script to extract text
                $pythonScript = base_path('scripts/extract_documents.py');
                $process = new Process([
                    'python3',
                    $pythonScript,
                    '--document-id',
                    (string)$document->id,
                ]);
                $process->setTimeout(600); // 10 minutes
                
                Log::info("[EXTRACTION] Executing Python: python3 {$pythonScript} --document-id {$document->id}");
                
                try {
                    $process->run();
                    
                    if ($process->isSuccessful()) {
                        Log::info("[EXTRACTION] Python script executed successfully", [
                            'output' => substr($process->getOutput(), 0, 500),
                        ]);
                    } else {
                        Log::error("[EXTRACTION] Python script failed", [
                            'exit_code' => $process->getExitCode(),
                            'error' => $process->getErrorOutput(),
                            'output' => $process->getOutput(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("[EXTRACTION] Exception during Python execution: " . $e->getMessage());
                }

                // Reload document to get Python script updates
                $document->refresh();
                Log::info("[EXTRACTION] Document refreshed", [
                    'extracted_text_length' => strlen($document->extracted_text ?? ''),
                    'processing_status' => $document->processing_status,
                ]);

                // Check if extraction was successful (either by script or this job)
                if (!empty($document->extracted_text)) {
                    // Step 2.1: Anonymize extracted text automatically (if not already done)
                    if (!$document->has_sensitive_data && !$document->anonymization_detections) {
                        Log::info("Anonymizing document {$document->id}");
                        $anonymizationService = app(AnonymizationService::class);
                        $anonymizationResult = $anonymizationService->anonymizeDocument($document->extracted_text);

                        // Store anonymization results
                        $document->update([
                            'anonymization_detections' => json_encode($anonymizationResult['detections']),
                            'has_sensitive_data' => $anonymizationResult['has_sensitive_data'],
                            'detections_count' => count($anonymizationResult['detections']),
                        ]);

                        Log::info("Document anonymized for document {$document->id}", [
                            'has_sensitive_data' => $anonymizationResult['has_sensitive_data'],
                            'detections_count' => count($anonymizationResult['detections']),
                        ]);
                    }

                    // Step 3: Generate embeddings and upsert to Pinecone
                    Log::info("Starting Pinecone indexing for document {$document->id}");
                    $success = $ragService->indexDocument($document);

                    if ($success) {
                        Log::info("Document {$document->id} successfully indexed in Pinecone");
                    } else {
                        Log::warning("Failed to index document {$document->id} in Pinecone, but document will be marked as completed");
                    }
                } else {
                    // No text extracted
                    Log::warning("No text extracted from document {$document->id}. File type: {$document->mime_type}");
                    
                    $document->update([
                        'extracted_text' => null,
                        'extracted_text_length' => 0,
                        'processing_error' => "No text content found. This may be an image, unsupported format, or empty document.",
                    ]);
                }
            } else {
                // Text already extracted - just ensure it's indexed in Pinecone
                Log::info("Document {$document->id} already has extracted text. Ensuring Pinecone indexing...");
                $success = $ragService->indexDocument($document);
                
                if ($success) {
                    Log::info("Document {$document->id} indexed in Pinecone");
                } else {
                    Log::warning("Failed to index document {$document->id} in Pinecone");
                }
            }

            // Step 4: Always mark as completed (even without text)
            $document->update([
                'processing_status' => 'completed',
                'processed_at' => now(),
            ]);
            
            Log::info("Document {$document->id} processing completed");

        } catch (\Exception $e) {
            Log::error("Error processing document {$document->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $document->update([
                'processing_status' => 'failed',
                'processing_error' => $e->getMessage(),
            ]);

            // Re-throw to trigger job retry mechanism
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessDocumentForRAG job failed permanently for document {$this->documentId}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $document = SubmittedDocument::find($this->documentId);
        if ($document) {
            $document->update([
                'processing_status' => 'failed',
                'processing_error' => 'Job failed after maximum retries: ' . $exception->getMessage(),
            ]);
        }
    }
}
