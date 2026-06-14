<?php

function get_all_ingredients(PDO $pdo): array
{
    return $pdo->query('SELECT id, name, unit FROM ingredients ORDER BY name')->fetchAll();
}

function get_ingredients_with_count(PDO $pdo): array
{
    return $pdo->query('
        SELECT i.*, COUNT(ri.recipe_id) AS recipe_count
        FROM ingredients i
        LEFT JOIN recipe_ingredients ri ON ri.ingredient_id = i.id
        GROUP BY i.id
        ORDER BY i.name
    ')->fetchAll();
}

function get_ingredient_by_id(PDO $pdo, int $id): array|false
{
    $stmt = $pdo->prepare('SELECT * FROM ingredients WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function insert_ingredient(PDO $pdo, string $name, string $unit): void
{
    $pdo->prepare('INSERT INTO ingredients (name, unit) VALUES (?, ?)')->execute([$name, $unit]);
}

function update_ingredient(PDO $pdo, int $id, string $name, string $unit): void
{
    $pdo->prepare('UPDATE ingredients SET name = ?, unit = ? WHERE id = ?')->execute([$name, $unit, $id]);
}

function delete_ingredient(PDO $pdo, int $id): void
{
    $pdo->prepare('DELETE FROM ingredients WHERE id = ?')->execute([$id]);
}

function get_recipe_ingredient_map(PDO $pdo, int $recipe_id): array
{
    $stmt = $pdo->prepare('SELECT ingredient_id, quantity FROM recipe_ingredients WHERE recipe_id = ?');
    $stmt->execute([$recipe_id]);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
