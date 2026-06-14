<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/models/categories.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_admin();
log_access();

$pdo    = get_db();
$errors = [];
$edit   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (empty($name)) $errors[] = 'Category name is required.';

        if (empty($errors)) {
            if ($action === 'create') {
                insert_category($pdo, $name, $desc);
            } else {
                update_category($pdo, (int) $_POST['cat_id'], $name, $desc);
            }
            header('Location: ' . BASE_URL . '/views/admin/categories.php');
            exit;
        }
    } elseif ($action === 'delete') {
        try {
            delete_category($pdo, (int) $_POST['cat_id']);
        } catch (PDOException $e) {
            $errors[] = 'Cannot delete: this category has recipes assigned to it.';
        }
        if (empty($errors)) {
            header('Location: ' . BASE_URL . '/views/admin/categories.php');
            exit;
        }
    }
}

if (isset($_GET['edit'])) {
    $edit = get_category_by_id($pdo, (int) $_GET['edit']);
}

$categories = get_categories_with_count($pdo);
$page_title = 'Manage Categories';

require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="admin-header">
    <h1>Manage Categories</h1>
    <nav class="admin-nav">
        <a href="<?= BASE_URL ?>/views/admin/index.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/views/admin/users.php">Users</a>
        <a href="<?= BASE_URL ?>/views/admin/recipes.php">Recipes</a>
        <a href="<?= BASE_URL ?>/views/admin/categories.php" class="active">Categories</a>
        <a href="<?= BASE_URL ?>/views/admin/ingredients.php">Ingredients</a>
        <a href="<?= BASE_URL ?>/views/admin/comments.php">Comments</a>
    </nav>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="two-col">
    <div>
        <h2><?= $edit ? 'Edit Category' : 'Add Category' ?></h2>
        <form method="POST" action="" class="admin-form">
            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
            <?php if ($edit): ?>
                <input type="hidden" name="cat_id" value="<?= $edit['id'] ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($edit['name'] ?? $_POST['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $edit ? 'Save Changes' : 'Add Category' ?></button>
                <?php if ($edit): ?>
                    <a href="<?= BASE_URL ?>/views/admin/categories.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div>
        <h2>All Categories</h2>
        <table class="data-table">
            <thead><tr><th>Name</th><th>Recipes</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td data-label="Name"><?= htmlspecialchars($cat['name']) ?></td>
                        <td data-label="Recipes"><?= $cat['recipe_count'] ?></td>
                        <td data-label="Actions" class="table-actions">
                            <a href="?edit=<?= $cat['id'] ?>" class="btn btn-small btn-secondary">Edit</a>
                            <form method="POST" action="" style="display:inline"
                                  onsubmit="return confirm('Delete this category?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn btn-small btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
