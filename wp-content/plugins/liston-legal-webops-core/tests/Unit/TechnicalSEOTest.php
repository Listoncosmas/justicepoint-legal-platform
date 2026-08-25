<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Tests\Unit;

use Liston\LegalWebOps\SEO\TechnicalSEO;
use PHPUnit\Framework\TestCase;

final class TechnicalSEOTest extends TestCase
{
    public function test_canonical_override_must_be_same_origin_and_http(): void
    {
        $seo = new TechnicalSEO();
        self::assertTrue($seo->valid_canonical_override(home_url('/canonical-target/')));
        self::assertFalse($seo->valid_canonical_override('https://unrelated.example/target/'));
        self::assertFalse($seo->valid_canonical_override('javascript:alert(1)'));
        self::assertFalse($seo->valid_canonical_override(home_url('/canonical-target/?preview=1')));
        self::assertFalse($seo->valid_canonical_override(home_url('/canonical-target/#section')));
    }
}
