<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

/**
 * Service pour extraire le contenu des documents pour l'IA
 * Alternative à l'extraction via jobs de queue
 */
class DocumentContentExtractor
{
    /**
     * Extraire le contenu d'un document à la volée pour le chat
     * 
     * @param \App\Models\SubmittedDocument $document
     * @return string Contenu textuel du document
     */
    public function extractForChat($document): string
    {
        try {
            Log::info("🔍 Extraction à la volée du document {$document->id}", [
                'filename' => $document->file_name,
                'mime_type' => $document->mime_type,
            ]);

            // Si déjà extrait, retourner directement
            if (!empty($document->extracted_text)) {
                Log::info("✅ Texte déjà extrait");
                return $document->extracted_text;
            }

            // Récupérer les paramètres de storage
            $settings = \App\Models\Utility::settings();
            $storageSetting = $settings['storage_setting'] ?? 'local';
            
            if ($storageSetting === 'r2') {
                config([
                    'filesystems.disks.r2.key' => $settings['r2_key'],
                    'filesystems.disks.r2.secret' => $settings['r2_secret'],
                    'filesystems.disks.r2.region' => $settings['r2_region'] ?? 'auto',
                    'filesystems.disks.r2.bucket' => $settings['r2_bucket'],
                    'filesystems.disks.r2.endpoint' => $settings['r2_endpoint'],
                    'filesystems.disks.r2.url' => $settings['r2_url'],
                ]);
                $disk = 'r2';
            } else {
                $disk = 'public';
            }

            // Vérifier si le fichier existe
            if (!Storage::disk($disk)->exists($document->storage_path)) {
                Log::error("❌ Fichier introuvable dans le storage");
                return "⚠️ Fichier introuvable sur le serveur.";
            }

            // Télécharger le fichier en mémoire
            $fileContent = Storage::disk($disk)->get($document->storage_path);
            $tempPath = sys_get_temp_dir() . '/' . uniqid('doc_') . '_' . basename($document->stored_filename);
            file_put_contents($tempPath, $fileContent);

            Log::info("📥 Fichier téléchargé temporairement", [
                'temp_path' => $tempPath,
                'size' => filesize($tempPath),
            ]);

            // Extraire selon le type
            $text = $this->extractByMimeType($tempPath, $document->mime_type);

            // Nettoyer le fichier temporaire
            unlink($tempPath);

            if (empty($text)) {
                Log::warning("⚠️ Aucun texte extrait");
                return "⚠️ Impossible d'extraire le texte de ce document. Il pourrait être une image ou un document scanné.";
            }

            Log::info("✅ Texte extrait avec succès", [
                'length' => strlen($text),
            ]);

            // Optionnel : sauvegarder pour les prochaines fois
            try {
                $document->update([
                    'extracted_text' => $text,
                    'extracted_text_length' => strlen($text),
                ]);
                Log::info("💾 Texte sauvegardé en BDD");
            } catch (\Exception $e) {
                Log::warning("⚠️ Impossible de sauvegarder le texte", [
                    'error' => $e->getMessage(),
                ]);
            }

            return $text;

        } catch (\Exception $e) {
            Log::error("❌ Erreur extraction à la volée", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return "⚠️ Erreur lors de la lecture du document : " . $e->getMessage();
        }
    }

    /**
     * Extraire le texte selon le type MIME
     */
    private function extractByMimeType(string $filePath, string $mimeType): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        // PDF
        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            return $this->extractFromPdf($filePath);
        }

