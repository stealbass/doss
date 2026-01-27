<?php
/**
 * Clear all Laravel caches
 * Access via: http://yourdomain.com/clear-cache.php
 */

// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OPcache cleared<br>";
} else {
    echo "⚠ OPcache not available<br>";
}

// Clear APCu cache
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "✓ APCu cache cleared<br>";
} else {
    echo "⚠ APCu not available<br>";
}

echo "<br><strong>Cache cleared successfully!</strong><br>";
echo "Please refresh your application page.<br>";
echo "<br><a href='/admin/document-templates'>Go to Document Templates</a>";
