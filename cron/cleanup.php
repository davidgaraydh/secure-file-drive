<?php
/**
 * Script to clean up expired signed URLs
 * Run periodically via cron job
 * Example: */30 * * * * php /path/to/project/cron/cleanup.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SecureFileDrive\Database\Database;
use SecureFileDrive\Services\SignedUrlService;

try {
    Database::getInstance()->initialize();
    $signedUrlService = new SignedUrlService();
    $deleted = $signedUrlService->cleanupExpiredTokens();
    
    echo date('Y-m-d H:i:s') . " - Cleanup completed. Tokens deleted: $deleted\n";
} catch (Exception $e) {
    echo date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n";
}

