<?php
define('ANIDAO', true);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Handle logout
if (isset($_GET['logout'])) {
    $auth->logout();
    redirect('/');
}

// Check if user is already logged in
if ($auth->isLoggedIn() && !isset($_GET['logout'])) {
    redirect('/');
}

// Generate verification code
$session = $auth->createSessionWithCode();
$verificationCode = $session['code'];

// Set temporary session token cookie
setcookie('temp_session_token', $session['token'], time() + 3600, '/'); // 1 hour expiry

// Set page title
$pageTitle = 'Login';

// Include header
include 'includes/header.php';
?>

<div class="login-container">
    <h2>Login to ANI DAO</h2>
    <p>Use Telegram to log in securely to your account.</p>
    
    <div class="verification-code"><?= $verificationCode ?></div>
    
    <p>Enter this code in our Telegram bot to log in.</p>
    
    <a href="https://t.me/<?= TELEGRAM_BOT_USERNAME ?>?start=<?= $verificationCode ?>" target="_blank" class="telegram-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21.198 2.433a2.242 2.242 0 0 0-1.022.215l-16.5 7.5a2.25 2.25 0 0 0 .126 4.073l3.9 1.205 2.25 6a2.25 2.25 0 0 0 4.153.4l2.01-4.03 4.044 2.441a2.25 2.25 0 0 0 3.312-1.06l3.75-16.5a2.247 2.247 0 0 0-1.92-2.245z"></path>
        </svg>
        Login with Telegram
    </a>
    
    <div class="mt-4">
        <p id="status-message">Waiting for authentication...</p>
        <div class="progress" style="height: 4px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkVerification = () => {
        fetch('/verify.php?code=<?= $verificationCode ?>', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('status-message').innerHTML = 'Authentication successful! Redirecting...';
                window.location.href = '/';
            }
        })
        .catch(error => {
            console.error('Error checking verification:', error);
        });
    };
    
    // Check every 3 seconds
    const interval = setInterval(checkVerification, 3000);
    
    // Stop checking after 15 minutes (timeout)
    setTimeout(() => {
        clearInterval(interval);
        document.getElementById('status-message').innerHTML = 'Verification timeout. Please refresh the page to try again.';
        document.querySelector('.progress').style.display = 'none';
    }, 15 * 60 * 1000);
});
</script>

<?php include 'includes/footer.php'; ?>
