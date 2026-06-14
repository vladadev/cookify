<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';

if (session_status() === PHP_SESSION_NONE) session_start();
log_access();

$id  = (int) ($_GET['id'] ?? 0);
$pdo = get_db();

$stmt = $pdo->prepare('
    SELECT r.*, c.name AS category_name, u.name AS author_name,
           ROUND(AVG(rt.score), 1) AS avg_rating, COUNT(DISTINCT rt.id) AS rating_count
    FROM recipes r
    JOIN categories c ON c.id = r.category_id
    JOIN users u      ON u.id = r.user_id
    LEFT JOIN ratings rt ON rt.recipe_id = r.id
    WHERE r.id = ?
    GROUP BY r.id
');
$stmt->execute([$id]);
$recipe = $stmt->fetch();

if (!$recipe) {
    header('Location: ' . BASE_URL . '/pages/index.php');
    exit;
}

$ingredients = $pdo->prepare('
    SELECT i.name, i.unit, ri.quantity
    FROM recipe_ingredients ri
    JOIN ingredients i ON i.id = ri.ingredient_id
    WHERE ri.recipe_id = ?
    ORDER BY i.name
');
$ingredients->execute([$id]);
$ingredients = $ingredients->fetchAll();

$comments = $pdo->prepare('
    SELECT cm.*, u.name AS user_name
    FROM comments cm
    JOIN users u ON u.id = cm.user_id
    WHERE cm.recipe_id = ?
    ORDER BY cm.created_at DESC
');
$comments->execute([$id]);
$comments = $comments->fetchAll();

$user_rating = null;
if (is_logged_in()) {
    $s = $pdo->prepare('SELECT score FROM ratings WHERE recipe_id = ? AND user_id = ?');
    $s->execute([$id, current_user_id()]);
    $row = $s->fetch();
    $user_rating = $row ? $row['score'] : null;
}

$comment_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    require_login();
    $body = trim($_POST['comment']);
    if (strlen($body) < 2) {
        $comment_error = 'Comment is too short.';
    } else {
        $pdo->prepare('INSERT INTO comments (recipe_id, user_id, body) VALUES (?, ?, ?)')
            ->execute([$id, current_user_id(), $body]);
        header('Location: ' . BASE_URL . '/pages/recipe.php?id=' . $id);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    require_login();
    $score = (int) $_POST['rating'];
    if ($score >= 1 && $score <= 5) {
        $pdo->prepare('
            INSERT INTO ratings (recipe_id, user_id, score) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE score = VALUES(score)
        ')->execute([$id, current_user_id(), $score]);
        header('Location: ' . BASE_URL . '/pages/recipe.php?id=' . $id);
        exit;
    }
}

$page_title = htmlspecialchars($recipe['title']);
require_once dirname(__DIR__) . '/includes/header.php';
?>

<article class="recipe-detail">
    <div class="recipe-detail-header">
        <?php if ($recipe['image_original']): ?>
            <img src="<?= BASE_URL . '/' . htmlspecialchars($recipe['image_original']) ?>"
                 alt="<?= htmlspecialchars($recipe['title']) ?>"
                 class="recipe-full-image">
        <?php endif; ?>

        <div class="recipe-meta-block">
            <span class="tag"><?= htmlspecialchars($recipe['category_name']) ?></span>
            <span class="tag tag-<?= $recipe['difficulty'] ?>"><?= ucfirst($recipe['difficulty']) ?></span>
            <h1><?= htmlspecialchars($recipe['title']) ?></h1>
            <p class="recipe-meta">
                By <strong><?= htmlspecialchars($recipe['author_name']) ?></strong> &bull;
                <?= (int)$recipe['prep_time'] ?> min prep &bull;
                ⭐ <?= $recipe['avg_rating'] ?? 'No ratings' ?>
                <?php if ($recipe['rating_count'] > 0): ?>
                    (<?= $recipe['rating_count'] ?> vote<?= $recipe['rating_count'] > 1 ? 's' : '' ?>)
                <?php endif; ?>
            </p>
            <p><?= nl2br(htmlspecialchars($recipe['description'])) ?></p>
        </div>
    </div>

    <section class="ingredients-section">
        <h2>Ingredients</h2>
        <ul class="ingredient-list">
            <?php foreach ($ingredients as $ing): ?>
                <li><?= htmlspecialchars($ing['quantity'] . ' ' . $ing['unit'] . ' ' . $ing['name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="rating-section">
        <h2>Rate This Recipe</h2>
        <?php if (is_logged_in()): ?>
            <form method="POST" action="" class="rating-form">
                <div class="star-rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>"
                               <?= $user_rating === $i ? 'checked' : '' ?>>
                        <label for="star<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
                <button type="submit" class="btn btn-secondary">
                    <?= $user_rating ? 'Update Rating' : 'Submit Rating' ?>
                </button>
            </form>
        <?php else: ?>
            <p><a href="<?= BASE_URL ?>/pages/login.php">Log in</a> to rate this recipe.</p>
        <?php endif; ?>
    </section>

    <section class="comments-section">
        <h2>Comments (<?= count($comments) ?>)</h2>

        <?php if (is_logged_in()): ?>
            <?php if ($comment_error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($comment_error) ?></div>
            <?php endif; ?>
            <form method="POST" action="" class="comment-form">
                <textarea name="comment" rows="3" placeholder="Share your thoughts…" required></textarea>
                <button type="submit" class="btn btn-primary">Post Comment</button>
            </form>
        <?php else: ?>
            <p><a href="<?= BASE_URL ?>/pages/login.php">Log in</a> to leave a comment.</p>
        <?php endif; ?>

        <div class="comment-list">
            <?php foreach ($comments as $c): ?>
                <div class="comment">
                    <strong><?= htmlspecialchars($c['user_name']) ?></strong>
                    <span class="comment-date"><?= date('M j, Y', strtotime($c['created_at'])) ?></span>
                    <p><?= nl2br(htmlspecialchars($c['body'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</article>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
