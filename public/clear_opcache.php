<?php
// Script temporaire pour vider l'OPcache PHP
// À SUPPRIMER après utilisation pour des raisons de sécurité

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache cleared successfully!<br><br>";
} else {
    echo "❌ OPcache is not enabled on this server.<br><br>";
}

echo "<strong>OPcache Status:</strong><br>";
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    if ($status !== false) {
        echo "- Enabled: " . ($status['opcache_enabled'] ? 'Yes' : 'No') . "<br>";
        echo "- Memory used: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB<br>";
        echo "- Cached scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "<br>";
    }
}

echo "<br><strong>Next steps:</strong><br>";
echo "1. Reload your website: <a href='/'>https://dossypro.com</a><br>";
echo "2. Delete this file immediately via SSH: <code>rm public/clear_opcache.php</code><br>";
