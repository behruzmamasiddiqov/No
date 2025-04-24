<?php
define('ANIDAO', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/TelegramBot.php';
require_once __DIR__ . '/admin.php';

/**
 * Main class for handling Telegram bot functionality
 */
class AniDaoBot {
    private $bot;
    private $db;
    private $auth;
    private $adminHandler;
    private $update;
    
    private $adminId;
    
    /**
     * Constructor
     */
    public function __construct($token, $db, $auth) {
        $this->bot = new TelegramBot($token);
        $this->db = $db;
        $this->auth = $auth;
        $this->adminHandler = new AdminHandler($this->bot, $this->db);
        $this->adminId = TELEGRAM_ADMIN_ID;
    }
    
    /**
     * Process incoming update
     */
    public function processUpdate($update) {
        $this->update = $update;
        
        // Check if this is a callback query
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return;
        }
        
        // Check if this is a text message
        if (isset($update['message']) && isset($update['message']['text'])) {
            $this->handleMessage($update['message']);
            return;
        }
    }
    
    /**
     * Handle text messages
     */
    private function handleMessage($message) {
        $chatId = $message['chat']['id'];
        $text = $message['text'];
        $firstName = $message['from']['first_name'] ?? 'User';
        
        // Handle /start command
        if (strpos($text, '/start') === 0) {
            // Check if there's a verification code
            $parts = explode(' ', $text);
            if (count($parts) > 1) {
                $code = trim($parts[1]);
                $this->handleVerificationCode($chatId, $code, $message['from']);
                return;
            }
            
            // Regular start command
            $this->bot->sendMessage($chatId, "👋 Welcome to ANI DAO, $firstName!\n\nThis bot helps you authenticate with our anime streaming platform.\n\nTo login, get a verification code from the website and send it here or click the login button on the website.");
            return;
        }
        
        // Handle admin commands (only for admin user)
        if ($chatId == $this->adminId) {
            if ($text === '/admin') {
                $this->adminHandler->showAdminPanel($chatId);
                return;
            }
            
            // Pass to admin handler
            if ($this->adminHandler->handleAdminMessage($chatId, $text, $message)) {
                return;
            }
        }
        
        // Check if the message is a 6-digit code
        if (preg_match('/^\d{6}$/', $text)) {
            $this->handleVerificationCode($chatId, $text, $message['from']);
            return;
        }
        
        // Default response for unknown commands
        $this->bot->sendMessage($chatId, "I didn't understand that command. If you're trying to log in, please enter the 6-digit verification code from the website.");
    }
    
    /**
     * Handle callback queries (button clicks)
     */
    private function handleCallbackQuery($callbackQuery) {
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        $data = $callbackQuery['data'];
        
        // Acknowledge the callback query
        $this->bot->answerCallbackQuery($callbackQuery['id']);
        
        // Check if it's an admin action and user is admin
        if ($chatId == $this->adminId && strpos($data, 'admin_') === 0) {
            $this->adminHandler->handleAdminCallback($callbackQuery);
            return;
        }
        
        // Handle other callbacks
        // Currently no other callback actions implemented
    }
    
    /**
     * Handle verification code for login
     */
    private function handleVerificationCode($chatId, $code, $user) {
        // Verify the code
        $session = $this->auth->verifyCode($code);
        
        if (!$session) {
            $this->bot->sendMessage($chatId, "❌ Invalid or expired verification code. Please try again or get a new code from the website.");
            return;
        }
        
        // Create or update user from Telegram data
        $telegramData = [
            'id' => $user['id'],
            'username' => $user['username'] ?? null,
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'photo_url' => null, // Not available in this context
            'auth_date' => time()
        ];
        
        $userId = $this->auth->saveUserFromTelegram($telegramData);
        
        // Update the session
        $this->auth->updateSessionAfterVerification($session['session_token'], $userId);
        
        // Send success message
        $this->bot->sendMessage($chatId, "✅ Authentication successful! You can now return to the website and continue browsing.");
        
        // If user is admin, show admin options
        if ($chatId == $this->adminId) {
            $this->bot->sendMessage($chatId, "👑 You are logged in as an admin. Send /admin to access the admin panel.");
        }
    }
}
