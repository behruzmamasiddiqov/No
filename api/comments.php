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

if (!$requestData || !isset($requestData['anime_id']) || !isset($requestData['comment'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
}

$animeId = (int)$requestData['anime_id'];
$comment = trim($requestData['comment']);
$episodeId = isset($requestData['episode_id']) ? (int)$requestData['episode_id'] : null;

// Validate anime ID
if ($animeId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid anime ID'], 400);
}

// Validate episode ID if provided
if ($episodeId !== null && $episodeId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid episode ID'], 400);
}

// Validate comment
if (empty($comment)) {
    jsonResponse(['success' => false, 'message' => 'Comment cannot be empty'], 400);
}

// Check if anime exists
$anime = $db->fetch("SELECT id FROM anime WHERE id = ?", [$animeId]);
if (!$anime) {
    jsonResponse(['success' => false, 'message' => 'Anime not found'], 404);
}

// Check if episode exists if episode_id is provided
if ($episodeId !== null) {
    $episode = $db->fetch("SELECT id FROM episodes WHERE id = ? AND anime_id = ?", [$episodeId, $animeId]);
    if (!$episode) {
        jsonResponse(['success' => false, 'message' => 'Episode not found'], 404);
    }
}

// Save comment
$commentId = addComment($db, $currentUser['id'], $animeId, $comment, $episodeId);

// Return comment data
$newComment = [
    'id' => $commentId,
    'user_id' => $currentUser['id'],
    'anime_id' => $animeId,
    'episode_id' => $episodeId,
    'comment' => $comment,
    'created_at' => date('Y-m-d H:i:s'),
    'username' => $currentUser['username'],
    'first_name' => $currentUser['first_name'],
    'last_name' => $currentUser['last_name'],
    'photo_url' => $currentUser['photo_url']
];

jsonResponse([
    'success' => true, 
    'message' => 'Comment added',
    'comment' => $newComment
]);
