<?php
/**
 * Plugin Name: JusticePoint Legal WebOps Core
 * Plugin URI:  https://github.com/listoncosmas/justicepoint-legal-platform
 * Description: Portable content, SEO, directory, intake, migration, REST, CLI, and Elementor services for the JusticePoint demonstration platform.
 * Version:     1.0.1
 * Author:      Liston Cosmas
 * License:     GPL-2.0-or-later
 * Text Domain: liston-legal-webops
 * Requires at least: 6.6
 * Requires PHP: 8.2
 */

declare(strict_types=1);

namespace Liston\LegalWebOps;

if (! defined('ABSPATH')) {
    exit;
}

define('JP_WEBOPS_VERSION', '1.0.1');
define('JP_WEBOPS_FILE', __FILE__);
define('JP_WEBOPS_PATH', plugin_dir_path(__FILE__));
define('JP_WEBOPS_URL', plugin_dir_url(__FILE__));

$autoload = JP_WEBOPS_PATH . 'vendor/autoload.php';
if (is_readable($autoload)) {
    require_once $autoload;
} else {
    spl_autoload_register(
        static function (string $class): void {
            $prefix = __NAMESPACE__ . '\\';
            if (! str_starts_with($class, $prefix)) {
                return;
            }

            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file     = JP_WEBOPS_PATH . 'src/' . $relative . '.php';
            if (is_readable($file)) {
                require_once $file;
            }
        }
    );
}

/**
 * Read an ACF-backed value with a native post-meta fallback.
 *
 * @return mixed
 */
function field(string $name, int|false $post_id = false, mixed $default = ''): mixed
{
    $post_id = $post_id ?: (int) get_the_ID();
    $value   = function_exists('get_field') ? get_field($name, $post_id) : get_post_meta($post_id, $name, true);

    return ($value === '' || $value === null || $value === false) ? $default : $value;
}

register_activation_hook(__FILE__, [Bootstrap::class, 'activate']);
register_deactivation_hook(__FILE__, [Bootstrap::class, 'deactivate']);

add_action(
    'plugins_loaded',
    static function (): void {
        Bootstrap::instance()->register();
    }
);
