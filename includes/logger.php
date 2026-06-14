<?php

function log_access(): void {
    $timestamp = date('Y-m-d H:i:s');
    $uri       = $_SERVER['REQUEST_URI'] ?? '/';
    $ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id   = $_SESSION['user_id'] ?? 'guest';

    $line = "[{$timestamp}] | {$uri} | IP: {$ip} | User: {$user_id}" . PHP_EOL;

    file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}
