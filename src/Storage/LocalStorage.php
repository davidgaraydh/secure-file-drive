<?php

namespace SecureFileDrive\Storage;

class LocalStorage implements StorageInterface
{
    private $basePath;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->basePath = $config['storage']['local_path'];

        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }

    public function store($file, $destination): string
    {
        $fullPath = $this->basePath . '/' . $destination;
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (is_uploaded_file($file['tmp_name'])) {
            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new \Exception("Error moving uploaded file");
            }
        } else {
            if (!copy($file['tmp_name'], $fullPath)) {
                throw new \Exception("Error copying file");
            }
        }

        return $fullPath;
    }

    public function retrieve($path): string
    {
        $fullPath = $this->basePath . '/' . $path;
        
        if (!file_exists($fullPath)) {
            throw new \Exception("File not found");
        }

        return $fullPath;
    }

    public function delete($path): bool
    {
        $fullPath = $this->basePath . '/' . $path;
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    public function exists($path): bool
    {
        $fullPath = $this->basePath . '/' . $path;
        return file_exists($fullPath);
    }
}

