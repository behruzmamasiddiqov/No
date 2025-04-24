<?php
// Prevent direct access to this file
if (!defined('ANIDAO')) {
    die('Direct access not permitted');
}

// Get all anime with optional filtering and pagination
function getAnimeList($db, $filters = [], $page = 1, $perPage = 12) {
    $params = [];
    $whereConditions = [];
    
    // Build WHERE clause based on filters
    if (!empty($filters['search'])) {
        $whereConditions[] = "a.title LIKE ?";
        $params[] = '%' . $filters['search'] . '%';
    }
    
    if (!empty($filters['year'])) {
        $whereConditions[] = "a.year = ?";
        $params[] = $filters['year'];
    }
    
    if (!empty($filters['genre'])) {
        $whereConditions[] = "g.id = ?";
        $params[] = $filters['genre'];
    }
    
    if (!empty($filters['status'])) {
        $whereConditions[] = "a.status = ?";
        $params[] = $filters['status'];
    }
    
    // Construct the base query
    $sql = "SELECT DISTINCT a.*, 
            (SELECT COUNT(e.id) FROM episodes e WHERE e.anime_id = a.id) as episode_count,
            (SELECT AVG(r.rating) FROM ratings r WHERE r.anime_id = a.id) as avg_rating
            FROM anime a
            LEFT JOIN anime_genres ag ON a.id = ag.anime_id
            LEFT JOIN genres g ON ag.genre_id = g.id";
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " ORDER BY a.created_at DESC";
    
    // Add pagination
    $offset = ($page - 1) * $perPage;
    $sql .= " LIMIT $perPage OFFSET $offset";
    
    // Execute query
    return $db->fetchAll($sql, $params);
}




function getAdminState($userId) {
    $filePath = "step/$userId.json";
    if (!file_exists($filePath)) return null;

    $data = json_decode(file_get_contents($filePath), true);
    return $data['state'] ?? null;
}

function getAdminData($userId, $key = null) {
    $filePath = "/step/$userId.json";
    if (!file_exists($filePath)) return null;

    $data = json_decode(file_get_contents($filePath), true);
    if ($key) {
        return $data['data'][$key] ?? null;
    }
    return $data['data'] ?? [];
}




// Get anime by ID with all related information
function getAnimeById($db, $animeId) {
    // Get anime details
    $sql = "SELECT a.*,
            (SELECT AVG(r.rating) FROM ratings r WHERE r.anime_id = a.id) as avg_rating,
            (SELECT COUNT(r.id) FROM ratings r WHERE r.anime_id = a.id) as rating_count
            FROM anime a
            WHERE a.id = ?";
    $anime = $db->fetch($sql, [$animeId]);
    
    if (!$anime) return null;
    
    // Get genres
    $sql = "SELECT g.* FROM genres g
            JOIN anime_genres ag ON g.id = ag.genre_id
            WHERE ag.anime_id = ?";
    $anime['genres'] = $db->fetchAll($sql, [$animeId]);
    
    // Get episodes
    $sql = "SELECT * FROM episodes WHERE anime_id = ? ORDER BY episode_number ASC";
    $anime['episodes'] = $db->fetchAll($sql, [$animeId]);
    
    return $anime;
}

// Get episode by ID
function getEpisodeById($db, $episodeId) {
    $sql = "SELECT e.*, a.title as anime_title, a.id as anime_id 
            FROM episodes e
            JOIN anime a ON e.anime_id = a.id
            WHERE e.id = ?";
    return $db->fetch($sql, [$episodeId]);
}

// Get user favorites
function getUserFavorites($db, $userId) {
    $sql = "SELECT a.*, f.created_at as favorited_at
            FROM favorites f
            JOIN anime a ON f.anime_id = a.id
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC";
    return $db->fetchAll($sql, [$userId]);
}

