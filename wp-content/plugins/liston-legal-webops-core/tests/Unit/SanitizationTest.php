<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Tests\Unit;

use Liston\LegalWebOps\Content\Fields;
use PHPUnit\Framework\TestCase;

final class SanitizationTest extends TestCase
{
    public function test_registered_meta_sanitization_strips_script_markup(): void
    {
        $value = Fields::sanitize_registered_meta('<script>alert(1)</script><strong>Allowed</strong>', 'short_description');
        self::assertStringNotContainsString('<script>', (string) $value);
        self::assertStringNotContainsString('<strong>', (string) $value);
    }
}