        // Word DOCX
        if (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword'
        ]) || in_array($extension, ['docx', 'doc'])) {
            return $this->extractFromWord($filePath);
        }

        // Excel
        if (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel'
        ]) || in_array($extension, ['xlsx', 'xls'])) {
            return $this->extractFromExcel($filePath);
        }

        // Texte brut
        if ($mimeType === 'text/plain' || $extension === 'txt') {
            return file_get_contents($filePath);
        }

        Log::warning("⚠️ Format de fichier non supporté", [
            'extension' => $extension,
            'mime_type' => $mimeType,
        ]);
        return '';
    }

    /**
     * Extraire texte d'un PDF - PRIORITÉ AUX COMMANDES SYSTÈME
     */
    private function extractFromPdf(string $path): string
    {
        // Méthode 1 PRIORITAIRE : Utiliser pdftotext système Linux
        if (function_exists('shell_exec')) {
            try {
                $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
                $output = shell_exec("pdftotext " . escapeshellarg($path) . " " . escapeshellarg($tempFile) . " 2>/dev/null");
                
                if (file_exists($tempFile)) {
                    $text = file_get_contents($tempFile);
                    unlink($tempFile);
                    
                    if (!empty(trim($text))) {
                        Log::info("✅ PDF extrait via pdftotext système");
                        return trim($text);
                    }
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ pdftotext system command failed", ['error' => $e->getMessage()]);
            }
        }

        // Méthode 2 FALLBACK : Utiliser Smalot PHP (si disponible)
        if (class_exists('Smalot\PdfParser\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($path);
                $text = $pdf->getText();
                $extracted = trim(preg_replace('/\s+/', ' ', $text ?? ''));
                if (!empty($extracted)) {
                    Log::info("✅ PDF extrait via Smalot");
                    return $extracted;
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ Smalot extraction failed", ['error' => $e->getMessage()]);
            }
        }

        Log::warning("⚠️ PDF extraction non disponible - pdftotext et packages PHP manquants");
        return '';
    }

    /**
     * Extraire texte d'un Word - PRIORITÉ AUX COMMANDES SYSTÈME
     */
    private function extractFromWord(string $path): string
    {
        // Méthode 1 PRIORITAIRE : Utiliser libreoffice système Linux
        if (function_exists('shell_exec')) {
            try {
                $tempFile = tempnam(sys_get_temp_dir(), 'word_');
                $tempPdf = $tempFile . '.pdf';
                
                // Convertir DOCX en PDF puis en texte
                shell_exec("libreoffice --headless --convert-to pdf --outdir " . escapeshellarg(dirname($tempFile)) . " " . escapeshellarg($path) . " 2>/dev/null");
                
                if (file_exists($tempPdf)) {
                    $textFile = $tempFile . '.txt';
                    shell_exec("pdftotext " . escapeshellarg($tempPdf) . " " . escapeshellarg($textFile) . " 2>/dev/null");
                    
                    if (file_exists($textFile)) {
                        $text = file_get_contents($textFile);
                        
                        // Cleanup
                        @unlink($textFile);
                        @unlink($tempPdf);
                        @unlink($tempFile);
                        
                        if (!empty(trim($text))) {
                            Log::info("✅ Word extrait via libreoffice + pdftotext");
                            return trim($text);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ libreoffice system command failed", ['error' => $e->getMessage()]);
            }
        }

        // Méthode 2 FALLBACK : Utiliser PhpOffice\PhpWord (si disponible)
        if (class_exists('PhpOffice\PhpWord\IOFactory')) {
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
                $text = '';

                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        } elseif (method_exists($element, 'getElements')) {
                            foreach ($element->getElements() as $childElement) {
                                if (method_exists($childElement, 'getText')) {
                                    $text .= $childElement->getText();
                                }
                            }
                            $text .= "\n";
                        }
                    }
                }

                $extracted = trim(preg_replace('/\s+/', ' ', $text ?? ''));
                if (!empty($extracted)) {
                    Log::info("✅ Word extrait via PhpWord");
                    return $extracted;
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ PhpWord extraction failed", ['error' => $e->getMessage()]);
            }
        }

        Log::warning("⚠️ Word extraction non disponible - libreoffice et packages PHP manquants");
        return '';
    }
                            Log::info("✅ Word extrait via libreoffice");
                            return trim($text);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ libreoffice extraction failed", ['error' => $e->getMessage()]);
            }
        }

        Log::warning("⚠️ Word extraction non disponible - PhpWord et libreoffice non disponibles");
        return '';
    }

    /**
     * Extraire texte d'un Excel - avec fallback système Linux
     */
    private function extractFromExcel(string $path): string
    {
        // Méthode 1 : Utiliser PhpOffice\PhpSpreadsheet
        if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                $text = '';

                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $text .= "=== " . $sheet->getTitle() . " ===\n";
                    
                    foreach ($sheet->getRowIterator() as $row) {
                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false);
                        
                        $rowData = [];
                        foreach ($cellIterator as $cell) {
                            $rowData[] = $cell->getValue();
                        }
                        $text .= implode(' | ', $rowData) . "\n";
                    }
                }

                $extracted = trim($text);
                if (!empty($extracted)) {
                    Log::info("✅ Excel extrait via PhpSpreadsheet");
                    return $extracted;
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ PhpSpreadsheet extraction failed", ['error' => $e->getMessage()]);
            }
        }

        // Méthode 2 : Utiliser libreoffice système Linux
        if (function_exists('shell_exec')) {
            try {
                $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
                $tempPdf = $tempFile . '.pdf';
                
                shell_exec("libreoffice --headless --convert-to pdf --outdir " . escapeshellarg(dirname($tempFile)) . " " . escapeshellarg($path) . " 2>/dev/null");
                
                if (file_exists($tempPdf)) {
                    $textFile = $tempFile . '.txt';
                    shell_exec("pdftotext " . escapeshellarg($tempPdf) . " " . escapeshellarg($textFile) . " 2>/dev/null");
                    
                    if (file_exists($textFile)) {
                        $text = file_get_contents($textFile);
                        
                        // Cleanup
                        @unlink($textFile);
                        @unlink($tempPdf);
                        @unlink($tempFile);
                        
                        if (!empty($text)) {
                            Log::info("✅ Excel extrait via libreoffice");
                            return trim($text);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ libreoffice extraction failed", ['error' => $e->getMessage()]);
            }
        }

        Log::warning("⚠️ Excel extraction non disponible - PhpSpreadsheet et libreoffice non disponibles");
        return '';
    }
}
