<?php

namespace SecureFileDrive\Database;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $dbConfig = $config['database'];

        try {
            if ($dbConfig['type'] === 'sqlite') {
                $this->connection = new PDO(
                    'sqlite:' . $dbConfig['sqlite_path'],
                    null,
                    null,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } else {
                $mysql = $dbConfig['mysql'];
                $this->connection = new PDO(
                    "mysql:host={$mysql['host']};dbname={$mysql['dbname']};charset=utf8mb4",
                    $mysql['username'],
                    $mysql['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            }
        } catch (PDOException $e) {
            throw new \Exception("Database connection error: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function initialize()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                original_name VARCHAR(255) NOT NULL,
                stored_name VARCHAR(255) NOT NULL UNIQUE,
                file_path TEXT NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                file_size BIGINT NOT NULL,
                extension VARCHAR(10) NOT NULL,
                upload_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                uploaded_by VARCHAR(100)
            )
        ";

        if ($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $sql = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $sql);
            $sql = str_replace('INTEGER PRIMARY KEY', 'INT PRIMARY KEY', $sql);
        }

        $this->connection->exec($sql);

        // Create indexes
        try {
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_uploaded_by ON files(uploaded_by)");
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_upload_date ON files(upload_date)");
        } catch (PDOException $e) {
            // Indexes may already exist, ignore error
        }

        $sql = "
            CREATE TABLE IF NOT EXISTS signed_urls (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                file_id INTEGER NOT NULL,
                token VARCHAR(255) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                access_count INTEGER DEFAULT 0,
                max_accesses INTEGER DEFAULT NULL,
                FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE
            )
        ";

        if ($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $sql = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $sql);
            $sql = str_replace('INTEGER PRIMARY KEY', 'INT PRIMARY KEY', $sql);
        }

        $this->connection->exec($sql);

        // Create indexes
        try {
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_token ON signed_urls(token)");
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_expires_at ON signed_urls(expires_at)");
        } catch (PDOException $e) {
            // Indexes may already exist, ignore error
        }

        // Create users table
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME,
                is_active TINYINT(1) DEFAULT 1
            )
        ";

        if ($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $sql = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $sql);
            $sql = str_replace('INTEGER PRIMARY KEY', 'INT PRIMARY KEY', $sql);
        }

        $this->connection->exec($sql);

        // Create indexes for users
        try {
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_username ON users(username)");
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_email ON users(email)");
        } catch (PDOException $e) {
            // Indexes may already exist, ignore error
        }
    }
}

