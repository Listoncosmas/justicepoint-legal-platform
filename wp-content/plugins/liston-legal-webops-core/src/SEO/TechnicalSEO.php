<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\SEO;

use Liston\LegalWebOps\Migration\RedirectRepository;
use function Liston\LegalWebOps\field;

final class TechnicalSEO
{
    public function register(): void
    {
        add_action('init', [$this, 'remove_core_duplicates'], 20);
        add_action('wp_head', [$this, 'render_head'], 2);
        add_filter('document_title_parts', [$this, 'title_parts'], 20);
        add_filter('wp_robots', [$this, 'robots'], 100);
        add_action('send_headers', [$this, 'send_demo_headers']);
        add_filter('wp_sitemaps_post_types', [$this, 'sitemap_post_types']);
        add_filter('wp_sitemaps_posts_entry', [$this, 'sitemap_entry'], 10, 3);
        add_shortcode('justicepoint_breadcrumbs', static fn (): string => Breadcrumbs::render());
    }

    public function remove_core_duplicates(): void
    {
        remove_action('wp_head', 'rel_canonical');
        remove_action('wp_head', 'wp_shortlink_wp_head');
    }

    /** @param array<string,string> $parts @return array<string,string> */
    public function title_parts(array $parts): array
    {
        if ($this->third_party_seo_active()) {
            return $parts;
        }

        if (is_singular()) {
            $override = trim((string) field('seo_title', false, ''));
            if ($override !== '') {
                $parts['title'] = wp_strip_all_tags($override);
                unset($parts['site'], $parts['tagline']);
            }
        }

        return $parts;
    }

    /** @param array<string,bool|string> $robots @return array<string,bool|string> */
    public function robots(array $robots): array
    {
        if ($this->is_demo_environment()) {
            unset($robots['index'], $robots['follow']);
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
            $robots['noarchive'] = true;
            $robots['max-image-preview'] = 'large';
            return $robots;
        }

        if ($this->should_noindex()) {
            unset($robots['index']);
            $robots['noindex'] = true;
            $robots['follow'] = true;
        }
        return $robots;
    }

    public function send_demo_headers(): void
    {
        if ($this->is_demo_environment() && ! headers_sent()) {
            header('X-Robots-Tag: noindex, nofollow, noarchive', true);
        }
    }

    public function render_head(): void
    {
        if (is_admin() || $this->third_party_seo_active()) {
            return;
        }

        $canonical  = $this->canonical_url();
        $description = $this->description();
        $title      = wp_get_document_title();
        $image      = is_singular() && has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'large') : '';

        if ($canonical !== '') {
            printf("\n<link rel=\"canonical\" href=\"%s\">\n", esc_url($canonical));
        }
        if ($description !== '') {
            printf("<meta name=\"description\" content=\"%s\">\n", esc_attr($description));
        }
        printf("<meta property=\"og:type\" content=\"%s\">\n", is_singular() ? 'article' : 'website');
        printf("<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr(get_bloginfo('name')));
        printf("<meta property=\"og:title\" content=\"%s\">\n", esc_attr($title));
        printf("<meta property=\"og:url\" content=\"%s\">\n", esc_url($canonical));
        if ($description !== '') {
            printf("<meta property=\"og:description\" content=\"%s\">\n", esc_attr($description));
        }
        if ($image) {
            printf("<meta property=\"og:image\" content=\"%s\">\n", esc_url($image));
        }

        $graph = $this->schema_graph();
        if ($graph !== []) {
            echo '<script type="application/ld+json">' . wp_json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
        }
    }

    public function canonical_url(): string
    {
        if (is_404() || is_search()) {
            return '';
        }

        if (is_singular()) {
            $override = trim((string) field('canonical_override', false, ''));
            if ($override !== '' && $this->valid_canonical_override($override)) {
                return user_trailingslashit($override);
            }
            return user_trailingslashit((string) get_permalink());
        }

        if (is_front_page()) {
            return user_trailingslashit(home_url('/'));
        }

        $paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
        if ($paged > 1) {
            return user_trailingslashit((string) get_pagenum_link($paged));
        }

        if (is_post_type_archive()) {
            return user_trailingslashit((string) get_post_type_archive_link((string) get_query_var('post_type')));
        }

        if (is_tax() || is_category() || is_tag()) {
            $link = get_term_link(get_queried_object());
            return is_wp_error($link) ? '' : user_trailingslashit($link);
        }

        return '';
    }

