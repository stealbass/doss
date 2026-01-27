<?php
// Simple OPcache reset helper. Remove after use for security.
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OPcache cleared successfully';
} else {
    echo 'OPcache not enabled';
}
