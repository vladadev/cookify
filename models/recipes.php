<?php

function get_all_recipes(PDO $pdo, ?int $category_id, string $sort, string $order, int $page, int $per_page): array
{
    $allowed_sort  = ['title', 'avg_rating', 'prep_time', 'created_at'];
    $allowed_order = ['ASC', 'DESC'];
    if (!in_array($sort, $allowed_sort, true))   $sort  = 'created_at';
    if (!in_array($order, $allowed_order, true)) $order = 'DESC';

    $where  = 'WHERE 1=1';
    $params = [];

    if ($category_id !== null) {
        $where   .= ' AND r.category_id = ?';
        $params[] = $category_id;
    }

    $sort_col = $sort === 'avg_rating' ? 'avg_rating' : 'r.' . $sort;
    $offset   = ($page - 1) * $per_page;

    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM recipes r $where");
    $count_stmt->execute($params);
    $total = (int) $count_stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT r.id, r.title, r.description, r.prep_time, r.difficulty,
               r.image_thumb, r.created_at,
               c.name AS category_name,
               u.name AS author_name,
               ROUND(AVG(rt.score), 1) AS avg_rating,
               COUNT(DISTINCT rt.id)   AS rating_count
        FROM recipes r
        JOIN categories c ON c.id = r.category_id
        JOIN users u      ON u.id = r.user_id
        LEFT JOIN ratings rt ON rt.recipe_id = r.id
        $where
        GROUP BY r.id
        ORDER BY $sort_col $order
        LIMIT ? OFFSET ?
    ");
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->execute($params);

    return ['rows' => $stmt->fetchAll(), 'total' => $total];
}

function get_recipe_by_id(PDO $pdo, int $id): array|false
{
    $stmt = $pdo->prepare('
        SELECT r.*, c.name AS category_name, u.name AS author_name,
               ROUND(AVG(rt.score), 1) AS avg_rating,
               COUNT(DISTINCT rt.id)   AS rating_count
        FROM recipes r
        JOIN categories c ON c.id = r.category_id
        JOIN users u      ON u.id = r.user_id
        LEFT JOIN ratings rt ON rt.recipe_id = r.id
        WHERE r.id = ?
        GROUP BY r.id
    ');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_recipe_ingredients(PDO $pdo, int $recipe_id): array
{
    $stmt = $pdo->prepare('
        SELECT i.name, i.unit, ri.quantity
        FROM recipe_ingredients ri
        JOIN ingredients i ON i.id = ri.ingredient_id
        WHERE ri.recipe_id = ?
        ORDER BY i.name
    ');
    $stmt->execute([$recipe_id]);
    return $stmt->fetchAll();
}

function get_user_recipes(PDO $pdo, int $user_id): array
{
    $stmt = $pdo->prepare('
        SELECT r.*, c.name AS category_name,
               ROUND(AVG(rt.score), 1) AS avg_rating
        FROM recipes r
        JOIN categories c ON c.id = r.category_id
        LEFT JOIN ratings rt ON rt.recipe_id = r.id
        WHERE r.user_id = ?
        GROUP BY r.id
        ORDER BY r.created_at DESC
    ');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function insert_recipe(PDO $pdo, int $user_id, int $category_id, string $title, string $description, int $prep_time, string $difficulty, ?string $image_original, ?string $image_thumb): int
{
    $pdo->prepare('
        INSERT INTO recipes (user_id, category_id, title, description, prep_time, difficulty, image_original, image_thumb)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ')->execute([$user_id, $category_id, $title, $description, $prep_time, $difficulty, $image_original, $image_thumb]);
    return (int) $pdo->lastInsertId();
}

function update_recipe(PDO $pdo, int $id, int $category_id, string $title, string $description, int $prep_time, string $difficulty, ?string $image_original, ?string $image_thumb): void
{
    $pdo->prepare('
        UPDATE recipes
        SET category_id = ?, title = ?, description = ?, prep_time = ?,
            difficulty = ?, image_original = ?, image_thumb = ?
        WHERE id = ?
    ')->execute([$category_id, $title, $description, $prep_time, $difficulty, $image_original, $image_thumb, $id]);
}

function delete_recipe(PDO $pdo, int $id): void
{
    $pdo->prepare('DELETE FROM recipes WHERE id = ?')->execute([$id]);
}

function save_recipe_ingredients(PDO $pdo, int $recipe_id, array $ing_ids, array $ing_qtys): void
{
    $pdo->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = ?')->execute([$recipe_id]);
    $stmt = $pdo->prepare('INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity) VALUES (?, ?, ?)');
    foreach ($ing_ids as $k => $ing_id) {
        $ing_id = (int) $ing_id;
        $qty    = (float) ($ing_qtys[$k] ?? 0);
        if ($ing_id > 0 && $qty > 0) {
            $stmt->execute([$recipe_id, $ing_id, $qty]);
        }
    }
}

function get_all_recipes_admin(PDO $pdo): array
{
    return $pdo->query('
        SELECT r.id, r.title, r.difficulty, r.prep_time, r.created_at,
               c.name AS category_name, u.name AS author_name
        FROM recipes r
        JOIN categories c ON c.id = r.category_id
        JOIN users u      ON u.id = r.user_id
        ORDER BY r.created_at DESC
    ')->fetchAll();
}
