<?php
define('ANIDAO', true);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    redirect('/login.php');
}

// Get current user
$currentUser = $auth->getCurrentUser();

// Get watch history (no limit for full history page)
$watchHistory = getUserWatchHistory($db, $currentUser['id'], 100);

// Set page title
$pageTitle = 'Watch History';

// Include header
include 'includes/header.php';
?>

<div class="mb-4">
    <h1 class="mb-4">Watch History</h1>
    
    <?php if (empty($watchHistory)): ?>
        <div class="alert alert-info">
            <p>You haven't watched any anime yet.</p>
            <a href="/" class="btn btn-primary mt-2">Explore Anime</a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="list-group">
                    <?php foreach ($watchHistory as $history): ?>
                        <a href="/watch.php?id=<?= $history['episode_id'] ?>" class="list-group-item list-group-item-action bg-transparent py-3">
                            <div class="d-flex">
                                <div class="flex-shrink-0" style="width: 120px; height: 70px; overflow: hidden; border-radius: 5px;">
                                    <?php if (!empty($history['cover_image'])): ?>
                                        <div style="width: 100%; height: 100%; background: url('<?= h($history['cover_image']) ?>') no-repeat center center; background-size: cover;"></div>
                                    <?php else: ?>
                                        <div class="bg-secondary d-flex align-items-center justify-content-center h-100">
                                            <i data-feather="film"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?= h($history['anime_title']) ?></h5>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($history['last_watched'])) ?></small>
                                    </div>
                                    <div class="d-flex w-100 justify-content-between">
                                        <p class="mb-1">Episode <?= $history['episode_number'] ?>
                                            <?= !empty($history['episode_title']) ? ' - ' . h($history['episode_title']) : '' ?>
                                        </p>
                                        <small>
                                            <?php if ($history['completed']): ?>
                                                <span class="text-success">Completed</span>
                                            <?php else: ?>
                                                <span><?= formatTime($history['watched_time']) ?></span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <?php
                                    $progress = ($history['completed']) ? 100 : 0;
                                    if (!$history['completed'] && $history['watched_time'] > 0) {
                                        // Assume average episode length of 24 minutes (1440 seconds)
                                        $progress = min(99, round(($history['watched_time'] / 1440) * 100));
                                    }
                                    ?>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?= $progress ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
