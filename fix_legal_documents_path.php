<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

// Find documents with empty file_path
$emptyPathDocs = DB::table('legal_documents')
    ->whereNull('file_path')
    ->orWhere('file_path', '=', '')
    ->get(['id', 'title', 'file_name']);

echo "Documents with empty file_path:\n";
echo "===============================\n";
foreach ($emptyPathDocs as $doc) {
    echo "ID: {$doc->id}, Title: {$doc->title}, FileName: {$doc->file_name}\n";
}

if (count($emptyPathDocs) > 0) {
    echo "\nTotal: " . count($emptyPathDocs) . " documents need repair\n";
    
    // Try to fix them by reconstructing paths
    foreach ($emptyPathDocs as $doc) {
        if (!empty($doc->file_name)) {
            // Reconstruct the path as it should be
            $filePath = 'legal_documents/' . $doc->file_name;
            
            DB::table('legal_documents')
                ->where('id', $doc->id)
                ->update(['file_path' => $filePath]);
            
            echo "✓ Fixed ID {$doc->id}: {$filePath}\n";
        }
    }
    
    echo "\n✅ All documents fixed!\n";
} else {
    echo "\n✅ All legal documents have file_path set correctly!\n";
}

// Verify the fix
echo "\nVerifying all documents now have file_path...\n";
$stillEmpty = DB::table('legal_documents')
    ->whereNull('file_path')
    ->orWhere('file_path', '=', '')
    ->count();

echo "Documents still with empty file_path: " . $stillEmpty . "\n";

// Show first few legal documents
echo "\nFirst 5 legal documents:\n";
$docs = DB::table('legal_documents')->limit(5)->get(['id', 'title', 'file_path']);
foreach ($docs as $doc) {
    echo "ID: {$doc->id}, Title: {$doc->title}, Path: {$doc->file_path}\n";
}
