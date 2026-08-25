<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\SEO;

final class Breadcrumbs
{
    /** @return array<int,array{name:string,url:string}> */
    public static function items(): array
    {
        $items = [['name' => 'Home', 'url' => home_url('/')]];

        if (is_front_page()) {
            return $items;
        }

        if (is_singular()) {
            $post_type = get_post_type();
            $object    = $post_type ? get_post_type_object($post_type) : null;
            if ($object && $object->has_archive) {
                $items[] = [
                    'name' => (string) $object->labels->name,
                    'url'  => (string) get_post_type_archive_link($post_type),
                ];
            } elseif (is_page() && wp_get_post_parent_id(get_the_ID())) {
                foreach (array_reverse(get_post_ancestors(get_the_ID())) as $ancestor) {
                    $items[] = ['name' => get_the_title($ancestor), 'url' => get_permalink($ancestor)];
                }
            }
            $items[] = ['name' => get_the_title(), 'url' => get_permalink()];
        } elseif (is_post_type_archive()) {
            $object = get_queried_object();
            $items[] = ['name' => (string) ($object->labels->name ?? post_type_archive_title('', false)), 'url' => self::current_url()];
        } elseif (is_tax() || is_category() || is_tag()) {
            $object = get_queried_object();
            $items[] = ['name' => (string) ($object->name ?? 'Archive'), 'url' => self::current_url()];
        } elseif (is_search()) {
            $items[] = ['name' => 'Search', 'url' => self::current_url()];
        } elseif (is_404()) {
            $items[] = ['name' => 'Page not found', 'url' => self::current_url()];
        }

        return $items;
    }

    public static function render(): string
    {
        $items = self::items();
        if (count($items) < 2) {
            return '';
        }

        $html = '<nav class="jp-breadcrumbs" aria-label="Breadcrumb"><ol>';
        foreach ($items as $index => $item) {
            $current = $index === array_key_last($items);
            $html .= '<li>';
            $html .= $current
                ? '<span aria-current="page">' . esc_html($item['name']) . '</span>'
                : '<a href="' . esc_url($item['url']) . '">' . esc_html($item['name']) . '</a>';
            $html .= '</li>';
        }
        return $html . '</ol></nav>';
    }

    private static function current_url(): string
    {
        $request = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
        return home_url(strtok((string) $request, '?') ?: '/');
    }
}
