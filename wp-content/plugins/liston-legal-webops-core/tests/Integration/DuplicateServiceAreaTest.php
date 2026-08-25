<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Tests\Integration;

use Liston\LegalWebOps\Content\DuplicateGuard;
use PHPUnit\Framework\TestCase;

final class DuplicateServiceAreaTest extends TestCase
{
    /** @var array<int,int> */
    private array $ids = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->ids) as $id) {
            wp_delete_post($id, true);
        }
        parent::tearDown();
    }

    public function test_duplicate_combination_is_reverted_to_draft(): void
    {
        $practice = $this->create('practice_area', 'Test Practice');
        $office = $this->create('office', 'Test Office');
        $first = $this->create('service_area', 'First Service');
        update_post_meta($first, 'practice_area', $practice);
        update_post_meta($first, 'office', $office);

        $duplicate = $this->create('service_area', 'Duplicate Service');
        update_post_meta($duplicate, 'practice_area', $practice);
        update_post_meta($duplicate, 'office', $office);
        (new DuplicateGuard())->prevent_duplicate($duplicate, get_post($duplicate), true);

        self::assertSame('draft', get_post_status($duplicate));
        self::assertSame($first, DuplicateGuard::find_duplicate($practice, $office, $duplicate));
    }

    private function create(string $type, string $title): int
    {
        $id = wp_insert_post(['post_type' => $type, 'post_status' => 'publish', 'post_title' => $title]);
        self::assertIsInt($id);
        $this->ids[] = $id;
        return $id;
    }
}

