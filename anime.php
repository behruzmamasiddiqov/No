<?php
define('ANIDAO', true);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get anime ID from URL
$animeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($animeId <= 0) {
    redirect('/');
}

// Get anime details with episodes and genres
$anime = getAnimeById($db, $animeId);

if (!$anime) {
    // Anime not found
    header('HTTP/1.0 404 Not Found');
    $pageTitle = 'Anime Not Found';
    include 'includes/header.php';
    echo '<div class="alert alert-danger">The requested anime could not be found.</div>';
    include 'includes/footer.php';
    exit;
}

// Get current user
$currentUser = $auth->getCurrentUser();
$userRating = null;
$isFavorite = false;

if ($currentUser) {
    // Get user's rating for this anime
    $userRating = getUserRating($db, $currentUser['id'], $animeId);
    
    // Check if anime is in user's favorites
    $isFavorite = isAnimeFavorite($db, $currentUser['id'], $animeId);
}

// Get comments for this anime
$comments = getAnimeComments($db, $animeId);

// Set page title
$pageTitle = $anime['title'];

// Include header
include 'includes/header.php';
?>

<div class="row">
    <!-- Anime Details -->
    <div class="col-md-8">
        <div class="mb-4">
            <?php if (!empty($anime['cover_image'])): ?>
                <img src="<?= h($anime['cover_image']) ?>" alt="<?= h($anime['title']) ?>" class="anime-cover mb-3">
            <?php endif; ?>
            
            <h1 class="anime-title"><?= h($anime['title']) ?></h1>
            
            <div class="anime-info">
                <div class="mb-2">
                    <span class="badge bg-primary"><?= $anime['year'] ?? 'Unknown' ?></span>
                    <span class="badge bg-<?= $anime['status'] == 'ongoing' ? 'success' : ($anime['status'] == 'completed' ? 'info' : 'warning') ?>">
                        <?= ucfirst(h($anime['status'])) ?>
                    </span>
                    
                    <?php if ($anime['avg_rating']): ?>
                        <span class="badge bg-warning text-dark">
                            <i data-feather="star" class="me-1"></i> 
                            <?= number_format($anime['avg_rating'], 1) ?>/10
                            (<?= $anime['rating_count'] ?> ratings)
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($currentUser): ?>
                        <button class="btn btn-sm btn-outline-<?= $isFavorite ? 'danger' : 'light' ?> ms-2 favorite-toggle" data-anime-id="<?= $animeId ?>">
                            <i data-feather="<?= $isFavorite ? 'heart-fill' : 'heart' ?>"></i>
                            <?= $isFavorite ? 'Remove from Favorites' : 'Add to Favorites' ?>
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <?php foreach ($anime['genres'] as $genre): ?>
                        <span class="genre-badge"><?= h($genre['name']) ?></span>
                    <?php endforeach; ?>
                </div>
                
                <p><?= nl2br(h($anime['description'])) ?></p>
            </div>
            
            <?php if ($currentUser): ?>
                <!-- Rating System -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Rate this Anime</h5>
                        <div class="rating-container" data-anime-id="<?= $animeId ?>">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="star <?= $userRating >= $i ? 'filled' : '' ?>" data-value="<?= $i ?>">
                                    <i data-feather="<?= $userRating >= $i ? 'star-fill' : 'star' ?>"></i>
                                </div>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" value="<?= $userRating ?? 0 ?>">
                            <span class="ms-2" id="rating-text">
                                <?= $userRating ? "Your rating: $userRating/10" : 'Rate this anime' ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Episodes List -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Episodes (<?= count($anime['episodes']) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($anime['episodes'])): ?>
                        <p class="text-muted">No episodes available.</p>
                    <?php else: ?>
                        <div class="episode-list">
                            <?php foreach ($anime['episodes'] as $episode): ?>
                                <a href="/watch.php?id=<?= $episode['id'] ?>" class="episode-item text-decoration-none text-white">
                                    <div class="episode-number">Ep <?= $episode['episode_number'] ?></div>
                                    <div class="episode-title">
                                        <?= !empty($episode['title']) ? h($episode['title']) : 'Episode ' . $episode['episode_number'] ?>
                                    </div>
                                    <i data-feather="play-circle"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Comments Section -->
            <div class="comment-section">
                <h4>Comments (<?= count($comments) ?>)</h4>
                
                <?php if ($currentUser): ?>
                    <form id="comment-form" class="mb-4" data-anime-id="<?= $animeId ?>">
                        <div class="mb-3">
                            <textarea class="form-control" name="comment" rows="3" placeholder="Write a comment..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Comment</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info mb-4">
                        <a href="/login.php">Login</a> to leave a comment.
                    </div>
                <?php endif; ?>
                
                <div class="comments-list">
                    <?php if (empty($comments)): ?>
                        <p class="text-muted">No comments yet. Be the first to comment!</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <span class="comment-user">
                                        <?= !empty($comment['username']) ? '@' . h($comment['username']) : h($comment['first_name']) ?>
                                    </span>
                                    <span class="comment-date">
                                        <?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="comment-text">
                                    <?= nl2br(h($comment['comment'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Related anime could go here in the future -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Status:</strong> <?= ucfirst(h($anime['status'])) ?></p>
                <p><strong>Year:</strong> <?= $anime['year'] ?? 'Unknown' ?></p>
                <p><strong>Episodes:</strong> <?= count($anime['episodes']) ?></p>
                <p><strong>Added:</strong> <?= date('M j, Y', strtotime($anime['created_at'])) ?></p>
                <p><strong>Last updated:</strong> <?= date('M j, Y', strtotime($anime['updated_at'])) ?></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
