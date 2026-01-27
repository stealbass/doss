<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TEST EXTRACTION TEXTE PDF ===\n\n";

// Récupérer le document ID 12
$document = \App\Models\SubmittedDocument::find(12);

if (!$document) {
    echo "❌ Document ID 12 not found\n";
    exit(1);
}

echo "✅ Document trouvé:\n";
echo "   ID: {$document->id}\n";
echo "   Filename: {$document->original_filename}\n";
echo "   Storage Path: {$document->storage_path}\n";
echo "   File Size: {$document->file_size} bytes\n";
echo "   MIME Type: {$document->mime_type}\n\n";

// Vérifier si le fichier existe dans le storage
$storagePath = $document->storage_path;
echo "🔍 Checking if file exists in storage...\n";

if (!Storage::disk('cloudflare')->exists($storagePath)) {
    echo "❌ File does NOT exist in Cloudflare R2 storage!\n";
    echo "   Path: {$storagePath}\n";
    exit(1);
}

echo "✅ File exists in Cloudflare R2\n\n";

// Télécharger le fichier temporairement
echo "📥 Downloading file from R2...\n";
$tempPath = sys_get_temp_dir() . '/test_' . basename($storagePath);
$fileContent = Storage::disk('cloudflare')->get($storagePath);
file_put_contents($tempPath, $fileContent);

echo "✅ Downloaded to: {$tempPath}\n";
echo "   File size: " . filesize($tempPath) . " bytes\n\n";

// Tenter l'extraction avec PdfParser
echo "🔧 Attempting text extraction with PdfParser...\n";

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($tempPath);
    
    $text = $pdf->getText();
    $textLength = strlen(trim($text));
    
    if ($textLength > 0) {
        echo "✅ SUCCESS! Extracted {$textLength} characters\n\n";
        echo "📄 First 500 characters:\n";
        echo str_repeat("=", 50) . "\n";
        echo substr($text, 0, 500) . "\n";
        echo str_repeat("=", 50) . "\n\n";
        
        echo "💾 Updating document in database...\n";
        $document->update([
            'extracted_text' => $text,
            'extracted_text_length' => $textLength,
            'processing_status' => 'completed',
            'processed_at' => now(),
            'processing_error' => null,
        ]);
        echo "✅ Document updated successfully!\n";
        
    } else {
        echo "⚠️ PDF parsed but NO TEXT extracted\n";
        echo "   This is likely a scanned PDF (image-only)\n";
        echo "   OCR would be needed to extract text\n";
        
        // Vérifier les métadonnées du PDF
        $details = $pdf->getDetails();
        echo "\n📋 PDF Metadata:\n";
        foreach ($details as $key => $value) {
            echo "   {$key}: {$value}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR during extraction:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "\n   Stack trace:\n";
    echo "   " . $e->getTraceAsString() . "\n";
}

// Cleanup
unlink($tempPath);
echo "\n🧹 Temporary file cleaned up\n";
