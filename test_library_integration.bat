@echo off
REM Test de l'intégration des bibliothèques juridiques et fiscales
echo ============================================
echo Test d'intégration - Bibliothèques RAG
echo ============================================
echo.

echo [1/4] Vérification des documents extraits...
php artisan tinker --execute="
echo 'Documents juridiques extraits: ' . \App\Models\LegalDocument::whereNotNull('extracted_text')->count();
echo 'Modèles extraits: ' . \App\Models\DocumentTemplate::whereNotNull('extracted_text')->count();
echo 'Ressources fiscales extraites: ' . \App\Models\FiscalSocialResource::whereNotNull('extracted_text')->count();
"
echo.

echo [2/4] Test de recherche sémantique dans les bibliothèques...
php artisan tinker --execute="
\$rag = app(\App\Services\AdvancedRagService::class);
\$results = \$rag->searchLibraries('impôt sur les sociétés', 'Sénégal', ['legal', 'fiscal'], 3);
echo 'Résultats trouvés: ' . count(\$results);
foreach (\$results as \$source => \$items) {
    echo \"Source: {\$source} - \" . count(\$items) . \" résultats\";
}
"
echo.

echo [3/4] Test de contexte RAG pour le chat...
php artisan tinker --execute="
\$rag = app(\App\Services\AdvancedRagService::class);
\$context = \$rag->getLibraryContext('TVA Sénégal', 'Sénégal', 1500);
echo 'Contexte généré: ' . strlen(\$context['context']) . ' caractères';
echo 'Sources: ' . count(\$context['sources']);
"
echo.

echo [4/4] Vérification Pinecone (configuration)...
php artisan tinker --execute="
\$settings = \App\Models\MobileAppSetting::first();
if (\$settings) {
    echo 'Pinecone configuré: ' . (!empty(\$settings->pinecone_api_key) ? 'OUI' : 'NON');
    echo 'Index Pinecone: ' . (\$settings->pinecone_index_name ?? 'N/A');
} else {
    echo 'Configuration Pinecone: Vérifier .env';
}
"
echo.

echo ============================================
echo Test terminé!
echo ============================================
echo.
echo Prochaines étapes:
echo 1. Si extraction OK mais pas de résultats Pinecone : relancer l'indexation
echo    php artisan rag:extract-library --source=all --limit=100
echo.
echo 2. Tester via l'app mobile avec une question fiscale ou juridique
echo.
pause
