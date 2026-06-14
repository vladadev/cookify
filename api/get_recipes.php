<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/models/recipes.php';

header('Content-Type: application/json; charset=utf-8');

$category_id = isset($_GET['category_id']) && (int) $_GET['category_id'] > 0
    ? (int) $_GET['category_id'] : null;

$sort     = $_GET['sort']  ?? 'created_at';
$order    = strtoupper($_GET['order'] ?? 'DESC');
$page     = max(1, (int) ($_GET['page']     ?? 1));
$per_page = min(24, max(1, (int) ($_GET['per_page'] ?? 6)));

$pdo    = get_db();
$result = get_all_recipes($pdo, $category_id, $sort, $order, $page, $per_page);

foreach ($result['rows'] as &$r) {
    $r['image_thumb'] = $r['image_thumb'] ? BASE_URL . '/' . $r['image_thumb'] : null;
    $r['url']         = BASE_URL . '/views/recipe.php?id=' . $r['id'];
}

echo json_encode([
    'recipes'     => $result['rows'],
    'total'       => $result['total'],
    'page'        => $page,
    'per_page'    => $per_page,
    'total_pages' => (int) ceil($result['total'] / $per_page),
]);
