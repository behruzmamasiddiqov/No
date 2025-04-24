<?php
define('ANIDAO', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/TelegramBot.php';
require_once __DIR__ . '/bot.php';

// Get the incoming request content
$content = file_get_contents('php://input');

// Log the incoming webhook if needed
// file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s') . " " . $content . "\n", FILE_APPEND);

// Decode the JSON content
$update = json_decode($content, true);

// Validate the update
if (!$update) {
    http_response_code(400);
    exit('Bad Request');
}

try {
    // Initialize bot
    $aniDaoBot = new AniDaoBot(TELEGRAM_BOT_TOKEN, $db, $auth);
    
    // Process the update
    $aniDaoBot->processUpdate($update);
    
    // Return success response
    http_response_code(200);
    echo 'OK';
} catch (Exception $e) {
    // Log the error
    error_log('Telegram Bot Error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo 'Internal Server Error';
}
