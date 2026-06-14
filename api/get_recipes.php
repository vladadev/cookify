<?php
require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

$allowed_sort = ['title', 'avg_rating', 'prep_time', 'created_at'];
$allowed_order = ['ASC', 'DESC'];

$category_id = isset($_GET['category_id']) && (int)$_GET['category_id'] > 0
    ? (int)$_GET['category_id'] : null;

$sort     = in_array($_GET['sort'] ?? '', $allowed_sort, true) ? $_GET['sort'] : 'created_at';
$order    = in_array(strtoupper($_GET['order'] ?? ''), $allowed_order, true)
    ? strtoupper($_GET['order']) : 'DESC';

$page     = max(1, (int)($_GET['page']     ?? 1));
$per_page = min(24, max(1, (int)($_GET['per_page'] ?? 6)));
$offset   = ($page - 1) * $per_page;

$pdo    = get_db();
$params = [];
$where  = 'WHERE 1=1';

if ($category_id !== null) {
    $where    .= ' AND r.category_id = ?';
    $params[]  = $category_id;
}

$sort_col = $sort === 'avg_rating' ? 'avg_rating' : 'r.' . $sort;

$count_sql = "SELECT COUNT(*) FROM recipes r $where";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = (int) $count_stmt->fetchColumn();

$sql = "
    SELECT r.id, r.title, r.description, r.prep_time, r.difficulty,
           r.image_thumb, r.created_at,
           c.name AS category_name,
           u.name AS author_name,
           ROUND(AVG(rt.score), 1) AS avg_rating,
           COUNT(DISTINCT rt.id) AS rating_count
    FROM recipes r
    JOIN categories c ON c.id = r.category_id
    JOIN users u      ON u.id = r.user_id
    LEFT JOIN ratings rt ON rt.recipe_id = r.id
    $where
    GROUP BY r.id
    ORDER BY $sort_col $order
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll();

foreach ($recipes as &$r) {
    $r['image_thumb'] = $r['image_thumb']
        ? BASE_URL . '/' . $r['image_thumb']
        : null;
    $r['url'] = BASE_URL . '/pages/recipe.php?id=' . $r['id'];
}

echo json_encode([
    'recipes'    => $recipes,
    'total'      => $total,
    'page'       => $page,
    'per_page'   => $per_page,
    'total_pages'=> (int) ceil($total / $per_page),
]);
