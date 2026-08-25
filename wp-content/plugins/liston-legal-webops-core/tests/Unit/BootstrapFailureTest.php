<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BootstrapFailureTest extends TestCase
{
    public function test_missing_wordpress_returns_a_nonzero_exit_code(): void
    {
        if (str_contains((string) ini_get('disable_functions'), 'exec')) {
            self::markTestSkipped('The PHP exec function is disabled.');
        }

        $missing_root = sys_get_temp_dir() . '/justicepoint-missing-wordpress-' . bin2hex(random_bytes(6));
        $probe = dirname(__DIR__) . '/fixtures/bootstrap-probe.php';
        $command = 'JP_TEST_ROOT=' . escapeshellarg($missing_root) . ' ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($probe) . ' 2>&1';
        $output = [];
        $exit_code = 0;
        exec($command, $output, $exit_code);

        self::assertNotSame(0, $exit_code, 'A missing WordPress installation must fail the test process.');
        self::assertStringContainsString('JusticePoint test bootstrap failed', implode("\n", $output));
    }
}
