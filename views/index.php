<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';
require_once ROOT . '/models/categories.php';

if (session_status() === PHP_SESSION_NONE) session_start();
log_access();

$pdo        = get_db();
$categories = get_all_categories($pdo);
$page_title = 'All Recipes';

require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="page-header">
    <h1>Recipes</h1>
</div>

<div class="filters">
    <select id="filter-category">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select id="filter-sort">
        <option value="created_at">Newest First</option>
        <option value="title">Title A–Z</option>
        <option value="avg_rating">Top Rated</option>
        <option value="prep_time">Prep Time</option>
    </select>
    <select id="filter-order">
        <option value="DESC">Descending</option>
        <option value="ASC">Ascending</option>
    </select>
</div>

<div id="recipes-grid" class="recipes-grid">
    <p class="loading">Loading recipes…</p>
</div>
<div id="pagination" class="pagination"></div>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
