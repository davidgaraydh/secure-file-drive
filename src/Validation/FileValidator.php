<?php

namespace SecureFileDrive\Validation;

class FileValidator
{
    private $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/config.php';
    }

    public function validate($file)
    {
        $errors = [];

        // Validate that file was uploaded correctly
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE);
        }

        // Validate size
        if (isset($file['size']) && $file['size'] > $this->config['validation']['max_size']) {
            $maxSizeMB = $this->config['validation']['max_size'] / (1024 * 1024);
            $errors[] = "File exceeds maximum allowed size of {$maxSizeMB}MB";
        }

        // Validate MIME type
        if (isset($file['type'])) {
            if (!in_array($file['type'], $this->config['validation']['allowed_types'])) {
                $errors[] = "File type not allowed: {$file['type']}";
            }
        }

        // Validate extension
        if (isset($file['name'])) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $this->config['validation']['allowed_extensions'])) {
                $errors[] = "File extension not allowed: {$extension}";
            }
        }

        // Validate that file exists
        if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            $errors[] = "File was not uploaded correctly";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    private function getUploadErrorMessage($errorCode)
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds maximum size defined in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds maximum size defined in form',
            UPLOAD_ERR_PARTIAL => 'File was partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Error writing file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
        ];

        return $messages[$errorCode] ?? 'Unknown error uploading file';
    }
}

