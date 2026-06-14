<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/models/recipes.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_admin();
log_access();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    delete_recipe($pdo, (int) $_POST['delete_id']);
    header('Location: ' . BASE_URL . '/views/admin/recipes.php');
    exit;
}

$recipes    = get_all_recipes_admin($pdo);
$page_title = 'Manage Recipes';

require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="admin-header">
    <h1>Manage Recipes</h1>
    <nav class="admin-nav">
        <a href="<?= BASE_URL ?>/views/admin/index.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/views/admin/users.php">Users</a>
        <a href="<?= BASE_URL ?>/views/admin/recipes.php" class="active">Recipes</a>
        <a href="<?= BASE_URL ?>/views/admin/categories.php">Categories</a>
        <a href="<?= BASE_URL ?>/views/admin/ingredients.php">Ingredients</a>
        <a href="<?= BASE_URL ?>/views/admin/comments.php">Comments</a>
    </nav>
</div>

<div class="table-scroll"><table class="data-table">
    <thead>
        <tr><th>ID</th><th>Title</th><th>Author</th><th>Category</th><th>Difficulty</th><th>Added</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php foreach ($recipes as $r): ?>
            <tr>
                <td data-label="ID"><?= $r['id'] ?></td>
                <td data-label="Title"><a href="<?= BASE_URL ?>/views/recipe.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></a></td>
                <td data-label="Author"><?= htmlspecialchars($r['author_name']) ?></td>
                <td data-label="Category"><?= htmlspecialchars($r['category_name']) ?></td>
                <td data-label="Difficulty"><span class="tag tag-<?= $r['difficulty'] ?>"><?= ucfirst($r['difficulty']) ?></span></td>
                <td data-label="Added"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                <td data-label="Actions" class="table-actions">
                    <a href="<?= BASE_URL ?>/views/edit_recipe.php?id=<?= $r['id'] ?>" class="btn btn-small btn-secondary">Edit</a>
                    <form method="POST" action="" style="display:inline"
                          onsubmit="return confirm('Delete this recipe?')">
                        <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table></div>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
