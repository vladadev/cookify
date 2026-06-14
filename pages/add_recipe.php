<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once dirname(__DIR__) . '/includes/upload.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_login();
log_access();

$pdo    = get_db();
$errors = [];

$categories  = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$ingredients = $pdo->query('SELECT id, name, unit FROM ingredients ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $prep_time   = (int) ($_POST['prep_time']   ?? 0);
    $difficulty  = $_POST['difficulty'] ?? '';

    $ing_ids  = $_POST['ingredient_id']  ?? [];
    $ing_qtys = $_POST['ingredient_qty'] ?? [];

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
            $errors[] = 'Image upload failed. Allowed types: jpg, jpeg, png, gif, webp. Max size: 5MB.';
        } else {
            $image_original = $paths['original'];
            $image_thumb    = $paths['thumb'];
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            INSERT INTO recipes (user_id, category_id, title, description, prep_time, difficulty, image_original, image_thumb)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([current_user_id(), $category_id, $title, $description, $prep_time, $difficulty, $image_original, $image_thumb]);
        $recipe_id = (int) $pdo->lastInsertId();

        $ins_stmt = $pdo->prepare('INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity) VALUES (?, ?, ?)');
        foreach ($ing_ids as $k => $ing_id) {
            $ing_id = (int) $ing_id;
            $qty    = (float) ($ing_qtys[$k] ?? 0);
            if ($ing_id > 0 && $qty > 0) {
                $ins_stmt->execute([$recipe_id, $ing_id, $qty]);
            }
        }

        header('Location: ' . BASE_URL . '/pages/recipe.php?id=' . $recipe_id);
        exit;
    }
}

$page_title = 'Add Recipe';
require_once dirname(__DIR__) . '/includes/header.php';
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
            <input type="file" id="image" name="image" accept="image/*">
            <small>Max 5MB. Supported: jpg, jpeg, png, gif, webp</small>
        </div>

        <div class="form-group">
            <label>Ingredients</label>
            <div id="ingredients-list">
                <div class="ingredient-row">
                    <select name="ingredient_id[]" required>
                        <option value="">Select ingredient…</option>
                        <?php foreach ($ingredients as $ing): ?>
                            <option value="<?= $ing['id'] ?>"><?= htmlspecialchars($ing['name']) ?> (<?= htmlspecialchars($ing['unit']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="ingredient_qty[]" placeholder="Quantity" step="0.01" min="0.01" required>
                    <button type="button" class="btn btn-small btn-danger remove-ingredient">✕</button>
                </div>
            </div>
            <button type="button" id="add-ingredient" class="btn btn-secondary btn-small">+ Add Ingredient</button>
        </div>

        <button type="submit" class="btn btn-primary">Publish Recipe</button>
    </form>
</div>

<script>
const ingredientTemplate = `<?php
$opts = '';
foreach ($ingredients as $ing) {
    $opts .= '<option value="' . $ing['id'] . '">' . htmlspecialchars($ing['name'], ENT_QUOTES) . ' (' . htmlspecialchars($ing['unit'], ENT_QUOTES) . ')</option>';
}
echo addslashes('<div class="ingredient-row"><select name="ingredient_id[]" required><option value="">Select ingredient…</option>' . $opts . '</select><input type="number" name="ingredient_qty[]" placeholder="Quantity" step="0.01" min="0.01" required><button type="button" class="btn btn-small btn-danger remove-ingredient">✕</button></div>');
?>`;

document.getElementById('add-ingredient').addEventListener('click', () => {
    document.getElementById('ingredients-list').insertAdjacentHTML('beforeend', ingredientTemplate);
});

document.getElementById('ingredients-list').addEventListener('click', e => {
    if (e.target.classList.contains('remove-ingredient')) {
        const rows = document.querySelectorAll('.ingredient-row');
        if (rows.length > 1) e.target.closest('.ingredient-row').remove();
    }
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
