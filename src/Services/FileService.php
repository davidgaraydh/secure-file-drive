<?php

namespace SecureFileDrive\Services;

use SecureFileDrive\Database\Database;
use SecureFileDrive\Storage\StorageInterface;
use SecureFileDrive\Validation\FileValidator;
use PDO;

class FileService
{
    private $db;
    private $storage;
    private $validator;

    public function __construct(StorageInterface $storage)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->storage = $storage;
        $this->validator = new FileValidator();
    }

    public function upload($file, $username = null)
    {
        // Validate file
        $validation = $this->validator->validate($file);
        if (!$validation['valid']) {
            throw new \Exception(implode(', ', $validation['errors']));
        }

        // Generate unique name for storage
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $storedName = uniqid() . '_' . time() . '.' . $extension;
        $datePath = date('Y/m/d');
        $destination = $datePath . '/' . $storedName;

        // Store file
        $this->storage->store($file, $destination);

        // Save metadata to database
        $stmt = $this->db->prepare("
            INSERT INTO files (original_name, stored_name, file_path, mime_type, file_size, extension, uploaded_by)
            VALUES (:original_name, :stored_name, :file_path, :mime_type, :file_size, :extension, :uploaded_by)
        ");

        $stmt->execute([
            ':original_name' => $file['name'],
            ':stored_name' => $storedName,
            ':file_path' => $destination,
            ':mime_type' => $file['type'],
            ':file_size' => $file['size'],
            ':extension' => $extension,
            ':uploaded_by' => $username
        ]);

        $fileId = $this->db->lastInsertId();

        return $this->getFileById($fileId);
    }

    public function getFileById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM files WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listFiles($username = null, $limit = 50, $offset = 0)
    {
        $sql = "SELECT * FROM files";
        $params = [];

        if ($username) {
            $sql .= " WHERE uploaded_by = :username";
            $params[':username'] = $username;
        }

        $sql .= " ORDER BY upload_date DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteFile($id)
    {
        $file = $this->getFileById($id);
        if (!$file) {
            throw new \Exception("File not found");
        }

        // Delete physical file
        $this->storage->delete($file['file_path']);

        // Delete from database (cascade will delete signed URLs)
        $stmt = $this->db->prepare("DELETE FROM files WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return true;
    }

    public function getFileContent($id)
    {
        $file = $this->getFileById($id);
        if (!$file) {
            throw new \Exception("File not found");
        }

        $path = $this->storage->retrieve($file['file_path']);
        return [
            'path' => $path,
            'mime_type' => $file['mime_type'],
            'original_name' => $file['original_name']
        ];
    }
}

