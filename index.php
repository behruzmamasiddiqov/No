<?php
define('ANIDAO', true);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Set page title
$pageTitle = 'Home';

// Get filters from URL parameters
$filters = [
    'search' => isset($_GET['search']) ? sanitizeInput($_GET['search']) : '',
    'genre' => isset($_GET['genre']) ? (int)$_GET['genre'] : null,
    'year' => isset($_GET['year']) ? (int)$_GET['year'] : null,
    'status' => isset($_GET['status']) ? sanitizeInput($_GET['status']) : null
];

// Get page number
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;

// Get anime list with pagination
$animeList = getAnimeList($db, $filters, $page, $perPage);

// Get genres for filter
$genres = getAllGenres($db);

// Count total anime for pagination
$params = [];
$whereConditions = [];

if (!empty($filters['search'])) {
    $whereConditions[] = "title LIKE ?";
    $params[] = '%' . $filters['search'] . '%';
}

if (!empty($filters['year'])) {
    $whereConditions[] = "year = ?";
    $params[] = $filters['year'];
}

if (!empty($filters['genre'])) {
    $whereConditions[] = "id IN (SELECT anime_id FROM anime_genres WHERE genre_id = ?)";
    $params[] = $filters['genre'];
}

if (!empty($filters['status'])) {
    $whereConditions[] = "status = ?";
    $params[] = $filters['status'];
}

$countSql = "SELECT COUNT(DISTINCT id) as total FROM anime";
if (!empty($whereConditions)) {
    $countSql .= " WHERE " . implode(" AND ", $whereConditions);
}

$totalCount = $db->fetch($countSql, $params)['total'];
$totalPages = ceil($totalCount / $perPage);

// Get current user
$currentUser = $auth->getCurrentUser();

// Insert sample data if the database is empty
insertSampleDataIfEmpty($db);

// Get featured anime for the slider
$featuredAnime = getFeaturedAnime($db, 7);

// Get recently updated anime
$recentlyUpdated = getRecentlyUpdatedAnime($db, 4);

// Get shares count for featured anime
$shares = 0;

if (!empty($featuredAnime)) {
    $featuredId = $featuredAnime[0]['id'];
    $shareResult = $db->fetch("SELECT count FROM shares WHERE anime_id = 1");
    if ($shareResult) {
        $shares = $shareResult['count'];
    }
}

// Include header
include 'includes/header.php';
?>

<!-- Featured Slider -->
<div class="featured-slider">
    <?php if (!empty($featuredAnime)): ?>
        <?php foreach ($featuredAnime as $index => $anime): ?>
            <div class="featured-slide" style="display: <?= $index === 0 ? 'block' : 'none' ?>;">
                <a href="/anime.php?id=<?= $anime['id'] ?>">
                    <img src="<?= h($anime['cover_image']) ?>" alt="<?= h($anime['title']) ?>">
                    <div class="slide-overlay">
                        <div class="slide-title"><?= h($anime['title']) ?></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="featured-slide">
            <div class="bg-secondary d-flex align-items-center justify-content-center" style="height: 300px;">
                <span>No featured anime available</span>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="slider-arrow left">
        <i data-feather="chevron-left"></i>
    </div>
    <div class="slider-arrow right">
        <i data-feather="chevron-right"></i>
    </div>
    
    <div class="slider-dots">
        <?php for ($i = 0; $i < count($featuredAnime); $i++): ?>
            <div class="slider-dot <?= $i === 0 ? 'active' : '' ?>"></div>
        <?php endfor; ?>
    </div>
</div>

<!-- Social Share -->
<div class="social-share">
    <div class="share-count">
        <strong><?= number_format($shares / 1000, 1) ?>k</strong>
        <span>Ulashish</span>
    </div>
    <div class="share-button share-telegram">
        <i data-feather="send"></i>
    </div>
    <div class="share-button share-twitter">
        <i data-feather="twitter"></i>
    </div>
    <div class="share-button share-facebook">
        <i data-feather="facebook"></i>
    </div>
    <div class="share-button share-reddit">
        <i data-feather="message-circle"></i>
    </div>
    <div class="share-button share-other">
        <i data-feather="share-2"></i>
    </div>