// Check if anime is in user's favorites
function isAnimeFavorite($db, $userId, $animeId) {
    $sql = "SELECT id FROM favorites WHERE user_id = ? AND anime_id = ?";
    return $db->fetch($sql, [$userId, $animeId]) ? true : false;
}

// Add anime to favorites
function addToFavorites($db, $userId, $animeId) {
    $sql = "INSERT IGNORE INTO favorites (user_id, anime_id) VALUES (?, ?)";
    $db->query($sql, [$userId, $animeId]);
}

// Remove anime from favorites
function removeFromFavorites($db, $userId, $animeId) {
    $sql = "DELETE FROM favorites WHERE user_id = ? AND anime_id = ?";
    $db->query($sql, [$userId, $animeId]);
}

// Get user watch history
function getUserWatchHistory($db, $userId, $limit = 20) {
    $sql = "SELECT wh.*, e.episode_number, e.title as episode_title, 
            a.id as anime_id, a.title as anime_title, a.cover_image
            FROM watch_history wh
            JOIN episodes e ON wh.episode_id = e.id
            JOIN anime a ON e.anime_id = a.id
            WHERE wh.user_id = ?
            ORDER BY wh.last_watched DESC
            LIMIT ?";
    return $db->fetchAll($sql, [$userId, $limit]);
}

// Update watch history
function updateWatchHistory($db, $userId, $episodeId, $watchedTime, $completed = false) {
    $sql = "INSERT INTO watch_history (user_id, episode_id, watched_time, completed)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            watched_time = ?, 
            completed = ?,
            last_watched = CURRENT_TIMESTAMP";
    $db->query($sql, [$userId, $episodeId, $watchedTime, $completed, $watchedTime, $completed]);
}

// Get anime comments with user information
function getAnimeComments($db, $animeId, $episodeId = null) {
    $params = [$animeId];
    $episodeCondition = "AND c.episode_id IS NULL";
    
    if ($episodeId !== null) {
        $episodeCondition = "AND c.episode_id = ?";
        $params[] = $episodeId;
    }
    
    $sql = "SELECT c.*, u.username, u.first_name, u.last_name, u.photo_url
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.anime_id = ? $episodeCondition
            ORDER BY c.created_at DESC";
    
    return $db->fetchAll($sql, $params);
}

// Add comment
function addComment($db, $userId, $animeId, $comment, $episodeId = null) {
    $sql = "INSERT INTO comments (user_id, anime_id, episode_id, comment)
            VALUES (?, ?, ?, ?)";
    $db->query($sql, [$userId, $animeId, $episodeId, $comment]);
    return $db->lastInsertId();
}

// Get user rating for anime
function getUserRating($db, $userId, $animeId) {
    $sql = "SELECT rating FROM ratings WHERE user_id = ? AND anime_id = ?";
    $result = $db->fetch($sql, [$userId, $animeId]);
    return $result ? $result['rating'] : null;
}

// Rate anime
function rateAnime($db, $userId, $animeId, $rating) {
    $sql = "INSERT INTO ratings (user_id, anime_id, rating)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = ?";
    $db->query($sql, [$userId, $animeId, $rating, $rating]);
}

// Get all genres
function getAllGenres($db) {
    $sql = "SELECT * FROM genres ORDER BY name ASC";
    return $db->fetchAll($sql);
}

// Format time (seconds) to human-readable format
function formatTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    } else {
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}

// Sanitize output
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Redirect to URL
function redirect($url) {
    header("Location: $url");
    exit;
}

// Check if it's an AJAX request
function isAjaxRequest() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

// Return JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Validate and sanitize input
function sanitizeInput($input) {
    return trim(strip_tags($input));
}

// Get featured anime for the slider
function getFeaturedAnime($db, $limit = 7) {
    $sql = "SELECT a.*, 
            (SELECT COUNT(e.id) FROM episodes e WHERE e.anime_id = a.id) as episode_count,
            (SELECT AVG(r.rating) FROM ratings r WHERE r.anime_id = a.id) as avg_rating
            FROM anime a
            ORDER BY a.updated_at DESC, a.created_at DESC
            LIMIT ?";
    return $db->fetchAll($sql, [$limit]);
}

