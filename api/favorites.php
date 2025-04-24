<?php
define('ANIDAO', true);

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if it's an AJAX request
if (!isAjaxRequest()) {
    http_response_code(403);
    exit('Access denied');
}

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'You must be logged in'], 401);
}

// Get current user
$currentUser = $auth->getCurrentUser();

// Get request data
$requestData = json_decode(file_get_contents('php://input'), true);

if (!$requestData || !isset($requestData['anime_id']) || !isset($requestData['action'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
}

$animeId = (int)$requestData['anime_id'];
$action = $requestData['action'];

// Validate anime ID
if ($animeId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid anime ID'], 400);
}

// Check if anime exists
$anime = $db->fetch("SELECT id, title FROM anime WHERE id = ?", [$animeId]);
if (!$anime) {
    jsonResponse(['success' => false, 'message' => 'Anime not found'], 404);
}

// Process the action
if ($action === 'add') {
    addToFavorites($db, $currentUser['id'], $animeId);
    jsonResponse([
        'success' => true, 
        'message' => 'Added to favorites',
        'is_favorite' => true
    ]);
} else if ($action === 'remove') {
    removeFromFavorites($db, $currentUser['id'], $animeId);
    jsonResponse([
        'success' => true, 
        'message' => 'Removed from favorites',
        'is_favorite' => false
    ]);
} else {
    jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}
