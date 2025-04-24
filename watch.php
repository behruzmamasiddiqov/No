<?php
define('ANIDAO', true);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get episode ID from URL
$episodeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($episodeId <= 0) {
    redirect('/');
}

// Get episode details with anime info
$episode = getEpisodeById($db, $episodeId);

if (!$episode) {
    // Episode not found
    header('HTTP/1.0 404 Not Found');
    $pageTitle = 'Episode Not Found';
    include 'includes/header.php';
    echo '<div class="alert alert-danger">The requested episode could not be found.</div>';
    include 'includes/footer.php';
    exit;
}

// Get anime details
$anime = getAnimeById($db, $episode['anime_id']);

// Get next and previous episodes
$nextEpisode = null;
$prevEpisode = null;

foreach ($anime['episodes'] as $key => $ep) {
    if ($ep['id'] == $episodeId) {
        // Get previous episode
        if ($key > 0) {
            $prevEpisode = $anime['episodes'][$key - 1];
        }
        
        // Get next episode
        if ($key < count($anime['episodes']) - 1) {
            $nextEpisode = $anime['episodes'][$key + 1];
        }
        
        break;
    }
}

// Get comments for this episode
$comments = getAnimeComments($db, $episode['anime_id'], $episodeId);

// Get current user
$currentUser = $auth->getCurrentUser();

// If user is logged in, update watch history when page loads
if ($currentUser) {
    updateWatchHistory($db, $currentUser['id'], $episodeId, 0, false);
}

// Set page title
$pageTitle = $anime['title'] . ' - Episode ' . $episode['episode_number'];

// Add player.js to header
$extraHead = '<script src="/assets/js/player.js"></script>';

// Include header
include 'includes/header.php';
?>

<div class="mb-4">
    <div class="d-flex align-items-center mb-3">
        <a href="/anime.php?id=<?= $episode['anime_id'] ?>" class="text-decoration-none text-white">
            <i data-feather="arrow-left" class="me-2"></i> Back to <?= h($anime['title']) ?>
        </a>
    </div>
    
    <h1 class="mb-3"><?= h($anime['title']) ?> - Episode <?= $episode['episode_number'] ?></h1>
    
    <?php if (!empty($episode['title'])): ?>
        <h5 class="text-muted mb-4"><?= h($episode['title']) ?></h5>
    <?php endif; ?>
    
<!-- Video Player -->
    <div class="video-container" data-episode-id="<?= $episodeId ?>">
        <iframe src="https://iframe.mediadelivery.net/play/<?= h($episode['bunny_stream_id']) ?>" 
                allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen></iframe>
    </div>
    
    <!-- Episode Navigation -->
    <div class="d-flex justify-content-between mb-4">
        <?php if ($prevEpisode): ?>
            <a href="/watch.php?id=<?= $prevEpisode['id'] ?>" class="btn btn-outline-primary">
                <i data-feather="chevron-left"></i> Previous Episode
            </a>
        <?php else: ?>
            <div></div> <!-- Placeholder for flex alignment -->
        <?php endif; ?>
        
        <?php if ($nextEpisode): ?>
            <a href="/watch.php?id=<?= $nextEpisode['id'] ?>" class="btn btn-primary">
                Next Episode <i data-feather="chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Episode List Quick Navigation -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Episodes</h5>
            <a href="/anime.php?id=<?= $episode['anime_id'] ?>" class="text-decoration-none">View All</a>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach (array_slice($anime['episodes'], 0, 12) as $ep): ?>
                    <div class="col-md-2 col-4 mb-2">
                        <a href="/watch.php?id=<?= $ep['id'] ?>" 
                           class="btn btn-sm <?= $ep['id'] == $episodeId ? 'btn-primary' : 'btn-outline-light' ?> w-100">
                            <?= $ep['episode_number'] ?>
                        </a>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($anime['episodes']) > 12): ?>
                    <div class="col-md-2 col-4 mb-2">
                        <a href="/anime.php?id=<?= $episode['anime_id'] ?>" class="btn btn-sm btn-outline-light w-100">
                            More...
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Comments Section -->
    <div class="comment-section">
        <h4>Comments (<?= count($comments) ?>)</h4>
        
        <?php if ($currentUser): ?>
            <form id="comment-form" class="mb-4" data-anime-id="<?= $episode['anime_id'] ?>" data-episode-id="<?= $episodeId ?>">
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

<?php include 'includes/footer.php'; ?>
