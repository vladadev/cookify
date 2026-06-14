<?php
$page_title = 'All Recipes';
require_once dirname(__DIR__) . '/includes/header.php';

$pdo  = get_db();
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
?>

<div class="page-header">
    <h1>Recipes</h1>
</div>

<div class="filters">
    <select id="filter-category">
        <option value="">All Categories</option>
        <?php foreach ($cats as $cat): ?>
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

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
