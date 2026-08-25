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
$jp_wp_load = rtrim($jp_test_root, '/') . '/wp-load.php';
if (! is_readable($jp_wp_load)) {
    fwrite(STDERR, "JusticePoint test bootstrap failed: WordPress wp-load.php is not readable at {$jp_wp_load}.\n");
    exit(1);
}

$jp_wordpress_loaded = false;
register_shutdown_function(
    static function () use (&$jp_wordpress_loaded): void {
        if (! $jp_wordpress_loaded) {
            fwrite(STDERR, "\nJusticePoint test bootstrap failed: WordPress did not load. Check database credentials, DB_HOST, and the MySQL socket.\n");
            exit(1);
        }
    }
);

require_once $jp_wp_load;
if (! defined('ABSPATH') || ! function_exists('add_action')) {
    throw new RuntimeException('JusticePoint test bootstrap failed: WordPress loaded incompletely.');
}
$jp_wordpress_loaded = true;
