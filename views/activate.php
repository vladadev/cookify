<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/models/users.php';

if (session_status() === PHP_SESSION_NONE) session_start();
log_access();

$token   = trim($_GET['token'] ?? '');
$success = false;
$message = '';

if ($token === '') {
    $message = 'Invalid activation link.';
} else {
    $pdo  = get_db();
    $user = get_user_by_token($pdo, $token);
    if ($user) {
        activate_user($pdo, $user['id']);
        $success = true;
        $message = 'Your account has been activated. You can now log in.';
    } else {
        $message = 'This activation link is invalid or has already been used.';
    }
}

$page_title = 'Account Activation';
require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="auth-box">
    <h1>Account Activation</h1>
    <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php if ($success): ?>
        <a href="<?= BASE_URL ?>/views/login.php" class="btn btn-primary">Go to Login</a>
    <?php endif; ?>
</div>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
