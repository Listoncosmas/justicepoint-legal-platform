<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Tests\Integration;

use Liston\LegalWebOps\SEO\TechnicalSEO;
use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase
{
    private int $attorney_id = 0;

    protected function tearDown(): void
    {
        if ($this->attorney_id) {
            wp_delete_post($this->attorney_id, true);
        }
        wp_reset_query();
        parent::tearDown();
    }

    public function test_attorney_page_generates_person_and_breadcrumb_schema(): void
    {
        global $wp_query, $post;
        $this->attorney_id = wp_insert_post(['post_type' => 'attorney', 'post_status' => 'publish', 'post_title' => 'Fictional Tester']);
        update_post_meta($this->attorney_id, 'fictional_name', 'Fictional Tester');
        $wp_query = new \WP_Query(['p' => $this->attorney_id, 'post_type' => 'attorney']);
        $post = $wp_query->post;
        setup_postdata($post);
        $types = array_column((new TechnicalSEO())->schema_graph(), '@type');
        self::assertContains('Person', $types);
        self::assertContains('BreadcrumbList', $types);
    }
}

