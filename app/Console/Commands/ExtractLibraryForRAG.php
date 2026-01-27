<?php

namespace App\Console\Commands;

use App\Jobs\ProcessFiscalResourceForRAG;
use App\Jobs\ProcessLegalDocumentForRAG;
use App\Jobs\ProcessTemplateForRAG;
use App\Models\FiscalSocialResource;
use App\Models\LegalDocument;
use App\Models\DocumentTemplate;
use Illuminate\Console\Command;

class ExtractLibraryForRAG extends Command
{
    protected $signature = 'rag:extract-library {--source=all : legal|template|fiscal|all} {--id=} {--limit=50}';
    protected $description = 'Extrait le texte (OCR) et indexe la bibliothèque juridique/templates/ressources fiscales dans Pinecone';

    public function handle(): int
    {
        $source = $this->option('source');
        $documentId = $this->option('id');
        $limit = (int) $this->option('limit') ?: 50;

        $sources = $source === 'all' ? ['legal', 'template', 'fiscal'] : [$source];

        foreach ($sources as $src) {
            match ($src) {
                'legal' => $this->processLegal($documentId, $limit),
                'template' => $this->processTemplate($documentId, $limit),
                'fiscal' => $this->processFiscal($documentId, $limit),
                default => $this->error("Source inconnue: {$src}"),
            };
        }

        return self::SUCCESS;
    }

    private function processLegal($documentId, int $limit): void
    {
        $query = LegalDocument::query()
            ->where(function ($q) {
                $q->whereNull('extracted_text')->orWhere('extracted_text', '');
            });

        if ($documentId) {
            $query->where('id', $documentId);
        } else {
            $query->limit($limit);
        }

        $docs = $query->get();
        foreach ($docs as $doc) {
            ProcessLegalDocumentForRAG::dispatch($doc->id);
        }

        $this->info("Legal: jobs dispatchés: {$docs->count()}");
    }

    private function processTemplate($documentId, int $limit): void
    {
        $query = DocumentTemplate::query()
            ->where(function ($q) {
                $q->whereNull('extracted_text')->orWhere('extracted_text', '');
            });

        if ($documentId) {
            $query->where('id', $documentId);
        } else {
            $query->limit($limit);
        }

        $docs = $query->get();
        foreach ($docs as $doc) {
            ProcessTemplateForRAG::dispatch($doc->id);
        }

        $this->info("Templates: jobs dispatchés: {$docs->count()}");
    }

    private function processFiscal($documentId, int $limit): void
    {
        $query = FiscalSocialResource::query()
            ->where(function ($q) {
                $q->whereNull('extracted_text')->orWhere('extracted_text', '');
            });

        if ($documentId) {
            $query->where('id', $documentId);
        } else {
            $query->limit($limit);
        }

        $docs = $query->get();
        foreach ($docs as $doc) {
            ProcessFiscalResourceForRAG::dispatch($doc->id);
        }

        $this->info("Fiscal: jobs dispatchés: {$docs->count()}");
    }
}
