<?php

namespace App\Services;

use App\Models\SubmittedDocument;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Synchronous Document Extraction Service
 * 
 * Extracts text from documents immediately (blocking call)
 * Used during upload to provide instant results to users
 */
class SyncDocumentExtractionService
{
    /**
     * Extract text from document synchronously
     * This is called immediately during upload, so users can query the document right away
     * 
     * @param SubmittedDocument $document
     * @return bool True if extraction was successful
     */
    public function extractText(SubmittedDocument $document): bool
    {
        try {
            Log::info("🔄 [SYNC EXTRACT] Starting synchronous extraction for document {$document->id}", [
                'filename' => $document->original_filename,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
            ]);

            // Mark as processing
            $document->update(['processing_status' => 'processing']);

            $scriptPath = base_path('scripts/extract_documents.py');
            $pythonPath = '/home/threesixty/yyy/Dossy/python-packages:/home/threesixty/yyy/Dossy/.local/lib/python3.7/site-packages';

            // Try multiple Python commands (python3 first, then python)
            $commands = [
                ['bash', '-c', "PYTHONPATH={$pythonPath} python3 {$scriptPath} --document-id {$document->id}"],
                ['bash', '-c', "PYTHONPATH={$pythonPath} python {$scriptPath} --document-id {$document->id}"],
            ];

            foreach ($commands as $cmd) {
                $process = new Process($cmd);
                $process->setTimeout(300); // 5 minutes timeout for sync extraction
                $process->run();

                if ($process->isSuccessful()) {
                    // Reload document to get extracted text from Python script
                    $document->refresh();

                    if (!empty($document->extracted_text)) {
                        Log::info("✅ [SYNC EXTRACT] Text extraction successful for document {$document->id}", [
                            'text_length' => strlen($document->extracted_text),
                            'pages' => $document->extracted_text_length,
                        ]);
                        return true;
                    }
                } else {
                    $errorOutput = $process->getErrorOutput();
                    Log::warning('🟡 [SYNC EXTRACT] Python script attempt failed', [
                        'document_id' => $document->id,
                        'command' => implode(' ', $cmd),
                        'error' => $errorOutput,
                        'output' => $process->getOutput(),
                    ]);
                }
            }

            // If we get here, extraction failed
            Log::error("❌ [SYNC EXTRACT] Text extraction failed for document {$document->id}", [
                'filename' => $document->original_filename,
                'mime_type' => $document->mime_type,
            ]);

            $document->update([
                'processing_status' => 'failed',
                'processing_error' => 'Text extraction failed during upload. Document may be unsupported or corrupted.',
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error("❌ [SYNC EXTRACT] Exception during extraction for document {$document->id}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $document->update([
                'processing_status' => 'failed',
                'processing_error' => 'Exception: ' . $e->getMessage(),
            ]);

            return false;
        }
    }
}
