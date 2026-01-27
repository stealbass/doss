<?php

namespace App\Console\Commands;

use App\Jobs\ProcessLegalDocumentForRAG;
use App\Jobs\ProcessTemplateForRAG;
use App\Jobs\ProcessFiscalResourceForRAG;
use App\Models\LegalDocument;
use App\Models\DocumentTemplate;
use App\Models\FiscalSocialResource;
use Illuminate\Console\Command;

class ExtractLibraryLargeFast extends Command
{
    protected $signature = 'rag:extract-large {--source=all : legal|template|fiscal|all} {--batch=10 : Job batch size} {--sync : Execute synchronously (blocking)} {--no-index : Extract only, skip Pinecone indexing}';
    protected $description = 'Optimized extraction for large documents (handles documents >200MB)';

    public function handle(): int
    {
        $source = $this->option('source');
        $batch_size = (int)$this->option('batch') ?: 10;
        $sync = $this->option('sync');
        $no_index = $this->option('no-index');

        $sources = $source === 'all' ? ['legal', 'template', 'fiscal'] : [$source];

        foreach ($sources as $src) {
            match ($src) {
                'legal' => $this->processLargeBatch($src, LegalDocument::class, ProcessLegalDocumentForRAG::class, $batch_size, $sync, $no_index),
                'template' => $this->processLargeBatch($src, DocumentTemplate::class, ProcessTemplateForRAG::class, $batch_size, $sync, $no_index),
                'fiscal' => $this->processLargeBatch($src, FiscalSocialResource::class, ProcessFiscalResourceForRAG::class, $batch_size, $sync, $no_index),
                default => $this->error("Source inconnue: {$src}"),
            };
        }

        $this->info("\n✅ Extraction jobs queued/executed");
        return self::SUCCESS;
    }

    private function processLargeBatch(
        string $source,
        string $modelClass,
        string $jobClass,
        int $batch_size,
        bool $sync,
        bool $no_index
    ): void {
        $unprocessed = $modelClass::query()
            ->where(function ($q) {
                $q->whereNull('extracted_text')->orWhere('extracted_text', '');
            })
            ->get();

        if ($unprocessed->isEmpty()) {
            $this->info("$source: No documents to process");
            return;
        }

        $this->info("\n📦 $source: " . $unprocessed->count() . " documents");
        
        $bar = $this->output->createProgressBar($unprocessed->count());
        $dispatched = 0;

        foreach ($unprocessed as $doc) {
            if ($sync) {
                // Synchronous = blocking (test mode)
                $jobClass::dispatchSync($doc->id);
                $this->info("✅ Processed: {$doc->file_name}");
            } else {
                // Asynchronous = queue
                $jobClass::dispatch($doc->id);
                $dispatched++;

                if ($dispatched % $batch_size === 0) {
                    $bar->advance($batch_size);
                    sleep(1); // Pause to avoid overwhelming queue
                }
            }
        }

        $bar->finish();
        $this->newLine();
        
        if (!$sync) {
            $this->info("  Queued: $dispatched jobs");
            $this->info("  Run: php artisan queue:work --queue=rag,default --timeout=1800 --stop-when-empty");
        }
    }
}
