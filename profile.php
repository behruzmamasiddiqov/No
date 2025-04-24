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

// Get user's watch history
$watchHistory = getUserWatchHistory($db, $currentUser['id'], 10);

// Get user's favorites
$favorites = getUserFavorites($db, $currentUser['id']);

// Set page title
$pageTitle = 'My Profile';

// Include header
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <!-- User Profile -->
        <div class="profile-card mb-4">
            <div class="d-flex align-items-center mb-3">
                <?php if (!empty($currentUser['photo_url'])): ?>
                    <img src="<?= h($currentUser['photo_url']) ?>" alt="Profile Picture" class="profile-image">
                <?php else: ?>
                    <div class="profile-image bg-secondary d-flex align-items-center justify-content-center">
                        <i data-feather="user" style="width: 36px; height: 36px;"></i>
                    </div>
                <?php endif; ?>
                
                <div>
                    <h4 class="profile-name mb-0">
                        <?= h($currentUser['first_name']) ?> <?= h($currentUser['last_name']) ?>
                    </h4>
                    <?php if (!empty($currentUser['username'])): ?>
                        <p class="profile-username mb-0">@<?= h($currentUser['username']) ?></p>
                    <?php endif; ?>
                    <p class="text-muted mb-0">Member since <?= date('F Y', strtotime($currentUser['created_at'])) ?></p>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6>Favorites</h6>
                        <h4><?= count($favorites) ?></h4>
                    </div>
                    <div>
                        <h6>Watch History</h6>
                        <h4><?= count($watchHistory) ?></h4>
                    </div>
                    <div>
                        <h6>Comments</h6>
                        <h4>
                            <?php
                            $commentCount = $db->fetch(
                                "SELECT COUNT(*) as count FROM comments WHERE user_id = ?",
                                [$currentUser['id']]
                            )['count'];
                            echo $commentCount;
                            ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Watch History -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Watch History</h5>
                <a href="/history.php" class="text-decoration-none">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($watchHistory)): ?>
                    <p class="text-muted">You haven't watched any anime yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($watchHistory as $history): ?>
                            <a href="/watch.php?id=<?= $history['episode_id'] ?>" class="list-group-item list-group-item-action bg-transparent border-0 py-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0" style="width: 100px; height: 60px; overflow: hidden; border-radius: 5px;">
                                        <?php if (!empty($history['cover_image'])): ?>
                                            <div style="width: 100%; height: 100%; background: url('<?= h($history['cover_image']) ?>') no-repeat center center; background-size: cover;"></div>
                                        <?php else: ?>
                                            <div class="bg-secondary d-flex align-items-center justify-content-center h-100">
                                                <i data-feather="film"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ms-3">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?= h($history['anime_title']) ?></h6>
                                            <small class="text-muted"><?= date('M j', strtotime($history['last_watched'])) ?></small>
                                        </div>
                                        <p class="mb-1">Episode <?= $history['episode_number'] ?>
                                            <?= !empty($history['episode_title']) ? ' - ' . h($history['episode_title']) : '' ?>
                                        </p>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Favorites -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">My Favorites</h5>
                <a href="/favorites.php" class="text-decoration-none">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($favorites)): ?>
                    <p class="text-muted">You haven't added any favorites yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach (array_slice($favorites, 0, 5) as $favorite): ?>
                            <a href="/anime.php?id=<?= $favorite['id'] ?>" class="list-group-item list-group-item-action bg-transparent border-0 py-2">
                                <div class="d-flex">
                                    <div class="flex-shrink-0" style="width: 60px; height: 60px; overflow: hidden; border-radius: 5px;">
                                        <?php if (!empty($favorite['cover_image'])): ?>
                                            <div style="width: 100%; height: 100%; background: url('<?= h($favorite['cover_image']) ?>') no-repeat center center; background-size: cover;"></div>
                                        <?php else: ?>
                                            <div class="bg-secondary d-flex align-items-center justify-content-center h-100">
                                                <i data-feather="film"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-0"><?= h($favorite['title']) ?></h6>
                                        <small class="text-muted">
                                            <?= $favorite['year'] ?? 'Unknown' ?> &middot; 
                                            <?= ucfirst(h($favorite['status'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Account Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Account</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="/login.php?logout=1" class="list-group-item list-group-item-action bg-transparent border-0 text-danger">
                        <i data-feather="log-out" class="me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
