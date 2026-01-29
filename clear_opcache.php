<?php
/**
 * Clear PHP opcache to force reload of modified files
 */
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "Opcache cleared successfully!\n";
} else {
    echo "Opcache not enabled\n";
}

// Also clear any file stat cache
clearstatcache(true);
echo "File stat cache cleared\n";
