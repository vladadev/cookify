<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/includes/mailer.php';
require_once ROOT . '/models/users.php';

if (session_status() === PHP_SESSION_NONE) session_start();
log_access();

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/views/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $errors[] = 'Please enter your email and password.';
    } else {
        $pdo  = get_db();
        $user = get_user_by_email($pdo, $email);

        if (!$user || !$user['is_active']) {
            $errors[] = 'Invalid credentials or account not activated.';
        } elseif ($user['is_locked']) {
            $errors[] = 'Your account is locked. Please contact the administrator.';
        } else {
            $now      = new DateTime();
            $last_fail = $user['last_failed_at'] ? new DateTime($user['last_failed_at']) : null;
            $minutes  = $last_fail ? ($now->getTimestamp() - $last_fail->getTimestamp()) / 60 : PHP_INT_MAX;

            if ($minutes > LOGIN_LOCKOUT_MINUTES) {
                reset_failed_attempts($pdo, $user['id']);
                $user['failed_attempts'] = 0;
            }

            if (password_verify($password, $user['password_hash'])) {
                record_login($pdo, $user['id']);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                header('Location: ' . BASE_URL . '/views/index.php');
                exit;
            } else {
                $new_attempts = $user['failed_attempts'] + 1;
                record_failed_login($pdo, $user['id'], $new_attempts);

                if ($new_attempts >= LOGIN_MAX_ATTEMPTS && $minutes <= LOGIN_LOCKOUT_MINUTES) {
                    lock_user($pdo, $user['id']);
                    send_lock_warning_email($user['email'], $user['name']);
                    $errors[] = 'Your account has been locked due to too many failed attempts.';
                } else {
                    $remaining = LOGIN_MAX_ATTEMPTS - $new_attempts;
                    $errors[]  = 'Invalid email or password. ' . ($remaining > 0 ? "{$remaining} attempt(s) remaining before lockout." : '');
                }
            }
        }
    }
}

$page_title = 'Login';
require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="auth-box">
    <h1>Log In</h1>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Log In</button>
    </form>
    <p class="auth-link">Don't have an account? <a href="<?= BASE_URL ?>/views/register.php">Register</a></p>
</div>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
