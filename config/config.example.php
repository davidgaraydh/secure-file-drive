<?php
/**
 * Example configuration file
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to config.php
 * 2. Fill in all credentials and secret keys
 * 3. NEVER commit config.php to the repository
 */

return [
    // Database
    'database' => [
        'type' => 'mysql', // 'sqlite' o 'mysql'
        'sqlite_path' => __DIR__ . '/../storage/database.sqlite',
        'mysql' => [
            'host' => 'localhost',
            'dbname' => 'secure_file_drive',
            'username' => 'YOUR_MYSQL_USERNAME',
            'password' => 'YOUR_MYSQL_PASSWORD'
        ]
    ],

    // Storage
    'storage' => [
        'type' => 'local', // 'local' or 's3'
        'local_path' => __DIR__ . '/../storage/files',
        's3' => [
            'region' => 'us-east-1',
            'bucket' => 'tu-bucket-name',
            'key' => 'tu-access-key',
            'secret' => 'tu-secret-key'
        ]
    ],

    // File validation
    'validation' => [
        'max_size' => 50 * 1024 * 1024, // 50MB in bytes
        'allowed_types' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'text/csv',
            'application/zip', 'application/x-rar-compressed'
        ],
        'allowed_extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp',
            'pdf',
            'doc', 'docx',
            'xls', 'xlsx',
            'txt', 'csv',
            'zip', 'rar'
        ]
    ],

    // Signed URLs
    'signed_urls' => [
        'expiration_hours' => 24, // Validity hours
        'secret_key' => 'GENERATE_A_RANDOM_SECRET_KEY_HERE', // Change to a secure key
    ],

    // Authentication
    'auth' => [
        'jwt_secret' => 'GENERATE_A_RANDOM_JWT_SECRET_KEY_HERE', // Change to a secure key
        'jwt_expiration' => 3600 * 24, // 24 hours in seconds
        // NOTE: Users now register from the web interface
        // This section is no longer used, but kept for compatibility
        'users' => []
    ],

    // API
    'api' => [
        'base_url' => '/proyectos_repositorio/secure-file-drive', // Adjust according to your configuration
        'cors_enabled' => true,
        'cors_origins' => ['*'] // In production, specify allowed domains
    ]
];

