<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Tests\Integration;

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
}

