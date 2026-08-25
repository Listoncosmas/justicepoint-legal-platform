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
        add_action('template_redirect', [$this, 'disable_unused_archives'], 0);
        add_action('template_redirect', [$this, 'redirect_attachment_pages'], 1);
        add_filter('wp_sitemaps_enabled', '__return_true');
        add_filter('wp_sitemaps_post_types', [$this, 'sitemap_post_types']);
        add_filter('wp_sitemaps_taxonomies', [$this, 'sitemap_taxonomies']);
        add_filter('wp_sitemaps_add_provider', [$this, 'sitemap_provider'], 10, 2);
        add_filter('wp_sitemaps_posts_entry', [$this, 'sitemap_entry'], 10, 3);
        add_filter('robots_txt', [$this, 'robots_txt'], 100, 2);
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
        $robots['max-image-preview'] = 'large';

        if ($this->is_demo_environment()) {
            unset($robots['index'], $robots['follow']);
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
            $robots['noarchive'] = true;
            return $robots;
        }

        $robots['max-snippet'] = -1;
        $robots['max-video-preview'] = -1;
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
        $image      = $this->social_image();
        $og_type    = is_singular('attorney') ? 'profile' : (is_singular(['practice_area', 'service_area']) ? 'article' : 'website');

        if ($canonical !== '') {
            printf("\n<link rel=\"canonical\" href=\"%s\">\n", esc_url($canonical));
        }
        if ($description !== '') {
            printf("<meta name=\"description\" content=\"%s\">\n", esc_attr($description));
        }
        printf("<meta property=\"og:type\" content=\"%s\">\n", esc_attr($og_type));
        printf("<meta property=\"og:locale\" content=\"%s\">\n", esc_attr(str_replace('-', '_', get_bloginfo('language'))));
        printf("<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr(get_bloginfo('name')));
        printf("<meta property=\"og:title\" content=\"%s\">\n", esc_attr($title));
        if ($canonical !== '') {
            printf("<meta property=\"og:url\" content=\"%s\">\n", esc_url($canonical));
        }
        if ($description !== '') {
            printf("<meta property=\"og:description\" content=\"%s\">\n", esc_attr($description));
        }
        if ($image) {
            printf("<meta property=\"og:image\" content=\"%s\">\n", esc_url($image));
            if (str_starts_with($image, 'https://')) {
                printf("<meta property=\"og:image:secure_url\" content=\"%s\">\n", esc_url($image));
            }
            printf("<meta property=\"og:image:alt\" content=\"%s\">\n", esc_attr($title));
            $dimensions = $this->image_dimensions($image);
            if ($dimensions !== []) {
                printf("<meta property=\"og:image:width\" content=\"%s\">\n", esc_attr((string) $dimensions[0]));
                printf("<meta property=\"og:image:height\" content=\"%s\">\n", esc_attr((string) $dimensions[1]));
            }
        }
        echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
        printf("<meta name=\"twitter:title\" content=\"%s\">\n", esc_attr($title));
        if ($description !== '') {
            printf("<meta name=\"twitter:description\" content=\"%s\">\n", esc_attr($description));
        }
        if ($image) {
            printf("<meta name=\"twitter:image\" content=\"%s\">\n", esc_url($image));
            printf("<meta name=\"twitter:image:alt\" content=\"%s\">\n", esc_attr($title));
        }
        if ($og_type === 'article') {
            printf("<meta property=\"article:published_time\" content=\"%s\">\n", esc_attr((string) get_the_date(DATE_W3C)));
            printf("<meta property=\"article:modified_time\" content=\"%s\">\n", esc_attr((string) get_the_modified_date(DATE_W3C)));
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
        if (! $validated || wp_parse_url($validated, PHP_URL_QUERY) !== null || wp_parse_url($validated, PHP_URL_FRAGMENT) !== null || wp_parse_url($validated, PHP_URL_USER) !== null || wp_parse_url($validated, PHP_URL_PASS) !== null) {
            return false;
        }
        $target_scheme = strtolower((string) wp_parse_url($validated, PHP_URL_SCHEME));
        $home_scheme   = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_SCHEME));
        $target_host = strtolower((string) wp_parse_url($validated, PHP_URL_HOST));
        $home_host   = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        $target_port = wp_parse_url($validated, PHP_URL_PORT);
        $home_port   = wp_parse_url(home_url('/'), PHP_URL_PORT);
        $target_path = '/' . ltrim((string) wp_parse_url($validated, PHP_URL_PATH), '/');
        $home_path   = '/' . trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
        $home_path   = $home_path === '/' ? '/' : trailingslashit($home_path);

        return in_array($target_scheme, ['http', 'https'], true)
            && hash_equals($home_scheme, $target_scheme)
            && $target_host !== ''
            && hash_equals($home_host, $target_host)
            && $target_port === $home_port
            && ($home_path === '/' || str_starts_with(trailingslashit($target_path), $home_path));
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
        ];

        $logo = $this->logo_url();
        if ($logo !== '') {
            $organization['logo'] = ['@type' => 'ImageObject', 'url' => $logo, 'contentUrl' => $logo];
        }
        $graph[] = $organization;

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
            $schema['areaServed'] = ['@type' => 'City', 'name' => field('office_city', $office_id, get_the_title($office_id))];
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
            'telephone' => $this->international_phone((string) field('telephone', $office_id, '(213) 555-0148')),
            'parentOrganization' => ['@id' => home_url('/') . '#organization'],
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
            'openingHoursSpecification' => [[
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'opens' => '08:30',
                'closes' => '18:00',
            ]],
            'image' => $this->social_image(),
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

        if (is_post_type_archive()) {
            return match ((string) get_query_var('post_type')) {
                'practice_area' => 'Explore fictional JusticePoint employment law practice areas, local office connections, related attorneys, and clear consultation paths.',
                'office' => 'Find a fictional JusticePoint Employment Law office, local service pages, accessible contact details, and consultation information.',
                'service_area' => 'Browse curated fictional employment law service pages connecting one practice area with one JusticePoint office market.',
                'attorney' => 'Meet the entirely fictional JusticePoint attorney team and explore their practice-area and office relationships.',
                default => (string) get_bloginfo('description'),
            };
        }
        return (string) get_bloginfo('description');
    }

    private function should_noindex(): bool
    {
        if (is_search() || is_404() || is_feed() || is_embed() || is_attachment() || is_post_type_archive('service_area')) {
            return true;
        }
        if (is_tax(['practice_category', 'city', 'state', 'attorney_specialty'])) {
            return true;
        }
        if (isset($_GET['city']) || isset($_GET['state']) || isset($_GET['practice_area']) || isset($_GET['office']) || isset($_GET['page'])) {
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
        unset($post_types['post'], $post_types['faq'], $post_types['elementor_library']);
        return $post_types;
    }

    /** @param array<string,\WP_Taxonomy> $taxonomies @return array<string,\WP_Taxonomy> */
    public function sitemap_taxonomies(array $taxonomies): array
    {
        unset($taxonomies['category'], $taxonomies['post_tag'], $taxonomies['practice_category'], $taxonomies['city'], $taxonomies['state'], $taxonomies['attorney_specialty']);
        return $taxonomies;
    }

    /** @return \WP_Sitemaps_Provider|false */
    public function sitemap_provider(\WP_Sitemaps_Provider $provider, string $name)
    {
        return $name === 'users' ? false : $provider;
    }

    /** @param array<string,string> $entry @return array<string,string> */
    public function sitemap_entry(array $entry, \WP_Post $post, string $post_type): array
    {
        unset($post_type);
        if (get_post_meta($post->ID, '_jp_exclude_sitemap', true)) {
            return [];
        }
        if (get_post_meta($post->ID, 'indexation', true) === 'noindex') {
            return [];
        }
        $path = (string) wp_parse_url($entry['loc'] ?? '', PHP_URL_PATH);
        if ($path !== '' && (new RedirectRepository())->find($path)) {
            return [];
        }
        return $entry;
    }

    public function robots_txt(string $output, bool $public): string
    {
        unset($output, $public);
        return "User-agent: *\nAllow: /\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nSitemap: " . home_url('/wp-sitemap.xml') . "\n";
    }

    public function redirect_attachment_pages(): void
    {
        if (! is_attachment()) {
            return;
        }
        $target = wp_get_attachment_url((int) get_queried_object_id());
        if ($target) {
            wp_safe_redirect($target, 301, 'JusticePoint attachment canonicalization');
            exit;
        }
    }

    public function disable_unused_archives(): void
    {
        if (! is_author() && ! is_date() && ! is_category() && ! is_tag()) {
            return;
        }
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }

    private function social_image(): string
    {
        if (is_singular() && has_post_thumbnail()) {
            return (string) get_the_post_thumbnail_url(null, 'large');
        }
        $theme_image = get_theme_file_path('/assets/images/justicepoint-hero.webp');
        return is_readable($theme_image) ? get_theme_file_uri('/assets/images/justicepoint-hero.webp') : '';
    }

    private function logo_url(): string
    {
        $site_icon = get_site_icon_url(512);
        if ($site_icon) {
            return $site_icon;
        }
        $theme_logo = get_theme_file_path('/assets/images/justicepoint-site-icon.png');
        return is_readable($theme_logo) ? get_theme_file_uri('/assets/images/justicepoint-site-icon.png') : '';
    }

    private function international_phone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        return strlen($digits) === 10 ? '+1' . $digits : ($digits !== '' && $digits[0] !== '+' ? '+' . $digits : $phone);
    }

    /** @return array{0:int,1:int}|array{} */
    private function image_dimensions(string $url): array
    {
        $upload = wp_upload_dir();
        if (str_starts_with($url, (string) $upload['baseurl'])) {
            $path = str_replace((string) $upload['baseurl'], (string) $upload['basedir'], $url);
        } elseif (str_starts_with($url, get_theme_file_uri('/'))) {
            $path = str_replace(get_theme_file_uri('/'), trailingslashit(get_theme_file_path('/')), $url);
        } else {
            return [];
        }
        $size = is_readable($path) ? wp_getimagesize($path) : false;
        return $size ? [(int) $size[0], (int) $size[1]] : [];
    }
}
