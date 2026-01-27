#!/usr/bin/env php
<?php
/**
 * Test SMTP Configuration
 * Usage: php test-smtp-connection.php
 */

echo "\n=== DOSSY PRO - SMTP Connection Test ===\n\n";

// Load environment variables
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die("❌ .env file not found!\n");
}

$env = parse_ini_file($envFile);

$host = $env['MAIL_HOST'] ?? 'smtp-threesixty.alwaysdata.net';
$port = $env['MAIL_PORT'] ?? 25;
$username = $env['MAIL_USERNAME'] ?? 'contact@dossypro.com';
$password = $env['MAIL_PASSWORD'] ?? '';
$encryption = $env['MAIL_ENCRYPTION'] ?? 'tls';

echo "Configuration:\n";
echo "  Host: $host\n";
echo "  Port: $port\n";
echo "  Username: $username\n";
echo "  Password: " . str_repeat('*', strlen($password)) . "\n";
echo "  Encryption: $encryption\n\n";

echo "Testing connection...\n";

// Test TCP connection
$errno = 0;
$errstr = '';
$timeout = 10;

if ($encryption === 'ssl') {
    $host = 'ssl://' . $host;
}

$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);

if (!$fp) {
    echo "❌ Failed to connect to SMTP server\n";
    echo "   Error ($errno): $errstr\n";
    exit(1);
}

echo "✅ TCP Connection successful\n";

// Read server greeting
$response = fgets($fp, 512);
echo "   Server: " . trim($response) . "\n";

// Send EHLO
fwrite($fp, "EHLO localhost\r\n");
$response = '';
while ($line = fgets($fp, 512)) {
    $response .= $line;
    if (substr($line, 3, 1) == ' ') break;
}
echo "   EHLO Response: OK\n";

// Try AUTH LOGIN
fwrite($fp, "AUTH LOGIN\r\n");
$response = fgets($fp, 512);
echo "   AUTH LOGIN: " . trim($response) . "\n";

if (strpos($response, '334') !== false) {
    // Send username
    fwrite($fp, base64_encode($username) . "\r\n");
    $response = fgets($fp, 512);
    echo "   Username sent: " . trim($response) . "\n";
    
    // Send password
    fwrite($fp, base64_encode($password) . "\r\n");
    $response = fgets($fp, 512);
    echo "   Password sent: " . trim($response) . "\n";
    
    if (strpos($response, '235') !== false) {
        echo "\n✅ ✅ ✅ SMTP Authentication SUCCESSFUL! ✅ ✅ ✅\n";
        echo "\nYour SMTP configuration is correct!\n";
        echo "Emails should now send properly.\n";
    } elseif (strpos($response, '535') !== false) {
        echo "\n❌ ❌ ❌ SMTP Authentication FAILED! ❌ ❌ ❌\n";
        echo "\nError: Incorrect username or password (535)\n";
        echo "\nPlease check:\n";
        echo "1. Email account exists in AlwaysData\n";
        echo "2. Password is correct\n";
        echo "3. SMTP access is enabled for this email\n";
    } else {
        echo "\n❌ Unexpected response: " . trim($response) . "\n";
    }
} else {
    echo "\n❌ Server doesn't support AUTH LOGIN\n";
}

// Close connection
fwrite($fp, "QUIT\r\n");
fgets($fp, 512);
fclose($fp);

echo "\n=== Test Complete ===\n\n";
