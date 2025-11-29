<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SecureFileDrive\Database\Database;
use SecureFileDrive\Storage\LocalStorage;
use SecureFileDrive\Services\FileService;
use SecureFileDrive\Services\SignedUrlService;
use SecureFileDrive\Services\UserService;
use SecureFileDrive\Auth\JWTAuth;

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize database
Database::getInstance()->initialize();

// JSON response
header('Content-Type: application/json');

// Get method and route
$method = $_SERVER['REQUEST_METHOD'];
$scriptName = basename($_SERVER['PHP_SELF']);

try {
    // Authentication (except for login, register and download)
    $auth = new JWTAuth();
    $username = null;

    if ($scriptName !== 'login.php' && $scriptName !== 'register.php' && $scriptName !== 'download.php') {
        $headers = getallheaders();
        $token = null;

        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        } elseif (isset($_GET['token'])) {
            $token = $_GET['token'];
        }

        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication token required']);
            exit;
        }

        $payload = $auth->validateToken($token);
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }

        $username = $payload['username'];
    }

    // Routing based on script name
    switch ($scriptName) {
        case 'login.php':
            handleLogin($auth);
            break;
        case 'register.php':
            handleRegister();
            break;
        case 'upload.php':
            handleUpload($username);
            break;
        case 'files.php':
            handleListFiles($username);
            break;
        case 'file.php':
            handleGetFile($username);
            break;
        case 'delete.php':
            handleDeleteFile($username);
            break;
        case 'signed-url.php':
            handleGenerateSignedUrl($username);
            break;
        case 'download.php':
            handleDownload();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleLogin($auth)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    $token = $auth->authenticate($username, $password);
    if ($token) {
        echo json_encode(['token' => $token, 'username' => $username]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
}

function handleRegister()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $email = trim($data['email'] ?? null);

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        return;
    }

    try {
        $userService = new UserService();
        $userId = $userService->register($username, $password, $email);
        
        echo json_encode([
            'success' => true,
            'message' => 'User registered successfully',
            'user_id' => $userId
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleUpload($username)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No file provided']);
        return;
    }

    $storage = new LocalStorage();
    $fileService = new FileService($storage);
    $file = $fileService->upload($_FILES['file'], $username);

    echo json_encode([
        'success' => true,
        'file' => $file
    ]);
}

function handleListFiles($username)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $storage = new LocalStorage();
    $fileService = new FileService($storage);
    $files = $fileService->listFiles($username, $limit, $offset);

    echo json_encode(['files' => $files]);
}

function handleGetFile($username)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'File ID required']);
        return;
    }

    $storage = new LocalStorage();
    $fileService = new FileService($storage);
    $file = $fileService->getFileById($id);

    if (!$file) {
        http_response_code(404);
        echo json_encode(['error' => 'File not found']);
        return;
    }

    echo json_encode(['file' => $file]);
}

function handleDeleteFile($username)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'File ID required']);
        return;
    }

    $storage = new LocalStorage();
    $fileService = new FileService($storage);
    $fileService->deleteFile($id);

    echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
}

function handleGenerateSignedUrl($username)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $fileId = $data['file_id'] ?? null;
    $expirationHours = $data['expiration_hours'] ?? null;
    $maxAccesses = $data['max_accesses'] ?? null;

    if (!$fileId) {
        http_response_code(400);
        echo json_encode(['error' => 'File ID required']);
        return;
    }

    $signedUrlService = new SignedUrlService();
    $url = $signedUrlService->generateSignedUrl($fileId, $expirationHours, $maxAccesses);

    echo json_encode(['signed_url' => $url]);
}

function handleDownload()
{
    $token = $_GET['token'] ?? null;
    if (!$token) {
        http_response_code(400);
        echo json_encode(['error' => 'Token required']);
        return;
    }

    $signedUrlService = new SignedUrlService();
    $result = $signedUrlService->validateToken($token);

    if (!$result) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid or expired token']);
        return;
    }

    $storage = new LocalStorage();
    $fileService = new FileService($storage);
    $fileContent = $fileService->getFileContent($result['file_id']);

    // Send file
    header('Content-Type: ' . $fileContent['mime_type']);
    header('Content-Disposition: attachment; filename="' . $fileContent['original_name'] . '"');
    header('Content-Length: ' . filesize($fileContent['path']));
    readfile($fileContent['path']);
    exit;
}

