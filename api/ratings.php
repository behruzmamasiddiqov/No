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

if (!$requestData || !isset($requestData['anime_id']) || !isset($requestData['rating'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
}

$animeId = (int)$requestData['anime_id'];
$rating = (int)$requestData['rating'];

// Validate anime ID
if ($animeId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid anime ID'], 400);
}

// Validate rating
if ($rating < 1 || $rating > 10) {
    jsonResponse(['success' => false, 'message' => 'Rating must be between 1 and 10'], 400);
}

// Check if anime exists
$anime = $db->fetch("SELECT id, title FROM anime WHERE id = ?", [$animeId]);
if (!$anime) {
    jsonResponse(['success' => false, 'message' => 'Anime not found'], 404);
}

// Save rating
rateAnime($db, $currentUser['id'], $animeId, $rating);

// Get new average rating
$avgRating = $db->fetch(
    "SELECT AVG(rating) as avg_rating FROM ratings WHERE anime_id = ?", 
    [$animeId]
)['avg_rating'];

jsonResponse([
    'success' => true, 
    'message' => 'Rating saved',
    'data' => [
        'anime_id' => $animeId,
        'user_rating' => $rating,
        'avg_rating' => $avgRating ? round($avgRating, 1) : 0
    ]
]);
