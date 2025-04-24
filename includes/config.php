<?php
// Prevent direct access to this file
if (!defined('ANIDAO')) {
    die('Direct access not permitted');
}

// Load secrets
require_once __DIR__ . '/secrets.php';

// Database configuration
define('DB_TYPE', $secrets['DB_TYPE'] ?? 'pgsql'); // Default: pgsql
define('DB_HOST', $secrets['DB_HOST']);
define('DB_PORT', $secrets['DB_PORT']);
define('DB_NAME', $secrets['DB_NAME']);
define('DB_USER', $secrets['DB_USER']);
define('DB_PASS', $secrets['DB_PASS']);

// Telegram Bot configuration
define('TELEGRAM_BOT_TOKEN', $secrets['TELEGRAM_BOT_TOKEN']);
define('TELEGRAM_BOT_USERNAME', $secrets['TELEGRAM_BOT_USERNAME']);
define('TELEGRAM_ADMIN_ID', $secrets['TELEGRAM_ADMIN_ID']);

// Bunny.net configuration
define('BUNNY_STREAM_LIBRARY_ID', $secrets['BUNNY_STREAM_LIBRARY_ID']);
define('BUNNY_API_KEY', $secrets['BUNNY_API_KEY']);
define('BUNNY_CDN_HOSTNAME', $secrets['BUNNY_CDN_HOSTNAME']);

// Site configuration
define('SITE_NAME', 'ANI DAO');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
define('SITE_URL', $protocol . $_SERVER['HTTP_HOST']);
define('SESSION_DURATION', 60 * 60 * 24 * 3); // 3 days

// Error reporting (change to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('UTC');