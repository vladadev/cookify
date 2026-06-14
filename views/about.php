<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/logger.php';

if (session_status() === PHP_SESSION_NONE) session_start();
log_access();

$page_title = 'About the Author';
require_once VIEWS . '/fixed/head.php';
require_once VIEWS . '/fixed/top-nav.php';
?>

<div class="about-page">
    <h1>About the Author</h1>
    <div class="about-card">
        <div class="about-avatar">👨‍💻</div>
        <div class="about-content">
            <h2>Your Name Here</h2>
            <p class="about-subtitle">Computer Science Student</p>
            <p>
                <strong>Cookify</strong> was developed as a final assignment for the Web Programming course.
                It is a full-stack PHP web application that allows users to share, discover, and rate recipes.
            </p>
            <h3>Technical Stack</h3>
            <ul>
                <li><strong>Backend:</strong> PHP 8+ with PDO and prepared statements</li>
                <li><strong>Database:</strong> MySQL 8 (XAMPP / phpMyAdmin)</li>
                <li><strong>Frontend:</strong> HTML5, CSS3, Vanilla JavaScript (Fetch API)</li>
                <li><strong>Image Processing:</strong> PHP GD Library</li>
                <li><strong>Email:</strong> PHPMailer with Gmail SMTP</li>
            </ul>
            <h3>Key Features</h3>
            <ul>
                <li>User registration with email activation</li>
                <li>Account locking after 3 failed login attempts</li>
                <li>Recipe management with image upload (thumbnail + original)</li>
                <li>AJAX-powered filtering, sorting and pagination</li>
                <li>Commenting and 1–5 star rating system</li>
                <li>Admin panel with access statistics and user management</li>
            </ul>
            <div class="about-links">
                <a href="<?= BASE_URL ?>/documentation.pdf" class="btn btn-primary" target="_blank">📄 Documentation</a>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS . '/fixed/footer.php'; ?>
