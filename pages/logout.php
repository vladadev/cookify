<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/logger.php';

if (session_status() === PHP_SESSION_NONE) session_start();
log_access();

session_destroy();
header('Location: ' . BASE_URL . '/pages/login.php');
exit;
