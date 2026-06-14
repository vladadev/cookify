<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

if (session_status() === PHP_SESSION_NONE) session_start();
log_access();

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/pages/index.php');
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
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_active']) {
            $errors[] = 'Invalid credentials or account not activated.';
        } elseif ($user['is_locked']) {
            $errors[] = 'Your account is locked. Please contact the administrator.';
        } else {
            $now       = new DateTime();
            $last_fail = $user['last_failed_at'] ? new DateTime($user['last_failed_at']) : null;
            $minutes   = $last_fail ? ($now->getTimestamp() - $last_fail->getTimestamp()) / 60 : PHP_INT_MAX;

            if ($minutes > LOGIN_LOCKOUT_MINUTES) {
                $pdo->prepare('UPDATE users SET failed_attempts = 0 WHERE id = ?')->execute([$user['id']]);
                $user['failed_attempts'] = 0;
            }

            if (password_verify($password, $user['password_hash'])) {
                $pdo->prepare('UPDATE users SET failed_attempts = 0, last_failed_at = NULL, last_login_at = NOW() WHERE id = ?')
                    ->execute([$user['id']]);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                header('Location: ' . BASE_URL . '/pages/index.php');
                exit;
            } else {
                $new_attempts = $user['failed_attempts'] + 1;
                $pdo->prepare('UPDATE users SET failed_attempts = ?, last_failed_at = NOW() WHERE id = ?')
                    ->execute([$new_attempts, $user['id']]);

                if ($new_attempts >= LOGIN_MAX_ATTEMPTS && $minutes <= LOGIN_LOCKOUT_MINUTES) {
                    $pdo->prepare('UPDATE users SET is_locked = 1 WHERE id = ?')->execute([$user['id']]);
                    send_lock_warning_email($user['email'], $user['name']);
                    $errors[] = 'Your account has been locked due to too many failed attempts. You will receive an email notification.';
                } else {
                    $remaining = LOGIN_MAX_ATTEMPTS - $new_attempts;
                    $errors[]  = 'Invalid email or password. ' . ($remaining > 0 ? "{$remaining} attempt(s) remaining before lockout." : '');
                }
            }
        }
    }
}

$page_title = 'Login';
require_once dirname(__DIR__) . '/includes/header.php';
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
    <p class="auth-link">Don't have an account? <a href="<?= BASE_URL ?>/pages/register.php">Register</a></p>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
