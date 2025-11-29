<?php
/**
 * Installation script
 * Run once to set up the system
 */

echo "=== Secure File Drive Installation ===\n\n";

// Create necessary directories
$directories = [
    __DIR__ . '/storage/files',
    __DIR__ . '/storage',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✓ Directory created: $dir\n";
    } else {
        echo "✓ Directory already exists: $dir\n";
    }
}

// Initialize database
require_once __DIR__ . '/vendor/autoload.php';

use SecureFileDrive\Database\Database;

try {
    Database::getInstance()->initialize();
    echo "✓ Database initialized successfully\n";
} catch (Exception $e) {
    echo "✗ Error initializing database: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Installation completed ===\n";
echo "\n✓ Database tables created (users, files, signed_urls)\n";
echo "\n📝 You can register from the web interface or use existing users\n";
echo "\n⚠️  IMPORTANT: Change secret keys in config/config.php\n";
echo "\n🌐 You can access the application at: http://localhost/proyectos_repositorio/secure-file-drive/\n";

