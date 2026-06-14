<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once dirname(__DIR__) . '/includes/upload.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_login();
log_access();

$id  = (int) ($_GET['id'] ?? 0);
$pdo = get_db();

$stmt = $pdo->prepare('SELECT * FROM recipes WHERE id = ?');
$stmt->execute([$id]);
$recipe = $stmt->fetch();

if (!$recipe || ($recipe['user_id'] !== current_user_id() && !is_admin())) {
    header('Location: ' . BASE_URL . '/pages/my_recipes.php');
    exit;
}

$categories  = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$all_ingredients = $pdo->query('SELECT id, name, unit FROM ingredients ORDER BY name')->fetchAll();

$ri_stmt = $pdo->prepare('SELECT ingredient_id, quantity FROM recipe_ingredients WHERE recipe_id = ?');
$ri_stmt->execute([$id]);
$recipe_ingredients = $ri_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $prep_time   = (int) ($_POST['prep_time']   ?? 0);
    $difficulty  = $_POST['difficulty'] ?? '';

    if (empty($title))       $errors[] = 'Title is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    if ($category_id === 0)  $errors[] = 'Please select a category.';
    if ($prep_time <= 0)     $errors[] = 'Prep time must be greater than 0.';
    if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) $errors[] = 'Invalid difficulty.';

    $image_original = $recipe['image_original'];
    $image_thumb    = $recipe['image_thumb'];

    if (!empty($_FILES['image']['name'])) {
        $paths = upload_image($_FILES['image']);
        if ($paths === false) {
            $errors[] = 'Image upload failed.';
        } else {
            $image_original = $paths['original'];
            $image_thumb    = $paths['thumb'];
        }
    }

    if (empty($errors)) {
        $pdo->prepare('
            UPDATE recipes
            SET category_id = ?, title = ?, description = ?, prep_time = ?,
                difficulty = ?, image_original = ?, image_thumb = ?
            WHERE id = ?
        ')->execute([$category_id, $title, $description, $prep_time, $difficulty, $image_original, $image_thumb, $id]);

        $pdo->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = ?')->execute([$id]);

        $ing_ids  = $_POST['ingredient_id']  ?? [];
        $ing_qtys = $_POST['ingredient_qty'] ?? [];
        $ins = $pdo->prepare('INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity) VALUES (?, ?, ?)');
        foreach ($ing_ids as $k => $ing_id) {
            $ing_id = (int) $ing_id;
            $qty    = (float) ($ing_qtys[$k] ?? 0);
            if ($ing_id > 0 && $qty > 0) $ins->execute([$id, $ing_id, $qty]);
        }

        header('Location: ' . BASE_URL . '/pages/recipe.php?id=' . $id);
        exit;
    }

    $recipe['title']       = $_POST['title']       ?? $recipe['title'];
    $recipe['description'] = $_POST['description'] ?? $recipe['description'];
    $recipe['category_id'] = $category_id;
    $recipe['prep_time']   = $prep_time;
    $recipe['difficulty']  = $difficulty;
}

$page_title = 'Edit Recipe';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="form-page">
    <h1>Edit Recipe</h1>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" class="recipe-form">
        <div class="form-group">
            <label for="title">Recipe Title</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($recipe['title']) ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $recipe['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="difficulty">Difficulty</label>
                <select id="difficulty" name="difficulty" required>
                    <?php foreach (['easy', 'medium', 'hard'] as $d): ?>
                        <option value="<?= $d ?>" <?= $recipe['difficulty'] === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="prep_time">Prep Time (min)</label>
                <input type="number" id="prep_time" name="prep_time" min="1" value="<?= $recipe['prep_time'] ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($recipe['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="image">Replace Image (optional)</label>
            <?php if ($recipe['image_thumb']): ?>
                <img src="<?= BASE_URL . '/' . htmlspecialchars($recipe['image_thumb']) ?>" alt="Current" class="current-thumb">
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif">
        </div>

        <div class="form-group">
            <label>Ingredients</label>
            <div id="ingredients-list">
                <?php foreach ($recipe_ingredients as $ing_id => $qty): ?>
                    <div class="ingredient-row" data-ing-id="<?= (int)$ing_id ?>" data-ing-qty="<?= htmlspecialchars((string)$qty) ?>"></div>
                <?php endforeach; ?>
                <?php if (empty($recipe_ingredients)): ?>
                    <div class="ingredient-row"></div>
                <?php endif; ?>
            </div>
            <button type="button" id="add-ingredient" class="btn btn-secondary btn-small">+ Add Ingredient</button>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= BASE_URL ?>/pages/recipe.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>const BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/recipe-form.js"></script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
