<?php

namespace App\Jobs;

use App\Models\DocumentTemplate;
use App\Services\AdvancedRagService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ProcessTemplateForRAG implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900; // 15 minutes for large/OCR files

    public function __construct(private int $templateId)
    {
    }

    public function handle(AdvancedRagService $ragService): void
    {
        $template = DocumentTemplate::find($this->templateId);
        if (!$template) {
            Log::error("DocumentTemplate {$this->templateId} introuvable pour RAG");
            return;
        }

        try {
            Log::info("[TEMPLATE RAG] Extraction + indexation", [
                'id' => $template->id,
                'file_name' => $template->file_name,
                'file_path' => $template->file_path,
                'size' => $template->file_size,
            ]);

            $this->runPythonExtractor($template->id, 'template');
            $template->refresh();

            if (!empty($template->extracted_text)) {
                $indexed = $ragService->indexTemplate($template);
                Log::info("[TEMPLATE RAG] Indexation Pinecone", ['id' => $template->id, 'indexed' => $indexed]);
            } else {
                Log::warning("[TEMPLATE RAG] Aucun texte extrait", ['id' => $template->id]);
            }
        } catch (\Exception $e) {
            Log::error("[TEMPLATE RAG] Erreur: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
            Log::info("[TEMPLATE RAG] Python command #{$cmdIndex}", [
                'command' => implode(' ', $cmd),
                'exit_code' => $exitCode,
                'stdout_length' => strlen($stdout),
                'stderr_length' => strlen($stderr),
            ]);
            
            if (!empty($stdout)) {
                Log::info("[TEMPLATE RAG] Python STDOUT", ['output' => substr($stdout, 0, 1000)]);
            }
            
            if (!empty($stderr)) {
                Log::warning("[TEMPLATE RAG] Python STDERR", ['error' => substr($stderr, 0, 5000)]);
            }

            if ($process->isSuccessful()) {
                Log::info("[TEMPLATE RAG] Python extraction réussie");
                return;
            }

            $lastError = $stderr ?: $stdout;
            $lastOutput = $stdout;
        }

        Log::error("[TEMPLATE RAG] Toutes les commandes Python ont échoué", [
            'last_error' => substr($lastError, 0, 500),
            'last_output' => substr($lastOutput, 0, 500),
        ]);
        
        throw new \RuntimeException('Extraction Python échouée: ' . $lastError);
    }
}
