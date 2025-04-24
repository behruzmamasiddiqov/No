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

if (!$requestData || !isset($requestData['episode_id']) || !isset($requestData['watched_time'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
}

$episodeId = (int)$requestData['episode_id'];
$watchedTime = (int)$requestData['watched_time'];
$completed = isset($requestData['completed']) ? (bool)$requestData['completed'] : false;

// Validate episode ID
if ($episodeId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid episode ID'], 400);
}

// Check if episode exists
$episode = $db->fetch("SELECT id FROM episodes WHERE id = ?", [$episodeId]);
if (!$episode) {
    jsonResponse(['success' => false, 'message' => 'Episode not found'], 404);
}

// Update watch history
updateWatchHistory($db, $currentUser['id'], $episodeId, $watchedTime, $completed);

jsonResponse([
    'success' => true, 
    'message' => 'Watch history updated',
    'data' => [
        'episode_id' => $episodeId,
        'watched_time' => $watchedTime,
        'completed' => $completed
    ]
]);
