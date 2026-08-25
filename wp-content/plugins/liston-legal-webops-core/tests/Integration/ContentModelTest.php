<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class ContentModelTest extends TestCase
{
    public function test_required_post_types_and_taxonomies_are_registered(): void
    {
        foreach (['practice_area', 'office', 'service_area', 'attorney', 'faq'] as $post_type) {
            self::assertTrue(post_type_exists($post_type), $post_type . ' should be registered.');
            self::assertTrue((bool) get_post_type_object($post_type)?->show_in_rest);
        }
        foreach (['practice_category', 'city', 'state', 'attorney_specialty'] as $taxonomy) {
            self::assertTrue(taxonomy_exists($taxonomy), $taxonomy . ' should be registered.');
        }
    }
}

