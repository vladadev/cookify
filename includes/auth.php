<?php

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/views/login.php');
        exit;
    }
}

function require_admin(): void {
    if (!is_logged_in() || $_SESSION['user_role'] !== 'admin') {
        header('Location: ' . BASE_URL . '/views/login.php');
        exit;
    }
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function current_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

function is_admin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
