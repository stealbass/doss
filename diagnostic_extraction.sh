#!/bin/bash

echo "==================================================="
echo "DIAGNOSTIC COMPLET - EXTRACTION DE DOCUMENTS"
echo "==================================================="
echo ""

cd /home/threesixty/yyy/Dossy/

echo "[ETAPE 1] Verification des packages installes"
echo "-----------------------------------------------------"
echo ""
echo "Verification PdfParser (PDF):"
if composer show smalot/pdfparser &> /dev/null; then
    echo "  ✅ INSTALLE"
else
    echo "  ❌ PAS INSTALLE"
fi

echo ""
echo "Verification PhpWord (Word):"
if composer show phpoffice/phpword &> /dev/null; then
    echo "  ✅ INSTALLE"
else
    echo "  ❌ PAS INSTALLE"
fi

echo ""
echo "Verification PhpSpreadsheet (Excel):"
if composer show phpoffice/phpspreadsheet &> /dev/null; then
    echo "  ✅ INSTALLE"
else
    echo "  ❌ PAS INSTALLE"
fi

echo ""
echo ""
echo "[ETAPE 2] Verification configuration Cloudflare R2"
echo "-----------------------------------------------------"
php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class); \$kernel->bootstrap(); \$settings = App\Models\Utility::settings(); echo 'Storage: ' . (\$settings['storage_setting'] ?? 'non defini') . PHP_EOL; echo 'R2 Bucket: ' . (\$settings['r2_bucket'] ?? 'non defini') . PHP_EOL; echo 'R2 Endpoint: ' . (\$settings['r2_endpoint'] ?? 'non defini') . PHP_EOL;"

echo ""
echo ""
echo "[ETAPE 3] Verification des documents dans la BDD"
echo "-----------------------------------------------------"
php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class); \$kernel->bootstrap(); \$docs = App\Models\SubmittedDocument::latest()->take(5)->get(['id', 'original_filename', 'processing_status', 'extracted_text_length']); foreach(\$docs as \$doc) { echo sprintf('ID: %3d | %s | Status: %s | Text Length: %d', \$doc->id, str_pad(substr(\$doc->original_filename, 0, 30), 30), str_pad(\$doc->processing_status, 10), \$doc->extracted_text_length ?? 0) . PHP_EOL; }"

echo ""
echo ""
echo "[ETAPE 4] Verification queue worker"
echo "-----------------------------------------------------"
echo "Verification si des jobs sont en attente:"
if php artisan queue:work --once --stop-when-empty 2> /dev/null; then
    echo "  ✅ Queue worker fonctionne"
else
    echo "  ⚠️ Aucun job en attente"
fi

echo ""
echo ""
echo "==================================================="
echo "DIAGNOSTIC TERMINE"
echo "==================================================="
echo ""
echo "ACTIONS RECOMMANDEES:"
echo ""
echo "1. Si packages manquants:"
echo "   > Executez: ./install_extraction_packages.sh"
echo ""
echo "2. Pour reessayer extraction d'un document:"
echo "   > php artisan document:retry-extraction [ID]"
echo "   > php artisan queue:work --once"
echo ""
echo "3. Pour voir les logs:"
echo "   > tail -f storage/logs/laravel.log"
echo ""
