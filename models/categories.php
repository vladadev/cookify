<?php

function get_all_categories(PDO $pdo): array
{
    return $pdo->query('SELECT id, name, description FROM categories ORDER BY name')->fetchAll();
}

function get_categories_with_count(PDO $pdo): array
{
    return $pdo->query('
        SELECT c.*, COUNT(r.id) AS recipe_count
        FROM categories c
        LEFT JOIN recipes r ON r.category_id = c.id
        GROUP BY c.id
        ORDER BY c.name
    ')->fetchAll();
}

function get_category_by_id(PDO $pdo, int $id): array|false
{
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function insert_category(PDO $pdo, string $name, string $description): void
{
    $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)')->execute([$name, $description]);
}

function update_category(PDO $pdo, int $id, string $name, string $description): void
{
    $pdo->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?')->execute([$name, $description, $id]);
}

function delete_category(PDO $pdo, int $id): void
{
    $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
}
