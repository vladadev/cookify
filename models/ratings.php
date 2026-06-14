<?php

function get_user_rating(PDO $pdo, int $recipe_id, int $user_id): ?int
{
    $stmt = $pdo->prepare('SELECT score FROM ratings WHERE recipe_id = ? AND user_id = ?');
    $stmt->execute([$recipe_id, $user_id]);
    $row = $stmt->fetch();
    return $row ? (int) $row['score'] : null;
}

function upsert_rating(PDO $pdo, int $recipe_id, int $user_id, int $score): void
{
    $pdo->prepare('
        INSERT INTO ratings (recipe_id, user_id, score) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE score = VALUES(score)
    ')->execute([$recipe_id, $user_id, $score]);
}
