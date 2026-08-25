<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Elementor;

use function Liston\LegalWebOps\field;

final class DirectoryRenderer
{
    /**
     * @param array{page?:int,per_page?:int,city?:string,state?:string,practice_area?:string} $filters
     * @return array{items:array<int,array<string,mixed>>,total:int,pages:int}|\WP_Error
     */
    public static function query(array $filters): array|\WP_Error
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $per_page = min(50, max(1, (int) ($filters['per_page'] ?? 12)));
        $tax_query = [];
        $allowed = ['city' => 'city', 'state' => 'state', 'practice_area' => 'practice_category'];
        foreach ($allowed as $parameter => $taxonomy) {
            $slug = sanitize_title((string) ($filters[$parameter] ?? ''));
            if ($slug !== '') {
                $tax_query[] = ['taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => [$slug]];
            }
        }
        if (count($tax_query) > 1) {
            $tax_query['relation'] = 'AND';
        }

        $query = new \WP_Query([
            'post_type'              => 'office',
            'post_status'            => 'publish',
            'posts_per_page'         => $per_page,
            'paged'                  => $page,
            'orderby'                => ['menu_order' => 'ASC', 'title' => 'ASC'],
            'tax_query'              => $tax_query,
            'fields'                 => 'ids',
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        $ids = array_map('intval', $query->posts);
        if ($ids) {
            update_meta_cache('post', $ids);
            update_object_term_cache($ids, 'office');
        }
        $items = [];
        foreach ($ids as $id) {
            $image_id = get_post_thumbnail_id($id);
            $items[] = [
                'id'               => $id,
                'title'            => get_the_title($id),
                'url'              => get_permalink($id),
                'address'          => (string) field('address', $id),
                'city'             => (string) field('office_city', $id),
                'state'            => (string) field('office_state', $id),
                'zip'              => (string) field('zip_code', $id),
                'telephone'        => (string) field('telephone', $id),
                'telephone_uri'    => preg_replace('/[^+\d]/', '', (string) field('telephone', $id)),
                'hours'            => (string) field('office_hours', $id),
                'consultation_url' => (string) field('consultation_url', $id, home_url('/consultation/')),
                'latitude'         => (float) field('latitude', $id),
                'longitude'        => (float) field('longitude', $id),
                'image'            => $image_id ? wp_get_attachment_image_url($image_id, 'medium_large') : '',
            ];
        }

        return ['items' => $items, 'total' => (int) $query->found_posts, 'pages' => (int) $query->max_num_pages];
    }

    /** @param array<string,mixed> $settings */
    public static function render(array $settings = []): string
    {
        $filters = [
            'page'          => isset($_GET['directory_page']) ? max(1, absint($_GET['directory_page'])) : 1,
            'per_page'      => min(50, max(1, (int) ($settings['per_page'] ?? 12))),
            'city'          => isset($_GET['city']) ? sanitize_title(wp_unslash($_GET['city'])) : '',
            'state'         => isset($_GET['state']) ? sanitize_title(wp_unslash($_GET['state'])) : '',
            'practice_area' => isset($_GET['practice_area']) ? sanitize_title(wp_unslash($_GET['practice_area'])) : '',
        ];
        $result = self::query($filters);
        if (is_wp_error($result)) {
            return '<div class="jp-notice jp-notice--error" role="alert">' . esc_html($result->get_error_message()) . '</div>';
        }
        $cities = get_terms(['taxonomy' => 'city', 'hide_empty' => true]);
        $states = get_terms(['taxonomy' => 'state', 'hide_empty' => true]);
        $practices = get_posts(['post_type' => 'practice_area', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC']);

        ob_start();
        ?>
        <section class="jp-directory" data-jp-directory data-endpoint="<?php echo esc_url(rest_url('liston-webops/v1/offices')); ?>">
            <form class="jp-directory__filters" method="get" action="<?php echo esc_url(get_permalink()); ?>" aria-label="Filter offices">
                <div class="jp-field"><label for="jp-city">City</label><select id="jp-city" name="city"><option value="">All cities</option><?php foreach ($cities as $city) : ?><option value="<?php echo esc_attr($city->slug); ?>" <?php selected($filters['city'], $city->slug); ?>><?php echo esc_html($city->name); ?></option><?php endforeach; ?></select></div>
                <div class="jp-field"><label for="jp-state">State</label><select id="jp-state" name="state"><option value="">All states</option><?php foreach ($states as $state) : ?><option value="<?php echo esc_attr($state->slug); ?>" <?php selected($filters['state'], $state->slug); ?>><?php echo esc_html($state->name); ?></option><?php endforeach; ?></select></div>
                <div class="jp-field"><label for="jp-practice">Practice area</label><select id="jp-practice" name="practice_area"><option value="">All practice areas</option><?php foreach ($practices as $practice) : ?><option value="<?php echo esc_attr($practice->post_name); ?>" <?php selected($filters['practice_area'], $practice->post_name); ?>><?php echo esc_html($practice->post_title); ?></option><?php endforeach; ?></select></div>
                <button class="jp-button jp-button--secondary" type="submit">Apply filters</button>
                <a class="jp-directory__reset" href="<?php echo esc_url(get_permalink()); ?>">Reset</a>
            </form>
            <p class="jp-directory__status" aria-live="polite"><strong><?php echo esc_html((string) $result['total']); ?></strong> office<?php echo $result['total'] === 1 ? '' : 's'; ?> found</p>
            <div class="jp-directory__layout">
                <div class="jp-directory__map" data-jp-map role="region" aria-label="Map showing JusticePoint office locations"></div>
                <div class="jp-directory__list" data-jp-office-list>
                    <?php echo self::cards($result['items']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
            <noscript><p class="jp-notice">The filter form and complete office list work without JavaScript. The interactive map requires JavaScript.</p></noscript>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    /** @param array<int,array<string,mixed>> $items */
    public static function cards(array $items): string
    {
        if ($items === []) {
            return '<div class="jp-empty"><h2>No matching offices</h2><p>Try clearing one or more filters, or call our central intake team.</p></div>';
        }
        ob_start();
        foreach ($items as $item) :
            ?>
            <article class="jp-office-row" data-office-card data-lat="<?php echo esc_attr((string) $item['latitude']); ?>" data-lng="<?php echo esc_attr((string) $item['longitude']); ?>" data-title="<?php echo esc_attr((string) $item['title']); ?>">
                <div class="jp-office-row__index" aria-hidden="true"></div>
                <div>
                    <p class="jp-eyebrow">JusticePoint office</p>
                    <h2><a href="<?php echo esc_url((string) $item['url']); ?>"><?php echo esc_html((string) $item['title']); ?></a></h2>
                    <address><?php echo esc_html((string) $item['address']); ?><br><?php echo esc_html((string) $item['city'] . ', ' . $item['state'] . ' ' . $item['zip']); ?></address>
                </div>
                <div class="jp-office-row__actions">
                    <a class="jp-text-link" href="tel:<?php echo esc_attr((string) $item['telephone_uri']); ?>"><?php echo esc_html((string) $item['telephone']); ?></a>
                    <a class="jp-button jp-button--small" href="<?php echo esc_url((string) $item['consultation_url']); ?>">Request consultation</a>
                </div>
            </article>
            <?php
        endforeach;
        return (string) ob_get_clean();
    }
}
