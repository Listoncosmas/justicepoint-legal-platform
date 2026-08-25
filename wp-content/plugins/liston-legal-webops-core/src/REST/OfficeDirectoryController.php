<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\REST;

use Liston\LegalWebOps\Elementor\DirectoryRenderer;

final class OfficeDirectoryController
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes(): void
    {
        register_rest_route(
            'liston-webops/v1',
            '/offices',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'index'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'page' => [
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => static fn ($value): bool => is_numeric($value) && (int) $value >= 1,
                    ],
                    'per_page' => [
                        'default' => 12,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => static fn ($value): bool => is_numeric($value) && (int) $value >= 1 && (int) $value <= 50,
                    ],
                    'city' => $this->slug_argument(),
                    'state' => $this->slug_argument(),
                    'practice_area' => $this->slug_argument(),
                ],
            ]
        );
    }

    public function index(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $filters = [
            'page'          => max(1, (int) $request->get_param('page')),
            'per_page'      => min(50, max(1, (int) $request->get_param('per_page'))),
            'city'          => sanitize_title((string) $request->get_param('city')),
            'state'         => sanitize_title((string) $request->get_param('state')),
            'practice_area' => sanitize_title((string) $request->get_param('practice_area')),
        ];
        $cache_key = 'jp_offices_' . md5(wp_json_encode($filters));
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            $response = rest_ensure_response($cached['items']);
            $response->header('X-WP-Total', (string) $cached['total']);
            $response->header('X-WP-TotalPages', (string) $cached['pages']);
            $response->header('X-JusticePoint-Cache', 'HIT');
            return $response;
        }

        $result = DirectoryRenderer::query($filters);
        if (is_wp_error($result)) {
            return $result;
        }
        $payload = ['items' => $result['items'], 'total' => $result['total'], 'pages' => $result['pages']];
        set_transient($cache_key, $payload, 5 * MINUTE_IN_SECONDS);
        $response = rest_ensure_response($result['items']);
        $response->header('X-WP-Total', (string) $result['total']);
        $response->header('X-WP-TotalPages', (string) $result['pages']);
        $response->header('X-JusticePoint-Cache', 'MISS');
        return $response;
    }

    /** @return array<string,mixed> */
    private function slug_argument(): array
    {
        return [
            'default' => '',
            // REST sanitizers receive the value, request, and parameter name.
            // Passing sanitize_title() directly makes the request object its
            // fallback title when the value is empty.
            'sanitize_callback' => static fn ($value): string => sanitize_title(is_string($value) ? $value : ''),
            'validate_callback' => static fn ($value): bool => $value === '' || (is_string($value) && (bool) preg_match('/^[a-z0-9-]+$/', $value)),
        ];
    }
}
