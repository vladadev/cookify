<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/models/users.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_admin();
log_access();

$pdo            = get_db();
$logins_today   = count_logins_today($pdo);
$total_users    = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$total_recipes  = (int) $pdo->query('SELECT COUNT(*) FROM recipes')->fetchColumn();
$total_comments = (int) $pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();

$page_counts  = [];
$total_visits = 0;

if (file_exists(LOG_FILE) && is_readable(LOG_FILE)) {
    $lines = file(LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (preg_match('/\|\s*([^\|]+?)\s*\|\s*IP:/', $line, $m)) {
            $uri = strtok(trim($m[1]), '?');
            $page_counts[$uri] = ($page_counts[$uri] ?? 0) + 1;
            $total_visits++;
        }
    }
    arsort($page_counts);
}

$page_title = 'Admin Dashboard';
require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="admin-header">
    <h1>Admin Dashboard</h1>
    <nav class="admin-nav">
        <a href="<?= BASE_URL ?>/views/admin/index.php" class="active">Dashboard</a>
        <a href="<?= BASE_URL ?>/views/admin/users.php">Users</a>
        <a href="<?= BASE_URL ?>/views/admin/recipes.php">Recipes</a>
        <a href="<?= BASE_URL ?>/views/admin/categories.php">Categories</a>
        <a href="<?= BASE_URL ?>/views/admin/ingredients.php">Ingredients</a>
        <a href="<?= BASE_URL ?>/views/admin/comments.php">Comments</a>
    </nav>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= number_format($total_users) ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= number_format($total_recipes) ?></div>
        <div class="stat-label">Total Recipes</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= number_format($total_comments) ?></div>
        <div class="stat-label">Total Comments</div>
    </div>
    <div class="stat-card highlight">
        <div class="stat-number"><?= number_format($logins_today) ?></div>
        <div class="stat-label">Logins Today</div>
    </div>
</div>

<section class="admin-section">
    <h2>Page Visit Statistics</h2>
    <?php if ($total_visits > 0): ?>
        <table class="data-table">
            <thead><tr><th>Page</th><th>Visits</th><th>%</th><th>Bar</th></tr></thead>
            <tbody>
                <?php foreach (array_slice($page_counts, 0, 20, true) as $uri => $count): ?>
                    <?php $pct = round($count / $total_visits * 100, 1); ?>
                    <tr>
                        <td data-label="Page"><?= htmlspecialchars($uri) ?></td>
                        <td data-label="Visits"><?= $count ?></td>
                        <td data-label="%"><?= $pct ?>%</td>
                        <td data-label="Bar">
                            <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><small>Total visits logged: <?= $total_visits ?></small></p>
    <?php else: ?>
        <p>No visits logged yet.</p>
    <?php endif; ?>
</section>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
