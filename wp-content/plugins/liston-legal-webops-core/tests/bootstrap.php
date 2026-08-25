<?php

declare(strict_types=1);

$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/law/';

define('WP_USE_THEMES', false);
$jp_test_root = getenv('JP_TEST_ROOT') ?: dirname(__DIR__, 4);
require_once rtrim($jp_test_root, '/') . '/wp-load.php';
