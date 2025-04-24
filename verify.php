<?php
define('ANIDAO', true);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// This endpoint can only be accessed via AJAX
if (!isAjaxRequest()) {
    redirect('/login.php');
}

// Get the code from the URL parameter
$code = isset($_GET['code']) ? sanitizeInput($_GET['code']) : null;

if (!$code) {
    jsonResponse(['success' => false, 'message' => 'No verification code provided'], 400);
}

// Check if the code is valid and has been verified
$session = $auth->verifyCode($code);

if (!$session) {
    jsonResponse(['success' => false, 'message' => 'Invalid or expired verification code'], 400);
}

// Check if the user has been verified via Telegram
if ($session['user_id'] != 0) {
    // Get the temporary session token
    $tempToken = isset($_COOKIE['temp_session_token']) ? $_COOKIE['temp_session_token'] : null;
    
    // If the tokens match, set the verified session cookie
    if ($tempToken === $session['session_token']) {
        // Set session token cookie
        setcookie('session_token', $session['session_token'], time() + SESSION_DURATION, '/');
        // Remove temporary token
        setcookie('temp_session_token', '', time() - 3600, '/');
        
        jsonResponse(['success' => true, 'message' => 'Verification successful']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Session token mismatch'], 400);
    }
} else {
    jsonResponse(['success' => false, 'message' => 'Verification pending'], 202);
}
