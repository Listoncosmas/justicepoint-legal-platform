<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Tests\Unit;

use Liston\LegalWebOps\Migration\RedirectRepository;
use Liston\LegalWebOps\Migration\RedirectValidator;
use PHPUnit\Framework\TestCase;

final class RedirectValidatorTest extends TestCase
{
    public function test_normalizes_paths_without_query_strings(): void
    {
        self::assertSame('/services/example.html', RedirectRepository::normalize_path('https://example.test/services/example.html?utm=legacy'));
    }

    public function test_detects_loops_chains_duplicates_and_missing_destinations(): void
    {
        $result = (new RedirectValidator())->validate_file(dirname(__DIR__) . '/fixtures/redirects-invalid.csv', false);
        $codes = array_column($result['issues'], 'code');
        self::assertFalse($result['valid']);
        self::assertContains('duplicate_source', $codes);
        self::assertContains('redirect_loop', $codes);
        self::assertContains('redirect_chain', $codes);
        self::assertContains('missing_destination', $codes);
    }
}

