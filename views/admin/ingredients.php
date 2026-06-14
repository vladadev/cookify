<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/models/ingredients.php';

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
        $unit = trim($_POST['unit'] ?? '');
        if (empty($name)) $errors[] = 'Ingredient name is required.';
        if (empty($unit)) $errors[] = 'Unit is required.';

        if (empty($errors)) {
            if ($action === 'create') {
                insert_ingredient($pdo, $name, $unit);
            } else {
                update_ingredient($pdo, (int) $_POST['ing_id'], $name, $unit);
            }
            header('Location: ' . BASE_URL . '/views/admin/ingredients.php');
            exit;
        }
    } elseif ($action === 'delete') {
        try {
            delete_ingredient($pdo, (int) $_POST['ing_id']);
        } catch (PDOException $e) {
            $errors[] = 'Cannot delete: this ingredient is used in one or more recipes.';
        }
        if (empty($errors)) {
            header('Location: ' . BASE_URL . '/views/admin/ingredients.php');
            exit;
        }
    }
}

if (isset($_GET['edit'])) {
    $edit = get_ingredient_by_id($pdo, (int) $_GET['edit']);
}

$ingredients = get_ingredients_with_count($pdo);
$page_title  = 'Manage Ingredients';

require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="admin-header">
    <h1>Manage Ingredients</h1>
    <nav class="admin-nav">
        <a href="<?= BASE_URL ?>/views/admin/index.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/views/admin/users.php">Users</a>
        <a href="<?= BASE_URL ?>/views/admin/recipes.php">Recipes</a>
        <a href="<?= BASE_URL ?>/views/admin/categories.php">Categories</a>
        <a href="<?= BASE_URL ?>/views/admin/ingredients.php" class="active">Ingredients</a>
        <a href="<?= BASE_URL ?>/views/admin/comments.php">Comments</a>
    </nav>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="two-col">
    <div>
        <h2><?= $edit ? 'Edit Ingredient' : 'Add Ingredient' ?></h2>
        <form method="POST" action="" class="admin-form">
            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
            <?php if ($edit): ?>
                <input type="hidden" name="ing_id" value="<?= $edit['id'] ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($edit['name'] ?? $_POST['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="unit">Unit <small>(e.g. g, ml, pcs, tbsp)</small></label>
                <input type="text" id="unit" name="unit" value="<?= htmlspecialchars($edit['unit'] ?? $_POST['unit'] ?? '') ?>" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $edit ? 'Save Changes' : 'Add Ingredient' ?></button>
                <?php if ($edit): ?>
                    <a href="<?= BASE_URL ?>/views/admin/ingredients.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div>
        <h2>All Ingredients (<?= count($ingredients) ?>)</h2>
        <table class="data-table">
            <thead><tr><th>Name</th><th>Unit</th><th>Used in</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($ingredients as $ing): ?>
                    <tr>
                        <td data-label="Name"><?= htmlspecialchars($ing['name']) ?></td>
                        <td data-label="Unit"><?= htmlspecialchars($ing['unit']) ?></td>
                        <td data-label="Used in"><?= $ing['recipe_count'] ?> recipe(s)</td>
                        <td data-label="Actions" class="table-actions">
                            <a href="?edit=<?= $ing['id'] ?>" class="btn btn-small btn-secondary">Edit</a>
                            <form method="POST" action="" style="display:inline"
                                  onsubmit="return confirm('Delete this ingredient?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="ing_id" value="<?= $ing['id'] ?>">
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
