<?php
// Prevent direct access to this file
if (!defined('ANIDAO')) {
    die('Direct access not permitted');
}

$currentUser = $auth->getCurrentUser();
$isAdmin = $auth->isAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' - ' . SITE_NAME : SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Favicon qo'shish -->
    <link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">
    
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="/">
    <div class="site-logo d-flex align-items-center">
        <img src="/assets/img/logo.jpeg" alt="Logo" height="32" class="me-0">
        <span class="brand-text">ANI DAO</span>
    </div>
</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/"><i data-feather="home"></i> Home</a>
                        </li>
                        <?php if ($currentUser): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/favorites.php"><i data-feather="heart"></i> Favorites</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/history.php"><i data-feather="clock"></i> History</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <form class="d-flex me-2" action="/" method="get">
                        <div class="input-group">
                            <input class="form-control" type="search" name="search" placeholder="Search anime..." 
                                   value="<?= isset($_GET['search']) ? h($_GET['search']) : '' ?>">
                            <button class="btn btn-outline-light" type="submit">
                                <i data-feather="search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($currentUser): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                                <?= !empty($currentUser['username']) ? '@' . h($currentUser['username']) : h($currentUser['first_name']) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/profile.php"><i data-feather="user"></i> Profile</a></li>
                                <?php if ($isAdmin): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#"><i data-feather="shield"></i> Admin (via Telegram)</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/login.php?logout=1"><i data-feather="log-out"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="/login.php" class="btn btn-outline-light">
                            <i data-feather="log-in"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container py-4">
