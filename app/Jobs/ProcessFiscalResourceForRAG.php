<?php

namespace App\Jobs;

use App\Models\FiscalSocialResource;
use App\Services\AdvancedRagService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ProcessFiscalResourceForRAG implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900; // 15 minutes for large/OCR files

    public function __construct(private int $resourceId)
    {
    }

    public function handle(AdvancedRagService $ragService): void
    {
        $resource = FiscalSocialResource::find($this->resourceId);
        if (!$resource) {
            Log::error("FiscalSocialResource {$this->resourceId} introuvable pour RAG");
            return;
        }

        try {
            Log::info("[FISCAL RAG] Extraction + indexation", [
                'id' => $resource->id,
                'file_name' => $resource->file_name,
                'file_path' => $resource->file_path,
                'size' => $resource->file_size,
            ]);

            $this->runPythonExtractor($resource->id, 'fiscal');
            $resource->refresh();

            if (!empty($resource->extracted_text)) {
                $indexed = $ragService->indexFiscalResource($resource);
                Log::info("[FISCAL RAG] Indexation Pinecone", ['id' => $resource->id, 'indexed' => $indexed]);
            } else {
                Log::warning("[FISCAL RAG] Aucun texte extrait", ['id' => $resource->id]);
            }
        } catch (\Exception $e) {
            Log::error("[FISCAL RAG] Erreur: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
            $process->setTimeout(900);
            $process->run();

            $stdout = $process->getOutput();
            $stderr = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            // Log détaillé pour debug
            Log::info("[FISCAL RAG] Python command #{$cmdIndex}", [
                'command' => implode(' ', $cmd),
                'exit_code' => $exitCode,
                'stdout_length' => strlen($stdout),
                'stderr_length' => strlen($stderr),
            ]);
            
            if (!empty($stdout)) {
                Log::info("[FISCAL RAG] Python STDOUT", ['output' => substr($stdout, 0, 1000)]);
            }
            
            if (!empty($stderr)) {
                Log::warning("[FISCAL RAG] Python STDERR", ['error' => substr($stderr, 0, 5000)]);
            }

            if ($process->isSuccessful()) {
                Log::info("[FISCAL RAG] Python extraction réussie");
                return;
            }

            $lastError = $stderr ?: $stdout;
            $lastOutput = $stdout;
        }

        Log::error("[FISCAL RAG] Toutes les commandes Python ont échoué", [
            'last_error' => substr($lastError, 0, 500),
            'last_output' => substr($lastOutput, 0, 500),
        ]);
        
        throw new \RuntimeException('Extraction Python échouée: ' . $lastError);
    }
}
