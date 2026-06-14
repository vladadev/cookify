<?php

function get_comments_for_recipe(PDO $pdo, int $recipe_id): array
{
    $stmt = $pdo->prepare('
        SELECT cm.*, u.name AS user_name
        FROM comments cm
        JOIN users u ON u.id = cm.user_id
        WHERE cm.recipe_id = ?
        ORDER BY cm.created_at DESC
    ');
    $stmt->execute([$recipe_id]);
    return $stmt->fetchAll();
}

function insert_comment(PDO $pdo, int $recipe_id, int $user_id, string $body): void
{
    $pdo->prepare('INSERT INTO comments (recipe_id, user_id, body) VALUES (?, ?, ?)')
        ->execute([$recipe_id, $user_id, $body]);
}

function delete_comment(PDO $pdo, int $id): void
{
    $pdo->prepare('DELETE FROM comments WHERE id = ?')->execute([$id]);
}

function get_all_comments_admin(PDO $pdo): array
{
    return $pdo->query('
        SELECT cm.*, u.name AS user_name, r.title AS recipe_title, r.id AS recipe_id
        FROM comments cm
        JOIN users u   ON u.id  = cm.user_id
        JOIN recipes r ON r.id  = cm.recipe_id
        ORDER BY cm.created_at DESC
    ')->fetchAll();
}
