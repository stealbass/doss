<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DiagnoseExtraction extends Command
{
    protected $signature = 'rag:diagnose-extraction {--python : Test Python dependencies}';
    protected $description = 'Diagnose extraction issues (file access, Python deps, R2 config)';

    public function handle(): int
    {
        $this->info("🔍 DIAGNOSTIC EXTRACTION\n");

        // 1. Check PHP environment
        $this->checkPhpEnvironment();

        // 2. Check file paths
        $this->checkFilePaths();

        // 3. Check R2 configuration
        $this->checkR2Config();

        // 4. Check database connection
        $this->checkDatabase();

        // 5. Check Python if requested
        if ($this->option('python')) {
            $this->checkPythonDependencies();
        }

        // 6. Check a sample document
        $this->checkSampleDocument();

        $this->info("\n✅ Diagnostic complete. Check logs in storage/logs/laravel.log");

        return self::SUCCESS;
    }

    private function checkPhpEnvironment(): void
    {
        $this->info("📋 PHP Environment:");
        $this->line("  PHP Version: " . phpversion());
        $this->line("  Max Execution: " . ini_get('max_execution_time') . "s");
        $this->line("  Memory Limit: " . ini_get('memory_limit'));
        $this->line("  Temp Dir: " . sys_get_temp_dir());
        
        $required_extensions = ['curl', 'json', 'mbstring'];
        foreach ($required_extensions as $ext) {
            $status = extension_loaded($ext) ? "✅" : "❌";
            $this->line("  $status Extension: $ext");
        }
    }

    private function checkFilePaths(): void
    {
        $this->info("\n📁 File Paths:");
        
        $paths = [
            'App Root' => base_path(),
            'Storage' => storage_path(),
            'Temp Dir' => storage_path('temp'),
            'Legal Docs' => storage_path('app/public/legal_documents'),
            'Submitted Docs' => storage_path('app/submitted_documents'),
        ];

        foreach ($paths as $label => $path) {
            $exists = is_dir($path) ? "✅" : (is_file($path) ? "📄" : "❌");
            $writable = is_writable($path) ? "W" : "R";
            $this->line("  $exists $label: $path ($writable)");
        }
    }

    private function checkR2Config(): void
    {
        $this->info("\n☁️ R2 Configuration:");
        
        $r2_key = env('R2_ACCESS_KEY_ID', '');
        $r2_secret = env('R2_SECRET_ACCESS_KEY', '');
        $r2_bucket = env('R2_BUCKET', '');
        $r2_endpoint = env('R2_ENDPOINT', '');
        $filesystem_disk = env('FILESYSTEM_DISK', 'local');

        $this->line("  Filesystem Disk: $filesystem_disk");
        $this->line("  R2 Key: " . (strlen($r2_key) > 0 ? "✅ Set" : "❌ Missing"));
        $this->line("  R2 Secret: " . (strlen($r2_secret) > 0 ? "✅ Set" : "❌ Missing"));
        $this->line("  R2 Bucket: " . (strlen($r2_bucket) > 0 ? "✅ $r2_bucket" : "❌ Missing"));
        $this->line("  R2 Endpoint: " . (strlen($r2_endpoint) > 0 ? "✅ Set" : "❌ Missing"));

        if ($filesystem_disk === 'r2') {
            if (!extension_loaded('curl')) {
                $this->error("  ❌ cURL extension required for R2 access!");
            }
        }
    }

    private function checkDatabase(): void
    {
        $this->info("\n🗄️  Database:");
        
        try {
            $pdo = \DB::connection()->getPdo();
            $this->line("  ✅ Connected: " . env('DB_HOST'));
            
            // Check table existence
            $tables = ['legal_documents', 'document_templates', 'fiscal_social_resources'];
            foreach ($tables as $table) {
                try {
                    \DB::table($table)->limit(1)->get();
                    $count = \DB::table($table)->count();
                    $null_count = \DB::table($table)->whereNull('extracted_text')->count();
                    $this->line("  ✅ $table: $count rows ($null_count without extraction)");
                } catch (\Exception $e) {
                    $this->error("  ❌ $table: Table error");
                }
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Connection failed: " . $e->getMessage());
        }
    }

    private function checkPythonDependencies(): void
    {
        $this->info("\n🐍 Python Dependencies:");
        
        $python_versions = ['python3', 'python'];
        $python_bin = null;

        foreach ($python_versions as $py) {
            exec("which $py 2>/dev/null", $output, $code);
            if ($code === 0 && !empty($output)) {
                $python_bin = $py;
                break;
            }
        }

        if (!$python_bin) {
            $this->error("  ❌ Python not found in PATH!");
            return;
        }

        $this->line("  ✅ Python: $python_bin");

        // Check Python packages
        $packages = [
            'PyPDF2',
            'pdfplumber',
            'python-docx',
            'openpyxl',
            'python-pptx',
            'pytesseract',
            'Pillow',
            'mysql-connector-python',
            'boto3',
        ];

        foreach ($packages as $pkg) {
            $import_name = strtolower(str_replace('-', '_', $pkg));
            if ($pkg === 'python-docx') $import_name = 'docx';
            if ($pkg === 'python-pptx') $import_name = 'pptx';
            if ($pkg === 'mysql-connector-python') $import_name = 'mysql.connector';

            exec("$python_bin -c \"import $import_name\" 2>&1", $output, $code);
            $status = ($code === 0) ? "✅" : "❌";
            $this->line("  $status $pkg");
        }

        // Check Tesseract (for OCR)
        exec("which tesseract 2>/dev/null", $output, $code);
        $status = ($code === 0) ? "✅" : "❌";
        $this->line("  $status Tesseract OCR");
    }

    private function checkSampleDocument(): void
    {
        $this->info("\n📄 Sample Document Check:");
        
        try {
            $doc = \DB::table('legal_documents')
                ->whereNull('extracted_text')
                ->orWhere('extracted_text', '')
                ->first();

            if (!$doc) {
                $this->info("  ℹ️  No unprocessed documents found");
                return;
            }

            $this->line("  Sample: ID {$doc->id} - {$doc->file_name}");
            $this->line("  File Path: {$doc->file_path}");
            $this->line("  File Size: " . ($doc->file_size ? number_format($doc->file_size / 1024 / 1024, 2) . "MB" : "Unknown"));

            // Try to locate the file
            $base_paths = [
                base_path("storage/app/public/{$doc->file_path}"),
                base_path("storage/app/{$doc->file_path}"),
                base_path("storage/uploads/{$doc->file_name}"),
            ];

            $found = false;
            foreach ($base_paths as $path) {
                if (file_exists($path)) {
                    $this->line("  ✅ File found locally: $path");
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->warning("  ⚠️  File not found locally - will try R2");
            }

        } catch (\Exception $e) {
            $this->error("  ❌ Error: " . $e->getMessage());
        }
    }
}
