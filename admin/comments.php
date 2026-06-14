<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_admin();
log_access();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $pdo->prepare('DELETE FROM comments WHERE id = ?')->execute([(int)$_POST['delete_id']]);
    header('Location: ' . BASE_URL . '/admin/comments.php');
    exit;
}

$comments = $pdo->query('
    SELECT cm.*, u.name AS user_name, r.title AS recipe_title, r.id AS recipe_id
    FROM comments cm
    JOIN users u   ON u.id   = cm.user_id
    JOIN recipes r ON r.id   = cm.recipe_id
    ORDER BY cm.created_at DESC
')->fetchAll();

$page_title = 'Manage Comments';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="admin-header">
    <h1>Manage Comments</h1>
    <nav class="admin-nav">
        <a href="<?= BASE_URL ?>/admin/index.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/admin/users.php">Users</a>
        <a href="<?= BASE_URL ?>/admin/recipes.php">Recipes</a>
        <a href="<?= BASE_URL ?>/admin/categories.php">Categories</a>
        <a href="<?= BASE_URL ?>/admin/comments.php" class="active">Comments</a>
    </nav>
</div>

<table class="data-table">
    <thead>
        <tr><th>User</th><th>Recipe</th><th>Comment</th><th>Date</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php foreach ($comments as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['user_name']) ?></td>
                <td><a href="<?= BASE_URL ?>/pages/recipe.php?id=<?= $c['recipe_id'] ?>"><?= htmlspecialchars($c['recipe_title']) ?></a></td>
                <td><?= htmlspecialchars(mb_substr($c['body'], 0, 80)) ?><?= mb_strlen($c['body']) > 80 ? '…' : '' ?></td>
                <td><?= date('M j, Y H:i', strtotime($c['created_at'])) ?></td>
                <td>
                    <form method="POST" action="" onsubmit="return confirm('Delete this comment?')">
                        <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
