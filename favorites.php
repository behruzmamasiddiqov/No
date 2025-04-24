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

// Get user's favorites
$favorites = getUserFavorites($db, $currentUser['id']);

// Set page title
$pageTitle = 'My Favorites';

// Include header
include 'includes/header.php';
?>

<div class="mb-4">
    <h1 class="mb-4">My Favorites</h1>
    
    <?php if (empty($favorites)): ?>
        <div class="alert alert-info">
            <p>You haven't added any favorite anime yet.</p>
            <a href="/" class="btn btn-primary mt-2">Explore Anime</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($favorites as $anime): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card h-100">
                        <div class="favorite-badge favorite-toggle active" data-anime-id="<?= $anime['id'] ?>">
                            <i data-feather="heart-fill"></i>
                        </div>
                        
                        <a href="/anime.php?id=<?= $anime['id'] ?>">
                            <?php if (!empty($anime['cover_image'])): ?>
                                <div class="card-img-top" style="background: url('<?= h($anime['cover_image']) ?>') no-repeat center center; background-size: cover; height: 250px;"></div>
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 250px;">
                                    <i data-feather="film" style="width: 48px; height: 48px; opacity: 0.5;"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                        
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="/anime.php?id=<?= $anime['id'] ?>" class="text-white text-decoration-none">
                                    <?= h($anime['title']) ?>
                                </a>
                            </h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary"><?= $anime['year'] ?? 'Unknown' ?></span>
                                    <span class="badge bg-<?= $anime['status'] == 'ongoing' ? 'success' : ($anime['status'] == 'completed' ? 'info' : 'warning') ?>">
                                        <?= ucfirst(h($anime['status'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Favorited on <?= date('M j, Y', strtotime($anime['favorited_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
