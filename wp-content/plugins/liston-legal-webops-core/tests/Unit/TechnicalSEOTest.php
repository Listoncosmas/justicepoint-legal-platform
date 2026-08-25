<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Tests\Unit;

use Liston\LegalWebOps\SEO\TechnicalSEO;
use PHPUnit\Framework\TestCase;

final class TechnicalSEOTest extends TestCase
{
    private int $front_page_id = 0;

    protected function tearDown(): void
    {
        if ($this->front_page_id) {
            wp_delete_post($this->front_page_id, true);
        }
        update_option('show_on_front', 'posts');
        update_option('page_on_front', 0);
        wp_reset_query();
        parent::tearDown();
    }

    public function test_canonical_override_must_be_same_origin_and_http(): void
    {
        $seo = new TechnicalSEO();
        self::assertTrue($seo->valid_canonical_override(home_url('/canonical-target/')));
        self::assertFalse($seo->valid_canonical_override('https://unrelated.example/target/'));
        self::assertFalse($seo->valid_canonical_override('javascript:alert(1)'));
        self::assertFalse($seo->valid_canonical_override(home_url('/canonical-target/?preview=1')));
        self::assertFalse($seo->valid_canonical_override(home_url('/canonical-target/#section')));
    }

    public function test_static_front_page_uses_website_open_graph_type(): void
    {
        global $post, $wp_query;
        $this->front_page_id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'SEO Front Page']);
        update_option('show_on_front', 'page');
        update_option('page_on_front', $this->front_page_id);
        $wp_query = new \WP_Query(['page_id' => $this->front_page_id]);
        $post = $wp_query->post;
        setup_postdata($post);

        self::assertTrue(is_front_page());
        self::assertSame('website', (new TechnicalSEO())->open_graph_type());
    }
}
