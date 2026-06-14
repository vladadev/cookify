<?php
// Copy this file to config.php and fill in your values

define('DB_HOST', 'localhost');
define('DB_NAME', 'cookify');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', 'http://localhost/cookify');
define('BASE_PATH', dirname(__DIR__));

define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('UPLOAD_ORIGINAL', UPLOAD_DIR . 'original/');
define('UPLOAD_THUMBS', UPLOAD_DIR . 'thumbs/');
define('LOG_FILE', BASE_PATH . '/logs/access.log');

define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('THUMB_WIDTH', 400);
define('THUMB_HEIGHT', 300);

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your_email@gmail.com');
define('MAIL_PASSWORD', 'your_app_password');
define('MAIL_FROM', 'your_email@gmail.com');
define('MAIL_FROM_NAME', 'Cookify');

define('LOGIN_MAX_ATTEMPTS', 3);
define('LOGIN_LOCKOUT_MINUTES', 5);

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}
