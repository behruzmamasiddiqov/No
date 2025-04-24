<?php
define('ANIDAO', true);

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// We'll skip the AJAX request check for share tracking

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate request data
if (empty($data) || empty($data['url'])) {
    jsonResponse(['success' => false, 'message' => 'Missing required parameters'], 400);
}

// Get anime ID from URL (simplified for now)
$url = $data['url'];
$animeId = null;

// Extract ID from anime.php?id=X URL
if (strpos($url, 'anime.php?id=') !== false) {
    preg_match('/anime\.php\?id=(\d+)/', $url, $matches);
    if (!empty($matches[1])) {
        $animeId = (int)$matches[1];
    }
} elseif (strpos($url, 'watch.php?id=') !== false) {
    // Extract anime ID from episode data
    preg_match('/watch\.php\?id=(\d+)/', $url, $matches);
    if (!empty($matches[1])) {
        $episodeId = (int)$matches[1];
        $episodeData = getEpisodeById($db, $episodeId);
        if ($episodeData) {
            $animeId = $episodeData['anime_id'];
        }
    }
} else {
    // Default to first anime for homepage shares
    $firstAnime = $db->fetch("SELECT id FROM anime ORDER BY id LIMIT 1");
    if ($firstAnime) {
        $animeId = $firstAnime['id'];
    }
}

// Track share if we have a valid anime ID
if ($animeId) {
    // Check if share record exists
    $shareData = $db->fetch("SELECT * FROM shares WHERE anime_id = ?", [$animeId]);
    
    if ($shareData) {
        // Update existing share count
        $db->query("UPDATE shares SET count = count + 1, updated_at = CURRENT_TIMESTAMP WHERE anime_id = ?", [$animeId]);
    } else {
        // Create new share record
        $db->query("INSERT INTO shares (anime_id, count) VALUES (?, 1)", [$animeId]);
    }
    
    jsonResponse(['success' => true, 'message' => 'Share tracked successfully']);
} else {
    jsonResponse(['success' => false, 'message' => 'Could not determine anime ID']);
}