// Get recently updated anime (with new episodes)
function getRecentlyUpdatedAnime($db, $limit = 4) {
    $sql = "SELECT DISTINCT a.*, e.created_at as episode_added_at, 
            e.episode_number, e.title as episode_title
            FROM anime a
            JOIN episodes e ON a.id = e.anime_id
            ORDER BY e.created_at DESC
            LIMIT ?";
    return $db->fetchAll($sql, [$limit]);
}

// Get popular anime based on views or ratings
function getPopularAnime($db, $limit = 4) {
    $sql = "SELECT a.*, 
            COUNT(wh.id) as view_count,
            (SELECT AVG(r.rating) FROM ratings r WHERE r.anime_id = a.id) as avg_rating
            FROM anime a
            LEFT JOIN episodes e ON a.id = e.anime_id
            LEFT JOIN watch_history wh ON e.id = wh.episode_id
            GROUP BY a.id
            ORDER BY view_count DESC, avg_rating DESC
            LIMIT ?";
    return $db->fetchAll($sql, [$limit]);
}

// Insert sample data if the database is empty
function insertSampleDataIfEmpty($db) {
    // Check if anime table is empty
    $count = $db->fetch("SELECT COUNT(*) as count FROM anime")['count'];
    
    if ($count == 0) {
        // Insert sample anime
        $animeData = [
            [
                'title' => 'To Be Hero X',
                'description' => 'A regular guy gets mysterious powers after a fateful encounter.',
                'cover_image' => 'https://i.imgur.com/5M3LS7b.jpg',
                'year' => 2023,
                'status' => 'ongoing'
            ],
            [
                'title' => 'Cosmic Entity',
                'description' => 'Explorers discover a mysterious cosmic entity with incredible power.',
                'cover_image' => 'https://i.imgur.com/UYQrN1L.jpg',
                'year' => 2022,
                'status' => 'completed'
            ],
            [
                'title' => 'Highschool Romance',
                'description' => 'A sweet romantic story set in a Japanese high school.',
                'cover_image' => 'https://i.imgur.com/ZF3z2vF.jpg',
                'year' => 2023,
                'status' => 'ongoing'
            ],
            [
                'title' => 'Fantasy World',
                'description' => 'Warriors battle dark forces in a fantastical realm.',
                'cover_image' => 'https://i.imgur.com/UB3BQoL.jpg',
                'year' => 2021,
                'status' => 'completed'
            ]
        ];
        
        foreach ($animeData as $anime) {
            $db->query(
                "INSERT INTO anime (title, description, cover_image, year, status) VALUES (?, ?, ?, ?, ?)",
                [$anime['title'], $anime['description'], $anime['cover_image'], $anime['year'], $anime['status']]
            );
            
            $animeId = $db->lastInsertId();
            
            // Add some random genres
            $genreIds = range(1, 15);
            shuffle($genreIds);
            $selectedGenres = array_slice($genreIds, 0, rand(2, 5));
            
            foreach ($selectedGenres as $genreId) {
                $db->query(
                    "INSERT INTO anime_genres (anime_id, genre_id) VALUES (?, ?) ON CONFLICT DO NOTHING",
                    [$animeId, $genreId]
                );
            }
            
            // Add some episodes
            $episodeCount = rand(1, 12);
            for ($i = 1; $i <= $episodeCount; $i++) {
                $db->query(
                    "INSERT INTO episodes (anime_id, episode_number, title, bunny_stream_id) VALUES (?, ?, ?, ?)",
                    [$animeId, $i, "Episode $i", "sample-stream-id-$i"]
                );
            }
        }
        
        // Add some sample shares
        $db->query("INSERT INTO shares (anime_id, count) VALUES (1, 76300) ON CONFLICT DO NOTHING", []);
    }
}
