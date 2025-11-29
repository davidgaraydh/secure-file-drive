<?php

namespace SecureFileDrive\Services;

use SecureFileDrive\Database\Database;
use PDO;

class SignedUrlService
{
    private $db;
    private $secret;
    private $expirationHours;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $config = require __DIR__ . '/../../config/config.php';
        $this->secret = $config['signed_urls']['secret_key'];
        $this->expirationHours = $config['signed_urls']['expiration_hours'];
    }

    public function generateSignedUrl($fileId, $expirationHours = null, $maxAccesses = null)
    {
        $expirationHours = $expirationHours ?? $this->expirationHours;
        $expiresAt = date('Y-m-d H:i:s', time() + ($expirationHours * 3600));

        // Generate unique token
        $token = bin2hex(random_bytes(32));

        // Save to database
        $stmt = $this->db->prepare("
            INSERT INTO signed_urls (file_id, token, expires_at, max_accesses)
            VALUES (:file_id, :token, :expires_at, :max_accesses)
        ");

        $stmt->execute([
            ':file_id' => $fileId,
            ':token' => $token,
            ':expires_at' => $expiresAt,
            ':max_accesses' => $maxAccesses
        ]);

        $config = require __DIR__ . '/../../config/config.php';
        $baseUrl = $config['api']['base_url'];

        return $baseUrl . '/api/download.php?token=' . $token;
    }

    public function validateToken($token)
    {
        $stmt = $this->db->prepare("
            SELECT su.*, f.* 
            FROM signed_urls su
            JOIN files f ON su.file_id = f.id
            WHERE su.token = :token
        ");

        $stmt->execute([':token' => $token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        // Check expiration
        if (strtotime($result['expires_at']) < time()) {
            // Delete expired token
            $this->deleteToken($token);
            return null;
        }

        // Check access limit
        if ($result['max_accesses'] !== null && $result['access_count'] >= $result['max_accesses']) {
            return null;
        }

        // Increment access counter
        $updateStmt = $this->db->prepare("
            UPDATE signed_urls 
            SET access_count = access_count + 1 
            WHERE token = :token
        ");
        $updateStmt->execute([':token' => $token]);

        return $result;
    }

    public function deleteToken($token)
    {
        $stmt = $this->db->prepare("DELETE FROM signed_urls WHERE token = :token");
        $stmt->execute([':token' => $token]);
    }

    public function cleanupExpiredTokens()
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            DELETE FROM signed_urls 
            WHERE expires_at < :now
        ");
        $stmt->execute([':now' => $now]);
        return $stmt->rowCount();
    }
}

