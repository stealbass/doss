#!/bin/bash

# Quick Test Script for Document Extraction
# Run this after deploying the fixes to verify everything works

echo "=========================================="
echo "DOCUMENT EXTRACTION QUICK TEST"
echo "=========================================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ artisan not found. Run this from the Laravel root directory"
    exit 1
fi

echo ""
echo "1️⃣  Checking PHP libraries..."
php -r "
    echo 'PhpWord: ' . (class_exists('PhpOffice\PhpWord\PhpWord') ? '✅' : '❌') . PHP_EOL;
    echo 'PhpSpreadsheet: ' . (class_exists('PhpOffice\PhpSpreadsheet\IOFactory') ? '✅' : '❌') . PHP_EOL;
    echo 'Smalot PdfParser: ' . (class_exists('Smalot\PdfParser\Parser') ? '✅' : '❌') . PHP_EOL;
"

echo ""
echo "2️⃣  Checking R2 Storage..."
php artisan tinker --execute="
    try {
        \$files = Storage::disk('r2')->listContents('documents/', true);
        \$count = count(iterator_to_array(\$files));
        echo 'R2 Documents folder: ✅ (' . \$count . ' files)' . PHP_EOL;
    } catch (\Exception \$e) {
        echo 'R2 Error: ❌ ' . \$e->getMessage() . PHP_EOL;
    }
"

echo ""
echo "3️⃣  Checking Database..."
php artisan tinker --execute="
    try {
        \$count = App\Models\SubmittedDocument::count();
        \$pending = App\Models\SubmittedDocument::where('processing_status', '!=', 'completed')->count();
        echo 'Total documents: ' . \$count . ' | Pending extraction: ' . \$pending . PHP_EOL;
    } catch (\Exception \$e) {
        echo 'DB Error: ❌' . PHP_EOL;
    }
"

echo ""
echo "4️⃣  Running full extraction test..."
php test_document_extraction_complete.php

echo ""
echo "=========================================="
echo "✅ TEST COMPLETE"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Upload a test PDF/DOCX/XLSX file"
echo "2. Check 'extracted_text' column in submitted_documents table"
echo "3. Verify document appears in Flutter 'Documents' screen"
echo "4. Test asking a question about the document in Chat"
