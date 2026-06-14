<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';

log_access();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookify<?= isset($page_title) ? ' — ' . htmlspecialchars($page_title) : '' ?></title>
    <meta name="base-url" content="<?= BASE_URL ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <a href="<?= BASE_URL ?>/pages/index.php" class="logo">
            🍳 Cookify
        </a>
        <nav class="main-nav">
            <a href="<?= BASE_URL ?>/pages/index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Recipes</a>

            <?php if (is_logged_in()): ?>
                <a href="<?= BASE_URL ?>/pages/add_recipe.php" class="<?= $current_page === 'add_recipe.php' ? 'active' : '' ?>">Add Recipe</a>
                <a href="<?= BASE_URL ?>/pages/my_recipes.php" class="<?= $current_page === 'my_recipes.php' ? 'active' : '' ?>">My Recipes</a>
                <?php if (is_admin()): ?>
                    <a href="<?= BASE_URL ?>/admin/index.php" class="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : '' ?>">Admin</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/pages/logout.php">Logout (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/pages/login.php" class="<?= $current_page === 'login.php' ? 'active' : '' ?>">Login</a>
                <a href="<?= BASE_URL ?>/pages/register.php" class="<?= $current_page === 'register.php' ? 'active' : '' ?>">Register</a>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>/pages/about.php" class="<?= $current_page === 'about.php' ? 'active' : '' ?>">About</a>
        </nav>
    </div>
</header>

<main class="container">
