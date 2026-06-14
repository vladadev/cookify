<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT . '/config/connection.php';
require_once ROOT . '/models/ingredients.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode(get_all_ingredients(get_db()));
