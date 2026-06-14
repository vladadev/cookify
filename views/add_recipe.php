<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/includes/upload.php';
require_once ROOT . '/models/recipes.php';
require_once ROOT . '/models/categories.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_login();
log_access();

$pdo        = get_db();
$errors     = [];
$categories = get_all_categories($pdo);

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

    $image_original = null;
    $image_thumb    = null;

    if (!empty($_FILES['image']['name'])) {
        $paths = upload_image($_FILES['image']);
        if ($paths === false) {
            $errors[] = 'Image upload failed. Allowed: jpg, jpeg, png, gif. Max 5MB.';
        } else {
            $image_original = $paths['original'];
            $image_thumb    = $paths['thumb'];
        }
    }

    if (empty($errors)) {
        $recipe_id = insert_recipe($pdo, current_user_id(), $category_id, $title, $description, $prep_time, $difficulty, $image_original, $image_thumb);
        save_recipe_ingredients($pdo, $recipe_id, $_POST['ingredient_id'] ?? [], $_POST['ingredient_qty'] ?? []);
        header('Location: ' . BASE_URL . '/views/recipe.php?id=' . $recipe_id);
        exit;
    }
}

$page_title = 'Add Recipe';
require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="form-page">
    <h1>Add a New Recipe</h1>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" class="recipe-form">
        <div class="form-group">
            <label for="title">Recipe Title</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select category…</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="difficulty">Difficulty</label>
                <select id="difficulty" name="difficulty" required>
                    <option value="easy"   <?= ($_POST['difficulty'] ?? '') === 'easy'   ? 'selected' : '' ?>>Easy</option>
                    <option value="medium" <?= ($_POST['difficulty'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="hard"   <?= ($_POST['difficulty'] ?? '') === 'hard'   ? 'selected' : '' ?>>Hard</option>
                </select>
            </div>
            <div class="form-group">
                <label for="prep_time">Prep Time (minutes)</label>
                <input type="number" id="prep_time" name="prep_time" min="1" value="<?= htmlspecialchars($_POST['prep_time'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label for="image">Recipe Image</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif">
            <small>Max 5MB. Supported: jpg, jpeg, png, gif</small>
        </div>
        <div class="form-group">
            <label>Ingredients</label>
            <div id="ingredients-list"></div>
            <button type="button" id="add-ingredient" class="btn btn-secondary btn-small">+ Add Ingredient</button>
        </div>
        <button type="submit" class="btn btn-primary">Publish Recipe</button>
    </form>
</div>

<script>const BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/recipe-form.js"></script>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
