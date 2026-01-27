<?php
/**
 * Diagnostic complet pour les documents juridiques
 * Vérifie file_path et suggère des corrections
 */

// Configuration de base - lecture du fichier .env
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    die("❌ .env not found!\n");
}

// Parser simple le .env
$env = [];
foreach (file($envPath) as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#') continue;
    
    [$key, $value] = explode('=', $line, 2) + [1 => null];
    $env[trim($key)] = trim($value, '"\'');
}

// Connexion à la BD
$host = $env['DB_HOST'] ?? 'localhost';
$user = $env['DB_USERNAME'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';
$db = $env['DB_DATABASE'] ?? 'dossy';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database: $db\n";
    echo "=======================================\n\n";
    
    // Check documents with empty file_path
    $stmt = $pdo->query("SELECT id, title, file_name, file_path FROM legal_documents ORDER BY id LIMIT 15");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total documents: " . count($documents) . "\n";
    echo "Sample documents:\n";
    echo "=========================================\n";
    
    $emptyCount = 0;
    foreach ($documents as $doc) {
        $path = empty($doc['file_path']) ? '❌ EMPTY' : '✅ ' . $doc['file_path'];
        echo "ID: {$doc['id']} | File: {$doc['file_name']} | Path: $path\n";
        
        if (empty($doc['file_path'])) {
            $emptyCount++;
        }
    }
    
    echo "\n=========================================\n";
    echo "Documents with empty file_path: $emptyCount\n";
    
    if ($emptyCount > 0) {
        echo "\n🔧 FIXING...\n";
        
        // Update empty paths
        $stmt = $pdo->prepare("UPDATE legal_documents SET file_path = CONCAT('legal_documents/', file_name) WHERE file_path IS NULL OR file_path = ''");
        $result = $stmt->execute();
        
        echo "✅ Fixed " . $stmt->rowCount() . " documents\n";
        
        // Verify
        echo "\nVerifying fix...\n";
        $stmt = $pdo->query("SELECT COUNT(*) as empty_count FROM legal_documents WHERE file_path IS NULL OR file_path = ''");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['empty_count'];
        
        if ($count == 0) {
            echo "✅ All documents now have file_path set!\n";
        } else {
            echo "⚠️  Still $count documents with empty file_path\n";
        }
    } else {
        echo "✅ All documents have file_path correctly set!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check .env file has correct DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE\n";
    echo "2. Verify MySQL is running\n";
    echo "3. Check your connection credentials\n";
}
