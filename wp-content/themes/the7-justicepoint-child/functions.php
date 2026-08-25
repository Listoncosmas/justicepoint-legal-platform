<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('JP_THEME_VERSION', '1.0.0');

require_once get_stylesheet_directory() . '/inc/template-functions.php';

add_action(
    'after_setup_theme',
    static function (): void {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
        add_theme_support('responsive-embeds');
        add_theme_support('editor-styles');
        register_nav_menus(['primary' => 'Primary navigation', 'footer' => 'Footer navigation']);
        add_image_size('jp-attorney-card', 720, 900, true);
        add_image_size('jp-attorney-mobile', 480, 600, true);
        add_image_size('jp-wide', 1600, 900, true);
    },
    20
);

add_action(
    'wp_enqueue_scripts',
    static function (): void {
        $css = get_stylesheet_directory() . '/assets/css/main.css';
        $js  = get_stylesheet_directory() . '/assets/dist/theme.js';
        $js_url = file_exists($js) ? get_stylesheet_directory_uri() . '/assets/dist/theme.js' : get_stylesheet_directory_uri() . '/assets/js/theme.js';
        wp_enqueue_style('justicepoint-theme', get_stylesheet_directory_uri() . '/assets/css/main.css', [], file_exists($css) ? (string) filemtime($css) : JP_THEME_VERSION);
        wp_enqueue_script('justicepoint-theme', $js_url, [], file_exists($js) ? (string) filemtime($js) : JP_THEME_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
    },
    100
);

add_action(
    'wp_head',
    static function (): void {
        printf('<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url(get_stylesheet_directory_uri() . '/assets/images/favicon.svg'));
        if (is_front_page()) {
            printf(
                '<link rel="preload" as="image" href="%1$s" imagesrcset="%2$s 900w, %1$s 1897w" imagesizes="100vw" fetchpriority="high">' . "\n",
                esc_url(get_stylesheet_directory_uri() . '/assets/images/justicepoint-hero.webp'),
                esc_url(get_stylesheet_directory_uri() . '/assets/images/justicepoint-hero-mobile.webp')
            );
        }
    },
    1
);

add_filter('body_class', static function (array $classes): array {
    $classes[] = 'jp-site';
    if (is_singular(['practice_area', 'service_area', 'office', 'attorney'])) {
        $classes[] = 'jp-structured-content';
    }
    return $classes;
});

add_action('init', static function (): void {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_print_font_faces', 50);
});

add_filter('use_default_gallery_style', '__return_false');
add_filter('should_load_separate_core_block_assets', '__return_true');
add_filter('elementor/frontend/print_google_fonts', '__return_false');

add_filter(
    'wp_get_attachment_image_attributes',
    static function (array $attributes): array {
        if (str_contains((string) ($attributes['class'] ?? ''), 'jp-lazy-attorney')) {
            $attributes['loading'] = 'lazy';
            unset($attributes['fetchpriority']);
        }
        return $attributes;
    },
    100
);

add_filter(
    'wp_content_img_tag',
    static function (string $image): string {
        if (! str_contains($image, 'jp-lazy-attorney')) {
            return $image;
        }
        $processor = new WP_HTML_Tag_Processor($image);
        if ($processor->next_tag('IMG')) {
            $processor->set_attribute('loading', 'lazy');
            $processor->remove_attribute('fetchpriority');
            return $processor->get_updated_html();
        }
        return $image;
    },
    100
);

add_action('wp', static function (): void {
    remove_action('wp_head', 'wp_print_font_faces', 50);
});

add_action(
    'after_setup_theme',
    static function (): void {
        $class = 'The7\\Mods\\Compatibility\\Gutenberg\\Block_Theme\\The7_Block_Theme_Compatibility';
        if (class_exists($class) && $class::$instance) {
            remove_action('wp_body_open', [$class::$instance, 'render_skip_link']);
        }

        $font_manager = 'The7\\Mods\\Compatibility\\Gutenberg\\Block_Theme\\The7_FSE_Font_Manager';
        if (class_exists($font_manager) && $font_manager::$instance) {
            remove_action('wp_head', [$font_manager::$instance, 'print_font_faces'], 50);
        }
    },
    999
);

add_action(
    'wp_enqueue_scripts',
    static function (): void {
        // Custom templates do not use The7 post-type or FSE presentation assets.
        wp_dequeue_style('the7-fse-styles');
        wp_dequeue_style('the7-core');
        wp_dequeue_script('the7-core');
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('global-styles');
        wp_dequeue_style('classic-theme-styles');
    },
    999
);
