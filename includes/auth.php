<?php
// Prevent direct access to this file
if (!defined('ANIDAO')) {
    die('Direct access not permitted');
}

class Auth {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Generate a random 6-digit code for Telegram verification
    public function generateVerificationCode() {
        return str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    // Create a new session with verification code
    public function createSessionWithCode($userId = null) {
        $code = $this->generateVerificationCode();
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_DURATION);
        
        // If userId is null, create a temporary session
        if ($userId === null) {
            // Create a temporary user for the session
            $sql = "INSERT INTO users (telegram_id, username) VALUES (0, 'temp_user')";
            $this->db->query($sql);
            $userId = $this->db->lastInsertId();
        }
        
        $sql = "INSERT INTO sessions (user_id, session_token, code, expires_at) 
                VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [$userId, $token, $code, $expiresAt]);
        
        return [
            'token' => $token,
            'code' => $code,
            'user_id' => $userId,
            'expires_at' => $expiresAt
        ];
    }
    
    // Verify a session code
    public function verifyCode($code) {
        $sql = "SELECT * FROM sessions WHERE code = ? AND expires_at > NOW()";
        return $this->db->fetch($sql, [$code]);
    }
    
    // Get user by telegram ID
    public function getUserByTelegramId($telegramId) {
        $sql = "SELECT * FROM users WHERE telegram_id = ?";
        return $this->db->fetch($sql, [$telegramId]);
    }
    
    // Create or update user from Telegram data
    public function saveUserFromTelegram($telegramData) {
        $telegramId = $telegramData['id'];
        $user = $this->getUserByTelegramId($telegramId);
        
        if ($user) {
            // Update existing user
            $sql = "UPDATE users SET 
                    username = ?, 
                    first_name = ?, 
                    last_name = ?, 
                    photo_url = ?, 
                    auth_date = ?
                    WHERE telegram_id = ?";
            $this->db->query($sql, [
                $telegramData['username'] ?? null,
                $telegramData['first_name'] ?? null, 
                $telegramData['last_name'] ?? null,
                $telegramData['photo_url'] ?? null,
                date('Y-m-d H:i:s', $telegramData['auth_date']),
                $telegramId
            ]);
            return $user['id'];
        } else {
            // Create new user
            $sql = "INSERT INTO users (
                    telegram_id, username, first_name, last_name, photo_url, auth_date
                    ) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $telegramId,
                $telegramData['username'] ?? null,
                $telegramData['first_name'] ?? null, 
                $telegramData['last_name'] ?? null,
                $telegramData['photo_url'] ?? null,
                date('Y-m-d H:i:s', $telegramData['auth_date'])
            ]);
            return $this->db->lastInsertId();
        }
    }
    
    // Update session after successful verification
    public function updateSessionAfterVerification($sessionToken, $userId) {
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_DURATION);
        $sql = "UPDATE sessions SET 
                user_id = ?, 
                code = NULL, 
                expires_at = ? 
                WHERE session_token = ?";
        $this->db->query($sql, [$userId, $expiresAt, $sessionToken]);
    }
    
    // Verify session token
    public function verifySession($token) {
        if (!$token) return false;
        
        $sql = "SELECT s.*, u.* FROM sessions s
                JOIN users u ON s.user_id = u.id
                WHERE s.session_token = ? AND s.expires_at > NOW()";
        return $this->db->fetch($sql, [$token]);
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        if (!isset($_COOKIE['session_token'])) return false;
        
        $user = $this->verifySession($_COOKIE['session_token']);
        return $user !== false;
    }
    
    // Get current user data
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) return false;
        
        return $this->verifySession($_COOKIE['session_token']);
    }
    
    // Check if current user is admin
    public function isAdmin() {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        
        return $user['telegram_id'] == TELEGRAM_ADMIN_ID;
    }
    
    // Logout user
    public function logout() {
        if (isset($_COOKIE['session_token'])) {
            $sql = "DELETE FROM sessions WHERE session_token = ?";
            $this->db->query($sql, [$_COOKIE['session_token']]);
            
            setcookie('session_token', '', time() - 3600, '/');
        }
    }
}

// Create auth instance
$auth = new Auth($db);
