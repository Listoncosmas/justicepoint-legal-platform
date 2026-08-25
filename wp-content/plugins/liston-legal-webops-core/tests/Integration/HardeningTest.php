<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class HardeningTest extends TestCase
{
    protected function tearDown(): void
    {
        wp_set_current_user(0);
        parent::tearDown();
    }

    public function test_anonymous_user_collection_is_not_enumerable(): void
    {
        wp_set_current_user(0);
        $response = rest_do_request(new \WP_REST_Request('GET', '/wp/v2/users'));

        self::assertSame(401, $response->get_status());
        self::assertSame('rest_not_logged_in', $response->get_data()['code']);
    }
}
