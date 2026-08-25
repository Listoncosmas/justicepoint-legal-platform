<?php

declare(strict_types=1);

$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/law/';

define('WP_USE_THEMES', false);

$jp_mamp_socket = '/Applications/MAMP/tmp/mysql/mysql.sock';
if (PHP_OS_FAMILY === 'Darwin' && is_readable($jp_mamp_socket) && ! is_readable((string) ini_get('mysqli.default_socket'))) {
    ini_set('mysqli.default_socket', $jp_mamp_socket);
    ini_set('pdo_mysql.default_socket', $jp_mamp_socket);
}

$jp_test_root = getenv('JP_TEST_ROOT') ?: dirname(__DIR__, 4);
require_once rtrim($jp_test_root, '/') . '/wp-load.php';
