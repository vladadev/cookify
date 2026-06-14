<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/models/recipes.php';
require_once ROOT . '/models/comments.php';
require_once ROOT . '/models/ratings.php';

if (session_status() === PHP_SESSION_NONE) session_start();
log_access();

$id  = (int) ($_GET['id'] ?? 0);
$pdo = get_db();

$recipe = get_recipe_by_id($pdo, $id);
if (!$recipe) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$ingredients = get_recipe_ingredients($pdo, $id);
$comments    = get_comments_for_recipe($pdo, $id);
$user_rating = is_logged_in() ? get_user_rating($pdo, $id, current_user_id()) : null;

$comment_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    require_login();
    $body = trim($_POST['comment']);
    if (strlen($body) < 2) {
        $comment_error = 'Comment is too short.';
    } else {
        insert_comment($pdo, $id, current_user_id(), $body);
        header('Location: ' . BASE_URL . '/views/recipe.php?id=' . $id);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    require_login();
    $score = (int) $_POST['rating'];
    if ($score >= 1 && $score <= 5) {
        upsert_rating($pdo, $id, current_user_id(), $score);
        header('Location: ' . BASE_URL . '/views/recipe.php?id=' . $id);
        exit;
    }
}

$page_title = htmlspecialchars($recipe['title']);
require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
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
                <?= (int) $recipe['prep_time'] ?> min prep &bull;
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
            <p><a href="<?= BASE_URL ?>/views/login.php">Log in</a> to rate this recipe.</p>
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
            <p><a href="<?= BASE_URL ?>/views/login.php">Log in</a> to leave a comment.</p>
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

<?php require_once VIEWS . '/fixed/footer.php'; ?>
