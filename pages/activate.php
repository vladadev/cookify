<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';

if (session_status() === PHP_SESSION_NONE) session_start();
log_access();

$token   = trim($_GET['token'] ?? '');
$message = '';
$success = false;

if ($token === '') {
    $message = 'Invalid activation link.';
} else {
    $pdo  = get_db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE activation_token = ? AND is_active = 0');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $pdo->prepare('UPDATE users SET is_active = 1, activation_token = NULL WHERE id = ?')
            ->execute([$user['id']]);
        $success = true;
        $message = 'Your account has been activated. You can now log in.';
    } else {
        $message = 'This activation link is invalid or has already been used.';
    }
}

$page_title = 'Account Activation';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="auth-box">
    <h1>Account Activation</h1>
    <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php if ($success): ?>
        <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-primary">Go to Login</a>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
