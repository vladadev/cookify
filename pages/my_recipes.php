<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_login();
log_access();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int) $_POST['delete_id'];
    $stmt   = $pdo->prepare('SELECT id FROM recipes WHERE id = ? AND user_id = ?');
    $stmt->execute([$del_id, current_user_id()]);
    if ($stmt->fetch()) {
        $pdo->prepare('DELETE FROM recipes WHERE id = ?')->execute([$del_id]);
    }
    header('Location: ' . BASE_URL . '/pages/my_recipes.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT r.*, c.name AS category_name,
           ROUND(AVG(rt.score), 1) AS avg_rating
    FROM recipes r
    JOIN categories c ON c.id = r.category_id
    LEFT JOIN ratings rt ON rt.recipe_id = r.id
    WHERE r.user_id = ?
    GROUP BY r.id
    ORDER BY r.created_at DESC
');
$stmt->execute([current_user_id()]);
$recipes = $stmt->fetchAll();

$page_title = 'My Recipes';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <h1>My Recipes</h1>
    <a href="<?= BASE_URL ?>/pages/add_recipe.php" class="btn btn-primary">+ Add Recipe</a>
</div>

<?php if (empty($recipes)): ?>
    <div class="empty-state">
        <p>You haven't posted any recipes yet.</p>
        <a href="<?= BASE_URL ?>/pages/add_recipe.php" class="btn btn-primary">Add Your First Recipe</a>
    </div>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Difficulty</th>
                <th>Prep Time</th>
                <th>Rating</th>
                <th>Posted</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recipes as $r): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/pages/recipe.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></a></td>
                    <td><?= htmlspecialchars($r['category_name']) ?></td>
                    <td><span class="tag tag-<?= $r['difficulty'] ?>"><?= ucfirst($r['difficulty']) ?></span></td>
                    <td><?= $r['prep_time'] ?> min</td>
                    <td><?= $r['avg_rating'] ?? '—' ?></td>
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
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