    public function valid_canonical_override(string $url): bool
    {
        $validated = wp_http_validate_url($url);
        if (! $validated || ! in_array(wp_parse_url($validated, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return false;
        }
        $target_host = strtolower((string) wp_parse_url($validated, PHP_URL_HOST));
        $home_host   = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        return $target_host !== '' && hash_equals($home_host, $target_host) && ! str_contains($validated, '#');
    }

    /** @return array<int,array<string,mixed>> */
    public function schema_graph(): array
    {
        if (is_404() || is_search()) {
            return [];
        }

        $home = home_url('/');
        $graph = [];
        $organization = [
            '@type' => 'Organization',
            '@id'   => $home . '#organization',
            'name'  => 'JusticePoint Employment Law',
            'url'   => $home,
            'description' => 'A fictional multi-location employment law firm created as a technical demonstration.',
            'email' => 'hello@justicepoint.example',
        ];

        if (is_front_page()) {
            $graph[] = $organization;
        }

        if (is_singular('office')) {
            $graph[] = $this->legal_service_schema((int) get_the_ID());
        } elseif (is_singular('service_area')) {
            $office_id = absint(field('office'));
            $practice_id = absint(field('practice_area'));
            $schema = $this->legal_service_schema($office_id);
            $schema['@id'] = get_permalink() . '#legal-service';
            $schema['url'] = get_permalink();
            $schema['name'] = get_the_title();
            $schema['serviceType'] = $practice_id ? get_the_title($practice_id) : 'Employment law';
            $schema['areaServed'] = field('office_city', $office_id, get_the_title($office_id));
            $graph[] = $schema;
        } elseif (is_singular('attorney')) {
            $graph[] = $this->person_schema((int) get_the_ID());
        }

        $breadcrumbs = Breadcrumbs::items();
        if (count($breadcrumbs) > 1) {
            $list = [];
            foreach ($breadcrumbs as $index => $item) {
                $list[] = ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['name'], 'item' => $item['url']];
            }
            $graph[] = ['@type' => 'BreadcrumbList', '@id' => $this->canonical_url() . '#breadcrumb', 'itemListElement' => $list];
        }

        $faq_schema = $this->faq_schema();
        if ($faq_schema !== []) {
            $graph[] = $faq_schema;
        }

        return array_values(array_filter($graph));
    }

    /** @return array<string,mixed> */
    private function legal_service_schema(int $office_id): array
    {
        $url = $office_id ? get_permalink($office_id) : home_url('/');
        return [
            '@type' => 'LegalService',
            '@id'   => $url . '#legal-service',
            'name'  => $office_id ? (string) field('local_business_name', $office_id, get_the_title($office_id)) : 'JusticePoint Employment Law',
            'url'   => $url,
            'telephone' => (string) field('telephone', $office_id, '(213) 555-0148'),
            'parentOrganization' => ['@id' => home_url('/') . '#organization'],
            'priceRange' => '$$',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => (string) field('address', $office_id),
                'addressLocality' => (string) field('office_city', $office_id),
                'addressRegion' => (string) field('office_state', $office_id),
                'postalCode' => (string) field('zip_code', $office_id),
                'addressCountry' => 'US',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) field('latitude', $office_id, 0),
                'longitude' => (float) field('longitude', $office_id, 0),
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function person_schema(int $attorney_id): array
    {
        $image_id = absint(field('professional_image', $attorney_id, get_post_thumbnail_id($attorney_id)));
        $schema = [
            '@type' => 'Person',
            '@id'   => get_permalink($attorney_id) . '#person',
            'name'  => (string) field('fictional_name', $attorney_id, get_the_title($attorney_id)),
            'jobTitle' => (string) field('position', $attorney_id, 'Employment Attorney'),
            'url'   => get_permalink($attorney_id),
            'worksFor' => ['@id' => home_url('/') . '#organization'],
            'description' => wp_strip_all_tags((string) field('biography', $attorney_id, get_the_excerpt($attorney_id))),
        ];
        if ($image_id) {
            $schema['image'] = wp_get_attachment_image_url($image_id, 'large');
        }
        return $schema;
    }

    /** @return array<string,mixed> */
    private function faq_schema(): array
    {
        if (! is_singular(['practice_area', 'service_area', 'office'])) {
            return [];
        }
        $ids = array_values(array_filter(array_map('absint', (array) field('related_faqs', false, []))));
        if ($ids === []) {
            return [];
        }

        $entities = [];
        foreach ($ids as $id) {
            if (get_post_status($id) !== 'publish') {
                continue;
            }
            $answer = (string) field('answer', $id, get_post_field('post_content', $id));
            if ($answer === '') {
                continue;
            }
            $entities[] = [
                '@type' => 'Question',
                'name' => get_the_title($id),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => wp_strip_all_tags($answer)],
            ];
        }

        return $entities ? ['@type' => 'FAQPage', '@id' => $this->canonical_url() . '#faq', 'mainEntity' => $entities] : [];
    }

    private function description(): string
    {
        if (is_singular()) {
            $override = trim((string) field('meta_description', false, ''));
            if ($override !== '') {
                return mb_substr(wp_strip_all_tags($override), 0, 160);
            }
            $short = trim((string) field('short_description', false, ''));
            if ($short !== '') {
                return mb_substr(wp_strip_all_tags($short), 0, 160);
            }
            return mb_substr(wp_strip_all_tags(get_the_excerpt()), 0, 160);
        }
        return (string) get_bloginfo('description');
    }

    private function should_noindex(): bool
    {
        if (is_search() || is_404()) {
            return true;
        }
        if (isset($_GET['city']) || isset($_GET['state']) || isset($_GET['practice_area']) || isset($_GET['page'])) {
            return true;
        }
        return is_singular() && field('indexation', false, 'index') === 'noindex';
    }

    private function is_demo_environment(): bool
    {
        $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        return ! (bool) get_option('blog_public') || in_array($host, ['localhost', '127.0.0.1'], true) || str_ends_with($host, '.test') || str_ends_with($host, '.local');
    }

    private function third_party_seo_active(): bool
    {
        return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION') || class_exists('The_SEO_Framework\Load');
    }

    /** @param array<string,\WP_Post_Type> $post_types @return array<string,\WP_Post_Type> */
    public function sitemap_post_types(array $post_types): array
    {
        unset($post_types['faq'], $post_types['elementor_library']);
        return $post_types;
    }

    /** @param array<string,string> $entry @return array<string,string> */
    public function sitemap_entry(array $entry, \WP_Post $post, string $post_type): array
    {
        unset($post_type);
        if (get_post_meta($post->ID, '_jp_exclude_sitemap', true)) {
            return [];
        }
        $path = (string) wp_parse_url($entry['loc'] ?? '', PHP_URL_PATH);
        if ($path !== '' && (new RedirectRepository())->find($path)) {
            return [];
        }
        return $entry;
    }
}

