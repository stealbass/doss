@echo off
echo ===================================================
echo DIAGNOSTIC COMPLET - EXTRACTION DE DOCUMENTS
echo ===================================================
echo.

cd /d "c:\Users\Lenovo T580\Downloads\doss-genspark_ai_developer_5\doss-genspark_ai_developer"

echo [ETAPE 1] Verification des packages installes
echo -----------------------------------------------------
echo.
echo Verification PdfParser (PDF):
composer show smalot/pdfparser 2>NUL
if %ERRORLEVEL% NEQ 0 (
    echo   ❌ PAS INSTALLE
) else (
    echo   ✅ INSTALLE
)

echo.
echo Verification PhpWord (Word):
composer show phpoffice/phpword 2>NUL
if %ERRORLEVEL% NEQ 0 (
    echo   ❌ PAS INSTALLE
) else (
    echo   ✅ INSTALLE
)

echo.
echo Verification PhpSpreadsheet (Excel):
composer show phpoffice/phpspreadsheet 2>NUL
if %ERRORLEVEL% NEQ 0 (
    echo   ❌ PAS INSTALLE
) else (
    echo   ✅ INSTALLE
)

echo.
echo.
echo [ETAPE 2] Verification configuration Cloudflare R2
echo -----------------------------------------------------
C:\alwaysdata\php-8.3.9\php.exe -r "require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); $settings = App\Models\Utility::settings(); echo 'Storage: ' . ($settings['storage_setting'] ?? 'non defini') . PHP_EOL; echo 'R2 Bucket: ' . ($settings['r2_bucket'] ?? 'non defini') . PHP_EOL; echo 'R2 Endpoint: ' . ($settings['r2_endpoint'] ?? 'non defini') . PHP_EOL;"

echo.
echo.
echo [ETAPE 3] Verification des documents dans la BDD
echo -----------------------------------------------------
C:\alwaysdata\php-8.3.9\php.exe -r "require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); $docs = App\Models\SubmittedDocument::latest()->take(5)->get(['id', 'original_filename', 'processing_status', 'extracted_text_length']); foreach($docs as $doc) { echo sprintf('ID: %%3d | %%s | Status: %%s | Text Length: %%d', $doc->id, str_pad(substr($doc->original_filename, 0, 30), 30), str_pad($doc->processing_status, 10), $doc->extracted_text_length ?? 0) . PHP_EOL; }"

echo.
echo.
echo [ETAPE 4] Verification queue worker
echo -----------------------------------------------------
echo Verification si des jobs sont en attente:
C:\alwaysdata\php-8.3.9\php.exe artisan queue:work --once --stop-when-empty 2>NUL
if %ERRORLEVEL% EQU 0 (
    echo   ✅ Queue worker fonctionne
) else (
    echo   ⚠️ Aucun job en attente
)

echo.
echo.
echo ===================================================
echo DIAGNOSTIC TERMINE
echo ===================================================
echo.
echo ACTIONS RECOMMANDEES:
echo.
echo 1. Si packages manquants:
echo    ^> Executez: install_extraction_packages.bat
echo.
echo 2. Pour reessayer extraction d'un document:
echo    ^> php artisan document:retry-extraction [ID]
echo    ^> php artisan queue:work --once
echo.
echo 3. Pour voir les logs:
echo    ^> Ouvrez: storage/logs/laravel.log
echo.
pause
