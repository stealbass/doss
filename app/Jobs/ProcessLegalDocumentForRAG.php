<?php

namespace App\Jobs;

use App\Models\LegalDocument;
use App\Services\AdvancedRagService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ProcessLegalDocumentForRAG implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900; // 15 minutes for large/OCR files

    public function __construct(private int $documentId)
    {
    }

    public function handle(AdvancedRagService $ragService): void
    {
        $document = LegalDocument::find($this->documentId);
        if (!$document) {
            Log::error("LegalDocument {$this->documentId} introuvable pour RAG");
            return;
        }

        try {
            Log::info("[LEGAL RAG] Extraction + indexation", [
                'id' => $document->id,
                'file_name' => $document->file_name,
                'file_path' => $document->file_path,
                'size' => $document->file_size,
            ]);

            $this->runPythonExtractor($document->id, 'legal');
            $document->refresh();

            if (!empty($document->extracted_text)) {
                $indexed = $ragService->indexLegalDocument($document);
                Log::info("[LEGAL RAG] Indexation Pinecone", ['id' => $document->id, 'indexed' => $indexed]);
            } else {
                Log::warning("[LEGAL RAG] Aucun texte extrait", ['id' => $document->id]);
            }
        } catch (\Exception $e) {
            Log::error("[LEGAL RAG] Erreur: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    private function runPythonExtractor(int $documentId, string $source): void
    {
        $pythonPath = '/home/threesixty/yyy/Dossy/python-packages:/home/threesixty/yyy/Dossy/.local/lib/python3.7/site-packages';
        $scriptPath = base_path('scripts/extract_documents_improved.py');

        $commands = [
            ['bash', '-c', "PYTHONPATH={$pythonPath} python3 {$scriptPath} --source={$source} --document-id {$documentId}"],
            ['bash', '-c', "PYTHONPATH={$pythonPath} python {$scriptPath} --source={$source} --document-id {$documentId}"],
        ];

        $lastError = '';
        $lastOutput = '';
        $cmdIndex = 0;
        
        foreach ($commands as $cmd) {
            $cmdIndex++;
            $process = new Process($cmd);
            $process->setTimeout(900); // Long timeout for big files
            $process->run();

            $stdout = $process->getOutput();
            $stderr = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            // Log détaillé pour debug
            Log::info("[LEGAL RAG] Python command #{$cmdIndex}", [
                'command' => implode(' ', $cmd),
                'exit_code' => $exitCode,
                'stdout_length' => strlen($stdout),
                'stderr_length' => strlen($stderr),
            ]);
            
            if (!empty($stdout)) {
                Log::info("[LEGAL RAG] Python STDOUT", ['output' => substr($stdout, 0, 1000)]);
            }
            
            if (!empty($stderr)) {
                Log::warning("[LEGAL RAG] Python STDERR", ['error' => substr($stderr, 0, 5000)]);
            }

            if ($process->isSuccessful()) {
                Log::info("[LEGAL RAG] Python extraction réussie");
                return;
            }

            $lastError = $stderr ?: $stdout;
            $lastOutput = $stdout;
        }

        Log::error("[LEGAL RAG] Toutes les commandes Python ont échoué", [
            'last_error' => substr($lastError, 0, 500),
            'last_output' => substr($lastOutput, 0, 500),
        ]);
        
        throw new \RuntimeException('Extraction Python échouée: ' . $lastError);
    }
}
