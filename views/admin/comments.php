<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/models/comments.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_admin();
log_access();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    delete_comment($pdo, (int) $_POST['delete_id']);
    header('Location: ' . BASE_URL . '/views/admin/comments.php');
    exit;
}

$comments   = get_all_comments_admin($pdo);
$page_title = 'Manage Comments';

require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="admin-header">
    <h1>Manage Comments</h1>
    <nav class="admin-nav">
        <a href="<?= BASE_URL ?>/views/admin/index.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/views/admin/users.php">Users</a>
        <a href="<?= BASE_URL ?>/views/admin/recipes.php">Recipes</a>
        <a href="<?= BASE_URL ?>/views/admin/categories.php">Categories</a>
        <a href="<?= BASE_URL ?>/views/admin/ingredients.php">Ingredients</a>
        <a href="<?= BASE_URL ?>/views/admin/comments.php" class="active">Comments</a>
    </nav>
</div>

<div class="table-scroll"><table class="data-table">
    <thead>
        <tr><th>User</th><th>Recipe</th><th>Comment</th><th>Date</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php foreach ($comments as $c): ?>
            <tr>
                <td data-label="User"><?= htmlspecialchars($c['user_name']) ?></td>
                <td data-label="Recipe"><a href="<?= BASE_URL ?>/views/recipe.php?id=<?= $c['recipe_id'] ?>"><?= htmlspecialchars($c['recipe_title']) ?></a></td>
                <td data-label="Comment"><?= htmlspecialchars(mb_substr($c['body'], 0, 80)) ?><?= mb_strlen($c['body']) > 80 ? '…' : '' ?></td>
                <td data-label="Date"><?= date('M j, Y H:i', strtotime($c['created_at'])) ?></td>
                <td data-label="Actions">
                    <form method="POST" action="" onsubmit="return confirm('Delete this comment?')">
                        <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table></div>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
