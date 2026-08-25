<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Tests\Integration;

use Liston\LegalWebOps\CLI\SeedCommand;
use Liston\LegalWebOps\Elementor\WidgetManager;
use PHPUnit\Framework\TestCase;

final class ElementorDependenciesTest extends TestCase
{
    public function test_directory_assets_are_registered_but_not_globally_enqueued(): void
    {
        (new WidgetManager())->assets();
        self::assertTrue(wp_script_is('jp-office-directory', 'registered'));
        self::assertFalse(wp_script_is('jp-office-directory', 'enqueued'));
        self::assertTrue(wp_style_is('jp-elementor-widgets', 'registered'));
    }

    public function test_service_template_places_custom_widgets_directly(): void
    {
        $widget_types = [];
        $shortcodes = [];
        $collect = static function (array $elements) use (&$collect, &$widget_types, &$shortcodes): void {
            foreach ($elements as $element) {
                if (isset($element['widgetType'])) {
                    $widget_types[] = (string) $element['widgetType'];
                }
                if (isset($element['settings']['shortcode'])) {
                    $shortcodes[] = (string) $element['settings']['shortcode'];
                }
                if (! empty($element['elements']) && is_array($element['elements'])) {
                    $collect($element['elements']);
                }
            }
        };
        $collect(SeedCommand::service_area_template_data());

        self::assertContains('jp-contextual-consultation', $widget_types);
        self::assertContains('jp-related-practices', $widget_types);
        self::assertStringContainsString('include_context_cta="no"', implode(' ', $shortcodes));
    }
}
