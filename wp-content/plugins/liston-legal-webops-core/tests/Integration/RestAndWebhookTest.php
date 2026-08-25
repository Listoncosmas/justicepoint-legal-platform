<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Tests\Integration;

use Liston\LegalWebOps\Integrations\WebhookClient;
use PHPUnit\Framework\TestCase;

final class RestAndWebhookTest extends TestCase
{
    public function test_office_endpoint_rejects_unbounded_page_size(): void
    {
        do_action('rest_api_init');
        $request = new \WP_REST_Request('GET', '/liston-webops/v1/offices');
        $request->set_param('per_page', 999);
        $response = rest_get_server()->dispatch($request);
        self::assertSame(400, $response->get_status());
    }

    public function test_office_endpoint_accepts_empty_optional_slugs(): void
    {
        do_action('rest_api_init');
        $request = new \WP_REST_Request('GET', '/liston-webops/v1/offices');
        $request->set_query_params(['city' => 'los-angeles', 'per_page' => 2]);
        $response = rest_get_server()->dispatch($request);

        self::assertSame(200, $response->get_status());
        self::assertMatchesRegularExpression('/^\d+$/', $response->get_headers()['X-WP-Total']);
        self::assertIsArray($response->get_data());
    }

    public function test_mock_webhook_confirms_success_without_persisting_payload(): void
    {
        putenv('JP_CRM_WEBHOOK_URL');
        $result = (new WebhookClient())->send(['name' => 'Private Test']);
        self::assertTrue($result['success']);
        self::assertTrue($result['mock']);
        self::assertNotEmpty($result['request_id']);
    }

    public function test_webhook_reports_failure_after_safe_retries(): void
    {
        putenv('JP_CRM_WEBHOOK_URL=https://example.com/consultations');
        $filter = static fn () => new \WP_Error('simulated_failure', 'Simulated transport failure.');
        add_filter('pre_http_request', $filter);
        $result = (new WebhookClient())->send(['name' => 'Private Test']);
        remove_filter('pre_http_request', $filter);
        putenv('JP_CRM_WEBHOOK_URL');
        self::assertFalse($result['success']);
        self::assertFalse($result['mock']);
    }
}
