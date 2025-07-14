<?php

namespace ChatApp\Auth;

use Firebase\JWT\JWT;
use ChatApp\Email\EmailService;

class AuthService
{
    private $db;
    private $emailService;

    public function __construct($db)
    {
        $this->db = $db;
        $this->emailService = new EmailService();
    }

    public function signup(string $firstname, string $lastname, string $email): void
    {
        $user = $this->getUserByEmail($email);
        if ($user) {
            // User already exists, treat as login request
            $this->requestLogin($email);
            return;
        }

        $user = $this->createUser($firstname, $lastname, $email);
        $otp = $this->generateShortOtp();
        $hashedOtp = password_hash($otp, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $this->storeLoginToken($user['id'], $hashedOtp, $expiresAt);
        $this->emailService->sendOtpEmail($email, $otp, true);
    }

    public function requestLogin(string $email): void
    {
        $user = $this->getUserByEmail($email);
        if (!$user) {
            // Or handle this case differently, e.g., by sending an error to the client
            return;
        }

        $otp = $this->generateShortOtp();
        $hashedOtp = password_hash($otp, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $this->storeLoginToken($user['id'], $hashedOtp, $expiresAt);
        $this->emailService->sendOtpEmail($email, $otp, false);
    }

    public function verifyLoginToken(string $email, string $otp): ?string
    {
        $user = $this->getUserByEmail($email);

        if (!$user || !$this->isLoginTokenValid($user, $otp)) {
            return null;
        }

        $this->invalidateLoginToken($user['id']);
        return $this->generateJwt($user['id']);
    }

    private function generateShortOtp(int $length = 6): string
    {
        return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
    }

    private function getUserByEmail(string $email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    private function createUser(string $firstname, string $lastname, string $email)
    {
        $username = strtolower($firstname . '.' . $lastname . '.' . random_int(100, 999));
        $stmt = $this->db->prepare("INSERT INTO users (firstname, lastname, username, email, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$firstname, $lastname, $username, $email]);
        return $this->getUserByEmail($email);
    }

    private function storeLoginToken(int $userId, string $hashedOtp, string $expiresAt): void
    {
        $stmt = $this->db->prepare("UPDATE users SET login_token = ?, login_token_expires_at = ? WHERE id = ?");
        $stmt->execute([$hashedOtp, $expiresAt, $userId]);
    }

    private function isLoginTokenValid(array $user, string $otp): bool
    {
        if (!$user['login_token'] || !$user['login_token_expires_at']) {
            return false;
        }

        if (strtotime($user['login_token_expires_at']) < time()) {
            return false;
        }

        return password_verify($otp, $user['login_token']);
    }

    private function invalidateLoginToken(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE users SET login_token = NULL, login_token_expires_at = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }

    private function generateJwt(int $userId): string
    {
        $secret = $_ENV['JWT_SECRET'];
        $payload = [
            'iss' => "chatapp", // Issuer
            'aud' => "chatapp", // Audience
            'iat' => time(), // Issued at
            'nbf' => time(), // Not before
            'exp' => time() + (60 * 60 * 24 * 7), // Expiration time (7 days)
            'data' => [
                'userId' => $userId
            ]
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }
}