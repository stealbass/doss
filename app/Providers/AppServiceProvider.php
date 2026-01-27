<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\LegalDocument;
use App\Models\FiscalSocialResource;
use App\Models\DocumentTemplate;
use App\Observers\LegalDocumentObserver;
use App\Observers\FiscalSocialResourceObserver;
use App\Observers\DocumentTemplateObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers for automatic Pinecone reindexing
        LegalDocument::observe(LegalDocumentObserver::class);
        FiscalSocialResource::observe(FiscalSocialResourceObserver::class);
        DocumentTemplate::observe(DocumentTemplateObserver::class);
    }
}
