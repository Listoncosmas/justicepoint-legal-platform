<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Elementor;

use Liston\LegalWebOps\Elementor\Widgets\AttorneyGrid;
use Liston\LegalWebOps\Elementor\Widgets\ContextualConsultationCTA;
use Liston\LegalWebOps\Elementor\Widgets\OfficeDirectoryMap;
use Liston\LegalWebOps\Elementor\Widgets\RelatedPracticeAreas;

final class WidgetManager
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'assets']);
        add_action('elementor/elements/categories_registered', [$this, 'category']);
        add_action('elementor/widgets/register', [$this, 'widgets']);
        add_shortcode('justicepoint_office_directory', static fn (array $attributes = []): string => self::directory_shortcode($attributes));
        add_shortcode('justicepoint_dynamic_service_area', static function (): string {
            if (! is_singular('service_area')) {
                return '';
            }
            $template = locate_template('template-parts/content-service-area.php');
            if ($template === '') {
                return '<p>Service-area template is unavailable.</p>';
            }
            ob_start();
            include $template;
            return (string) ob_get_clean();
        });
    }

    public function assets(): void
    {
        $widget_css = JP_WEBOPS_PATH . 'assets/css/widgets.css';
        $directory_js = JP_WEBOPS_PATH . 'assets/dist/directory.js';
        wp_register_style('jp-elementor-widgets', JP_WEBOPS_URL . 'assets/css/widgets.css', [], is_file($widget_css) ? (string) filemtime($widget_css) : JP_WEBOPS_VERSION);
        wp_register_style('jp-maplibre', JP_WEBOPS_URL . 'assets/dist/maplibre-gl.css', [], '5.7.0');
        wp_register_script('jp-office-directory', JP_WEBOPS_URL . 'assets/dist/directory.js', [], is_file($directory_js) ? (string) filemtime($directory_js) : JP_WEBOPS_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
    }

    public function category(\Elementor\Elements_Manager $manager): void
    {
        $manager->add_category('legal-webops', ['title' => 'Legal WebOps', 'icon' => 'fa fa-balance-scale']);
    }

    public function widgets(\Elementor\Widgets_Manager $manager): void
    {
        if (! class_exists('Elementor\\Widget_Base')) {
            return;
        }
        $manager->register(new ContextualConsultationCTA());
        $manager->register(new RelatedPracticeAreas());
        $manager->register(new OfficeDirectoryMap());
        $manager->register(new AttorneyGrid());
    }

    /** @param array<string,mixed> $attributes */
    private static function directory_shortcode(array $attributes): string
    {
        wp_enqueue_style('jp-elementor-widgets');
        wp_enqueue_style('jp-maplibre');
        wp_enqueue_script('jp-office-directory');
        return DirectoryRenderer::render(['per_page' => absint($attributes['per_page'] ?? 12)]);
    }
}
