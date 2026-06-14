<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<header class="site-header">
    <div class="container">
        <a href="<?= BASE_URL ?>/" class="logo">🍳 Cookify</a>
        <nav class="main-nav">
            <a href="<?= BASE_URL ?>/"
               class="<?= $current_page === 'index.php' && strpos($_SERVER['PHP_SELF'], '/admin/') === false ? 'active' : '' ?>">Recipes</a>

            <?php if (is_logged_in()): ?>
                <a href="<?= BASE_URL ?>/views/add_recipe.php"
                   class="<?= $current_page === 'add_recipe.php' ? 'active' : '' ?>">Add Recipe</a>
                <a href="<?= BASE_URL ?>/views/my_recipes.php"
                   class="<?= $current_page === 'my_recipes.php' ? 'active' : '' ?>">My Recipes</a>
                <?php if (is_admin()): ?>
                    <a href="<?= BASE_URL ?>/views/admin/index.php"
                       class="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : '' ?>">Admin</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/views/logout.php">Logout (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/views/login.php"
                   class="<?= $current_page === 'login.php' ? 'active' : '' ?>">Login</a>
                <a href="<?= BASE_URL ?>/views/register.php"
                   class="<?= $current_page === 'register.php' ? 'active' : '' ?>">Register</a>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>/views/about.php"
               class="<?= $current_page === 'about.php' ? 'active' : '' ?>">About</a>
        </nav>
    </div>
</header>
<main class="container">
