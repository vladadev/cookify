<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/includes/mailer.php';
require_once ROOT . '/models/users.php';

if (session_status() === PHP_SESSION_NONE) session_start();
log_access();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    if (empty($name))                                       $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = 'Invalid email address.';
    if (strlen($password) < 8)                              $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)                             $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $pdo = get_db();
        if (get_user_by_email($pdo, $email)) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash  = password_hash($password, PASSWORD_BCRYPT);
            $token = bin2hex(random_bytes(32));
            insert_user($pdo, $name, $email, $hash, $token);
            send_activation_email($email, $name, $token);
            $success = true;
        }
    }
}

$page_title = 'Register';
require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="auth-box">
    <h1>Create an Account</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Registration successful! Please check your email to activate your account.
        </div>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm">Confirm Password</label>
                <input type="password" id="confirm" name="confirm" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Register</button>
        </form>
        <p class="auth-link">Already have an account? <a href="<?= BASE_URL ?>/views/login.php">Log in</a></p>
    <?php endif; ?>
</div>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
