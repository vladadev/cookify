<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';

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
                $pdo->prepare('INSERT INTO ingredients (name, unit) VALUES (?, ?)')->execute([$name, $unit]);
            } else {
                $ing_id = (int) $_POST['ing_id'];
                $pdo->prepare('UPDATE ingredients SET name = ?, unit = ? WHERE id = ?')->execute([$name, $unit, $ing_id]);
            }
            header('Location: ' . BASE_URL . '/admin/ingredients.php');
            exit;
        }
    } elseif ($action === 'delete') {
        $ing_id = (int) $_POST['ing_id'];
        try {
            $pdo->prepare('DELETE FROM ingredients WHERE id = ?')->execute([$ing_id]);
        } catch (PDOException $e) {
            $errors[] = 'Cannot delete: this ingredient is used in one or more recipes.';
        }
        if (empty($errors)) {
            header('Location: ' . BASE_URL . '/admin/ingredients.php');
            exit;
        }
    }
}

if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM ingredients WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

$ingredients = $pdo->query('
    SELECT i.*, COUNT(ri.recipe_id) AS recipe_count
    FROM ingredients i
    LEFT JOIN recipe_ingredients ri ON ri.ingredient_id = i.id
    GROUP BY i.id
    ORDER BY i.name
')->fetchAll();

$page_title = 'Manage Ingredients';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="admin-header">
    <h1>Manage Ingredients</h1>
    <nav class="admin-nav">
        <a href="<?= BASE_URL ?>/admin/index.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/admin/users.php">Users</a>
        <a href="<?= BASE_URL ?>/admin/recipes.php">Recipes</a>
        <a href="<?= BASE_URL ?>/admin/categories.php">Categories</a>
        <a href="<?= BASE_URL ?>/admin/ingredients.php" class="active">Ingredients</a>
        <a href="<?= BASE_URL ?>/admin/comments.php">Comments</a>
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
                <label for="unit">Unit <small>(e.g. g, ml, pcs, tbsp, tsp)</small></label>
                <input type="text" id="unit" name="unit" value="<?= htmlspecialchars($edit['unit'] ?? $_POST['unit'] ?? '') ?>" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $edit ? 'Save Changes' : 'Add Ingredient' ?></button>
                <?php if ($edit): ?>
                    <a href="<?= BASE_URL ?>/admin/ingredients.php" class="btn btn-secondary">Cancel</a>
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
                        <td><?= htmlspecialchars($ing['name']) ?></td>
                        <td><?= htmlspecialchars($ing['unit']) ?></td>
                        <td><?= $ing['recipe_count'] ?> recipe(s)</td>
                        <td class="table-actions">
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

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
