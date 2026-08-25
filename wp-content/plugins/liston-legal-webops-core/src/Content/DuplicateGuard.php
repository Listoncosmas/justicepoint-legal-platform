<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Content;

use function Liston\LegalWebOps\field;

final class DuplicateGuard
{
    private static bool $updating = false;

    public function register(): void
    {
        add_action('save_post', [$this, 'prevent_duplicate'], 99, 3);
        add_action('admin_notices', [$this, 'notice']);
        add_filter('acf/validate_value/name=office', [$this, 'validate_acf_combination'], 10, 4);
    }

    public function prevent_duplicate(int $post_id, \WP_Post $post, bool $update): void
    {
        unset($update);
        if ($post->post_type !== 'service_area' || self::$updating || wp_is_post_revision($post_id) || $post->post_status === 'trash') {
            return;
        }

        $practice_id = absint(field('practice_area', $post_id));
        $office_id   = absint(field('office', $post_id));
        if (! $practice_id || ! $office_id) {
            return;
        }

        $duplicate = self::find_duplicate($practice_id, $office_id, $post_id);
        if (! $duplicate) {
            return;
        }

        self::$updating = true;
        wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
        self::$updating = false;
        set_transient('jp_duplicate_service_' . get_current_user_id(), $duplicate, 60);
    }

    public static function find_duplicate(int $practice_id, int $office_id, int $exclude_id = 0): int
    {
        $ids = get_posts([
            'post_type'              => 'service_area',
            'post_status'            => ['publish', 'draft', 'pending', 'private', 'future'],
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'post__not_in'           => $exclude_id ? [$exclude_id] : [],
            'meta_query'             => [
                'relation' => 'AND',
                ['key' => 'practice_area', 'value' => $practice_id, 'compare' => '=', 'type' => 'NUMERIC'],
                ['key' => 'office', 'value' => $office_id, 'compare' => '=', 'type' => 'NUMERIC'],
            ],
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        return (int) ($ids[0] ?? 0);
    }

    public function validate_acf_combination(mixed $valid, mixed $value, array $field, string $input): mixed
    {
        unset($field, $input);
        if ($valid !== true) {
            return $valid;
        }
        $post_id = isset($_POST['post_ID']) ? absint($_POST['post_ID']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF verifies its save nonce before this validation filter.
        $practice = isset($_POST['acf']['field_jp_service_area_practice_area']) ? absint($_POST['acf']['field_jp_service_area_practice_area']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF verifies its save nonce before this validation filter.
        $office = absint($value);
        $duplicate = ($practice && $office) ? self::find_duplicate($practice, $office, $post_id) : 0;

        return $duplicate ? sprintf('That practice-area and office combination already exists in service area #%d.', $duplicate) : $valid;
    }

    public function notice(): void
    {
        $key = 'jp_duplicate_service_' . get_current_user_id();
        $duplicate = (int) get_transient($key);
        if (! $duplicate) {
            return;
        }
        delete_transient($key);
        printf('<div class="notice notice-error"><p><strong>Duplicate prevented:</strong> this practice-area/location combination already exists as <a href="%1$s">service area #%2$s</a>. The new record remains a draft.</p></div>', esc_url(get_edit_post_link($duplicate)), esc_html((string) $duplicate));
    }
}
