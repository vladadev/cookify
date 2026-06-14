<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/models/recipes.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_login();
log_access();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int) $_POST['delete_id'];
    $recipe = get_recipe_by_id($pdo, $del_id);
    if ($recipe && $recipe['user_id'] === current_user_id()) {
        delete_recipe($pdo, $del_id);
    }
    header('Location: ' . BASE_URL . '/views/my_recipes.php');
    exit;
}

$recipes    = get_user_recipes($pdo, current_user_id());
$page_title = 'My Recipes';

require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="page-header">
    <h1>My Recipes</h1>
    <a href="<?= BASE_URL ?>/views/add_recipe.php" class="btn btn-primary">+ Add Recipe</a>
</div>

<?php if (empty($recipes)): ?>
    <div class="empty-state">
        <p>You haven't posted any recipes yet.</p>
        <a href="<?= BASE_URL ?>/views/add_recipe.php" class="btn btn-primary">Add Your First Recipe</a>
    </div>
<?php else: ?>
    <div class="table-scroll"><table class="data-table">
        <thead>
            <tr>
                <th>Title</th><th>Category</th><th>Difficulty</th>
                <th>Prep Time</th><th>Rating</th><th>Posted</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recipes as $r): ?>
                <tr>
                    <td data-label="Title"><a href="<?= BASE_URL ?>/views/recipe.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></a></td>
                    <td data-label="Category"><?= htmlspecialchars($r['category_name']) ?></td>
                    <td data-label="Difficulty"><span class="tag tag-<?= $r['difficulty'] ?>"><?= ucfirst($r['difficulty']) ?></span></td>
                    <td data-label="Prep Time"><?= $r['prep_time'] ?> min</td>
                    <td data-label="Rating"><?= $r['avg_rating'] ?? '—' ?></td>
                    <td data-label="Posted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
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
<?php endif; ?>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