</div>

<!-- Recently Updated Section -->
<h2 class="section-title">So'nggi yuklanganlar</h2>
<div class="recent-grid mb-5">
    <?php if (!empty($recentlyUpdated)): ?>
        <?php foreach ($recentlyUpdated as $anime): ?>
            <div class="recent-item">
                <a href="/anime.php?id=<?= $anime['id'] ?>">
                    <div class="hd-badge">HD</div>
                    <img src="<?= h($anime['cover_image']) ?>" alt="<?= h($anime['title']) ?>" class="recent-img">
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info w-100">No recently updated anime available</div>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Filters Sidebar -->
    <div class="col-md-3 mb-4">
        <div class="filter-card">
            <h5 class="filter-title">Saralash</h5>
            <form action="/" method="get">
                <?php if (!empty($filters['search'])): ?>
                    <input type="hidden" name="search" value="<?= h($filters['search']) ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="genre" class="form-label">Janr</label>
                    <select class="form-select" id="genre" name="genre">
                        <option value="">Barcha janrlar</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?= $genre['id'] ?>" <?= $filters['genre'] == $genre['id'] ? 'selected' : '' ?>>
                                <?= h($genre['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="year" class="form-label">Yil</label>
                    <select class="form-select" id="year" name="year">
                        <option value="">Barcha yildagi</option>
                        <?php for ($y = date('Y'); $y >= 2000; $y--): ?>
                            <option value="<?= $y ?>" <?= $filters['year'] == $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Barchasi</option>
                        <option value="ongoing" <?= $filters['status'] == 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                        <option value="completed" <?= $filters['status'] == 'completed' ? 'selected' : '' ?>>Tugallangan</option>
                        <option value="upcoming" <?= $filters['status'] == 'upcoming' ? 'selected' : '' ?>>Kelayotgan</option>
                    </select>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Filterlash</button>
                    <a href="/" class="btn btn-outline-secondary mt-2">Filterni tozalash</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Anime Grid -->
    <div class="col-md-9">
        <?php if (!empty($filters['search'])): ?>
            <h3 class="mb-4">Qidiruv natijalari: <?= h($filters['search']) ?></h3>
        <?php else: ?>
            <h3 class="mb-4"><?= !empty($filters) && ($filters['genre'] || $filters['year'] || $filters['status']) ? 'Filtrlangan animelar' : 'Barcha animelar' ?></h3>
        <?php endif; ?>
        
        <?php if (empty($animeList)): ?>
            <div class="alert alert-info">
                Hech qanday anime topilmadi. Boshqa filtrlarni sinab ko'ring yoki keyinroq tekshiring.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($animeList as $anime): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card h-100">
                            <?php if ($currentUser): ?>
                                <div class="favorite-badge favorite-toggle <?= isAnimeFavorite($db, $currentUser['id'], $anime['id']) ? 'active' : '' ?>" 
                                     data-anime-id="<?= $anime['id'] ?>">
                                    <?php if (isAnimeFavorite($db, $currentUser['id'], $anime['id'])): ?>
                                        <i data-feather="heart-fill"></i>
                                    <?php else: ?>
                                        <i data-feather="heart"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
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
                                    <div>
                                        <?php if ($anime['avg_rating']): ?>
                                            <span class="text-warning"><i data-feather="star"></i> <?= number_format($anime['avg_rating'], 1) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">Episodes: <?= $anime['episode_count'] ?? 0 ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <!-- Previous button -->
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <!-- Page numbers -->
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        // Always show first page
                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                        }
                        
                        // Show page numbers
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">
                                <a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a>
                            </li>';
                        }
                        
                        // Always show last page
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '">' . $totalPages . '</a></li>';
                        }
                        ?>
                        
                        <!-- Next button -->
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
