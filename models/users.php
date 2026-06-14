<?php

function get_user_by_email(PDO $pdo, string $email): array|false
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function get_user_by_token(PDO $pdo, string $token): array|false
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE activation_token = ? AND is_active = 0');
    $stmt->execute([$token]);
    return $stmt->fetch();
}

function get_all_users(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
}

function insert_user(PDO $pdo, string $name, string $email, string $hash, string $token): void
{
    $pdo->prepare('INSERT INTO users (name, email, password_hash, activation_token) VALUES (?, ?, ?, ?)')
        ->execute([$name, $email, $hash, $token]);
}

function activate_user(PDO $pdo, int $id): void
{
    $pdo->prepare('UPDATE users SET is_active = 1, activation_token = NULL WHERE id = ?')->execute([$id]);
}

function record_failed_login(PDO $pdo, int $id, int $new_attempts): void
{
    $pdo->prepare('UPDATE users SET failed_attempts = ?, last_failed_at = NOW() WHERE id = ?')
        ->execute([$new_attempts, $id]);
}

function lock_user(PDO $pdo, int $id): void
{
    $pdo->prepare('UPDATE users SET is_locked = 1 WHERE id = ?')->execute([$id]);
}

function reset_failed_attempts(PDO $pdo, int $id): void
{
    $pdo->prepare('UPDATE users SET failed_attempts = 0, last_failed_at = NULL WHERE id = ?')->execute([$id]);
}

function record_login(PDO $pdo, int $id): void
{
    $pdo->prepare('UPDATE users SET failed_attempts = 0, last_failed_at = NULL, last_login_at = NOW() WHERE id = ?')
        ->execute([$id]);
}

function set_user_role(PDO $pdo, int $id, string $role): void
{
    $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
}

function unlock_user(PDO $pdo, int $id): void
{
    $pdo->prepare('UPDATE users SET is_locked = 0, failed_attempts = 0 WHERE id = ?')->execute([$id]);
}

function delete_user(PDO $pdo, int $id): void
{
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
}

function change_user_password(PDO $pdo, int $id, string $new_password): void
{
    $hash = password_hash($new_password, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
}

function count_logins_today(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(last_login_at) = CURDATE()")->fetchColumn();
}
