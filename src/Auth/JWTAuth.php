<?php

namespace SecureFileDrive\Auth;

class JWTAuth
{
    private $secret;
    private $expiration;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->secret = $config['auth']['jwt_secret'];
        $this->expiration = $config['auth']['jwt_expiration'];
    }

    public function generateToken($username)
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        $payload = [
            'username' => $username,
            'iat' => time(),
            'exp' => time() + $this->expiration
        ];

        $base64Header = $this->base64UrlEncode(json_encode($header));
        $base64Payload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $this->secret, true);
        $base64Signature = $this->base64UrlEncode($signature);

        return "$base64Header.$base64Payload.$base64Signature";
    }

    public function validateToken($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        [$base64Header, $base64Payload, $base64Signature] = $parts;

        $signature = $this->base64UrlDecode($base64Signature);
        $expectedSignature = hash_hmac('sha256', "$base64Header.$base64Payload", $this->secret, true);

        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        $payload = json_decode($this->base64UrlDecode($base64Payload), true);

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    public function authenticate($username, $password)
    {
        $userService = new \SecureFileDrive\Services\UserService();
        $user = $userService->verifyPassword($username, $password);

        if (!$user) {
            return false;
        }

        return $this->generateToken($username);
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

