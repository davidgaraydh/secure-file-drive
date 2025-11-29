<?php

namespace SecureFileDrive\Services;

use SecureFileDrive\Database\Database;
use PDO;

class UserService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function register($username, $password, $email = null)
    {
        // Validate that user doesn't exist
        if ($this->userExists($username)) {
            throw new \Exception("User already exists");
        }

        // Validate email if provided
        if ($email && $this->emailExists($email)) {
            throw new \Exception("Email already registered");
        }

        // Validate password length
        if (strlen($password) < 6) {
            throw new \Exception("Password must be at least 6 characters long");
        }

        // Validate username format
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            throw new \Exception("Username can only contain letters, numbers and underscores (3-20 characters)");
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $stmt = $this->db->prepare("
            INSERT INTO users (username, password, email, created_at)
            VALUES (:username, :password, :email, NOW())
        ");

        try {
            $stmt->execute([
                ':username' => $username,
                ':password' => $passwordHash,
                ':email' => $email
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new \Exception("Error registering user: " . $e->getMessage());
        }
    }

    public function getUserByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username AND is_active = 1");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id AND is_active = 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verifyPassword($username, $password)
    {
        $user = $this->getUserByUsername($username);
        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Update last login
        $this->updateLastLogin($username);

        return $user;
    }

    public function userExists($username)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetchColumn() > 0;
    }

    public function emailExists($email)
    {
        if (!$email) {
            return false;
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    private function updateLastLogin($username)
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE username = :username");
        $stmt->execute([':username' => $username]);
    }
}

