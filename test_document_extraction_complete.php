<?php
/**
 * Complete Document Extraction Test
 * Tests:
 * 1. Database connection
 * 2. R2 storage access
 * 3. PHP extraction libraries availability
 * 4. Document extraction process
 */

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\SubmittedDocument;
use App\Jobs\ProcessDocumentForRAG;

$output = [];

$output[] = "=====================================";
$output[] = "DOCUMENT EXTRACTION TEST SUITE";
$output[] = "=====================================\n";

// Test 1: Database connection
$output[] = "Test 1: Database Connection";
try {
    $docCount = SubmittedDocument::count();
    $output[] = "✅ Database connected. Total documents: {$docCount}";
} catch (\Exception $e) {
    $output[] = "❌ Database error: " . $e->getMessage();
}

// Test 2: R2 Storage access
$output[] = "\nTest 2: R2 Storage Connection";
try {
    $files = Storage::disk('r2')->listContents('documents/', true);
    $fileCount = count(iterator_to_array($files));
    $output[] = "✅ R2 Storage connected. Files in documents/: {$fileCount}";
} catch (\Exception $e) {
    $output[] = "❌ R2 Storage error: " . $e->getMessage();
}

// Test 3: Check PHP extraction libraries
$output[] = "\nTest 3: Available Extraction Libraries";
$output[] = "PhpOffice\\PhpWord: " . (class_exists('\PhpOffice\PhpWord\PhpWord') ? "✅" : "❌");
$output[] = "PhpOffice\\PhpSpreadsheet: " . (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory') ? "✅" : "❌");
$output[] = "Smalot\\PdfParser: " . (class_exists('\Smalot\PdfParser\Parser') ? "✅" : "❌");

// Test 4: Find a document that needs extraction
$output[] = "\nTest 4: Finding Documents for Extraction Test";
$testDoc = SubmittedDocument::where('processing_status', '!=', 'completed')
    ->where('extracted_text', null)
    ->first();

if ($testDoc) {
    $output[] = "Found test document: #{$testDoc->id} - {$testDoc->original_filename}";
    $output[] = "  Status: {$testDoc->processing_status}";
    $output[] = "  MIME: {$testDoc->mime_type}";
    $output[] = "  Storage path: {$testDoc->storage_path}";
    
    // Test 5: Run extraction
    $output[] = "\nTest 5: Running Extraction Job";
    try {
        ProcessDocumentForRAG::dispatchSync($testDoc->id);
        $output[] = "✅ Extraction job dispatched";
        
        // Reload document to check results
        $testDoc->refresh();
        $output[] = "  Processing status: {$testDoc->processing_status}";
        $output[] = "  Extracted text length: " . strlen($testDoc->extracted_text ?? '');
        
        if ($testDoc->extracted_text) {
            $output[] = "✅ EXTRACTION SUCCESSFUL";
            $preview = substr($testDoc->extracted_text, 0, 100) . "...";
            $output[] = "  Preview: {$preview}";
        } else {
            $output[] = "❌ EXTRACTION RETURNED EMPTY";
            if ($testDoc->processing_error) {
                $output[] = "  Error: {$testDoc->processing_error}";
            }
        }
        
    } catch (\Exception $e) {
        $output[] = "❌ Extraction failed: " . $e->getMessage();
    }
} else {
    $output[] = "⚠️  No documents found needing extraction";
}

// Output results
foreach ($output as $line) {
    echo $line . "\n";
}

// Also write to log
Log::info("Test Results:\n" . implode("\n", $output));
echo "\n\nTest completed. Check storage/logs/laravel.log for details.";
?>
