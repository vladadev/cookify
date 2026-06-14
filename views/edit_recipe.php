<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/includes/upload.php';
require_once ROOT . '/models/recipes.php';
require_once ROOT . '/models/categories.php';
require_once ROOT . '/models/ingredients.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_login();
log_access();

$id  = (int) ($_GET['id'] ?? 0);
$pdo = get_db();

$recipe = get_recipe_by_id($pdo, $id);
if (!$recipe || ($recipe['user_id'] !== current_user_id() && !is_admin())) {
    header('Location: ' . BASE_URL . '/views/my_recipes.php');
    exit;
}

$categories         = get_all_categories($pdo);
$recipe_ingredients = get_recipe_ingredient_map($pdo, $id);
$errors             = [];

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
        update_recipe($pdo, $id, $category_id, $title, $description, $prep_time, $difficulty, $image_original, $image_thumb);
        save_recipe_ingredients($pdo, $id, $_POST['ingredient_id'] ?? [], $_POST['ingredient_qty'] ?? []);
        header('Location: ' . BASE_URL . '/views/recipe.php?id=' . $id);
        exit;
    }

    $recipe['title']       = $title;
    $recipe['description'] = $description;
    $recipe['category_id'] = $category_id;
    $recipe['prep_time']   = $prep_time;
    $recipe['difficulty']  = $difficulty;
}

$page_title = 'Edit Recipe';
require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
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
                    <div class="ingredient-row" data-ing-id="<?= (int) $ing_id ?>" data-ing-qty="<?= htmlspecialchars((string) $qty) ?>"></div>
                <?php endforeach; ?>
                <?php if (empty($recipe_ingredients)): ?>
                    <div class="ingredient-row"></div>
                <?php endif; ?>
            </div>
            <button type="button" id="add-ingredient" class="btn btn-secondary btn-small">+ Add Ingredient</button>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= BASE_URL ?>/views/recipe.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>const BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/recipe-form.js"></script>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
