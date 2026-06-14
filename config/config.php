<?php

$env = parse_ini_file(__DIR__ . '/../.env');

define('ROOT',   dirname(__DIR__));
define('VIEWS',  ROOT . '/views');
define('MODELS', ROOT . '/models');

define('BASE_URL', $env['BASE_URL']);

define('DB_HOST',    $env['DB_HOST']);
define('DB_NAME',    $env['DB_NAME']);
define('DB_USER',    $env['DB_USER']);
define('DB_PASS',    $env['DB_PASS']);
define('DB_CHARSET', 'utf8mb4');

define('UPLOAD_DIR',      ROOT . '/uploads/');
define('UPLOAD_ORIGINAL', ROOT . '/uploads/original/');
define('UPLOAD_THUMBS',   ROOT . '/uploads/thumbs/');
define('LOG_FILE',        ROOT . '/logs/access.log');

define('MAX_FILE_SIZE',  5 * 1024 * 1024);
define('THUMB_WIDTH',    400);
define('THUMB_HEIGHT',   300);

define('MAIL_HOST',      $env['MAIL_HOST']);
define('MAIL_PORT',      (int) $env['MAIL_PORT']);
define('MAIL_USERNAME',  $env['MAIL_USERNAME']);
define('MAIL_PASSWORD',  $env['MAIL_PASSWORD']);
define('MAIL_FROM',      $env['MAIL_FROM']);
define('MAIL_FROM_NAME', $env['MAIL_FROM_NAME']);

define('LOGIN_MAX_ATTEMPTS',   3);
define('LOGIN_LOCKOUT_MINUTES', 5);
