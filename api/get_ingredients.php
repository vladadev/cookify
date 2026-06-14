<?php
require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

$pdo  = get_db();
$rows = $pdo->query('SELECT id, name, unit FROM ingredients ORDER BY name')->fetchAll();

echo json_encode($rows);
