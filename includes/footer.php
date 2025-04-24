<?php
// Prevent direct access to this file
if (!defined('ANIDAO')) {
    die('Direct access not permitted');
}
?>

    </main>

    <!-- Player Responsive CSS -->
    <style>
    .video-container {
        position: relative !important;
        width: 100% !important;
        max-width: 100% !important;
        padding-bottom: 56.25% !important; /* 16:9 aspect ratio */
        height: 0 !important;
        overflow: hidden !important;
        background-color: black !important;
    }

    .video-container iframe {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        border: none !important;
    }
    </style>

    <footer class="footer mt-auto py-3">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>ANI DAO</h5>
                    <p class="text-muted">Siz yoqtirgan eng yaxshi animelar makoni!</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Telegram orqali biz bilan bog'lanish: <a href="https://t.me/<?= TELEGRAM_BOT_USERNAME ?>" target="_blank">@<?= TELEGRAM_BOT_USERNAME ?></a></p>
                    <p class="mb-0 text-muted">&copy; <?= date('Y') ?> ANI DAO. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Feather icons
        feather.replace();
    </script>
    <script src="/assets/js/main.js"></script>
</body>
</html>