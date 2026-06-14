<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_admin();
log_access();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $pdo->prepare('DELETE FROM recipes WHERE id = ?')->execute([(int)$_POST['delete_id']]);
    header('Location: ' . BASE_URL . '/admin/recipes.php');
    exit;
}

$recipes = $pdo->query('
    SELECT r.id, r.title, r.difficulty, r.prep_time, r.created_at,
           c.name AS category_name, u.name AS author_name
    FROM recipes r
    JOIN categories c ON c.id = r.category_id
    JOIN users u      ON u.id = r.user_id
    ORDER BY r.created_at DESC
')->fetchAll();

$page_title = 'Manage Recipes';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="admin-header">
    <h1>Manage Recipes</h1>
    <nav class="admin-nav">
        <a href="<?= BASE_URL ?>/admin/index.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/admin/users.php">Users</a>
        <a href="<?= BASE_URL ?>/admin/recipes.php" class="active">Recipes</a>
        <a href="<?= BASE_URL ?>/admin/categories.php">Categories</a>
        <a href="<?= BASE_URL ?>/admin/ingredients.php">Ingredients</a>
        <a href="<?= BASE_URL ?>/admin/comments.php">Comments</a>
    </nav>
</div>

<table class="data-table">
    <thead>
        <tr><th>ID</th><th>Title</th><th>Author</th><th>Category</th><th>Difficulty</th><th>Added</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php foreach ($recipes as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><a href="<?= BASE_URL ?>/pages/recipe.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></a></td>
                <td><?= htmlspecialchars($r['author_name']) ?></td>
                <td><?= htmlspecialchars($r['category_name']) ?></td>
                <td><span class="tag tag-<?= $r['difficulty'] ?>"><?= ucfirst($r['difficulty']) ?></span></td>
                <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                <td class="table-actions">
                    <a href="<?= BASE_URL ?>/pages/edit_recipe.php?id=<?= $r['id'] ?>" class="btn btn-small btn-secondary">Edit</a>
                    <form method="POST" action="" style="display:inline"
                          onsubmit="return confirm('Delete this recipe?')">
                        <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
