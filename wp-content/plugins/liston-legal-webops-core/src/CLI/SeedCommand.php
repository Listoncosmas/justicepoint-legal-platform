<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\CLI;

final class SeedCommand
{
    /**
     * Generate an idempotent fictional JusticePoint content set.
     *
     * ## OPTIONS
     *
     * [--reset]
     * : Trash previously seeded JusticePoint content before rebuilding it.
     */
    public function __invoke(array $args, array $assoc_args): void
    {
        unset($args);
        if (isset($assoc_args['reset'])) {
            $this->reset();
        }
        if (! post_type_exists('practice_area')) {
            \WP_CLI::error('JusticePoint content types are not registered. Activate the core plugin first.');
        }

        update_option('blogname', 'JusticePoint Employment Law');
        update_option('blogdescription', 'Clear employment law guidance for people and workplaces. Fictional demonstration.');
        update_option('blog_public', 0);
        update_option('permalink_structure', '/%postname%/');
        update_option('timezone_string', 'America/Los_Angeles');
        update_option('default_comment_status', 'closed');

        if (wp_get_theme('the7-justicepoint-child')->exists()) {
            switch_theme('the7-justicepoint-child');
        }

        $practice_data = $this->practice_data();
        $practice_ids = [];
        foreach ($practice_data as $slug => $data) {
            $id = $this->upsert_post('practice_area', 'practice:' . $slug, $data['title'], $data['content'], $data['excerpt'], $slug, $data['order']);
            $practice_ids[$slug] = $id;
            $this->set_meta($id, [
                'short_description'  => $data['excerpt'],
                'detailed_intro'     => $data['intro'],
                'common_claim_types' => implode("\n", $data['claims']),
                'eligibility'        => $data['eligibility'],
                'process_steps'      => implode("\n", $data['steps']),
                'primary_cta'        => $data['cta'],
                'seo_title'          => $data['title'] . ' Lawyers | JusticePoint',
                'meta_description'   => $data['meta'],
                'indexation'         => 'index',
            ]);
            $term = term_exists($slug, 'practice_category');
            if (! $term) {
                $term = wp_insert_term($data['title'], 'practice_category', ['slug' => $slug]);
            }
            if (! is_wp_error($term)) {
                wp_set_object_terms($id, [(int) (is_array($term) ? $term['term_id'] : $term)], 'practice_category');
            }
        }

        $office_ids = [];
        foreach ($this->office_data() as $slug => $data) {
            $id = $this->upsert_post('office', 'office:' . $slug, $data['title'], $data['content'], $data['excerpt'], $slug, $data['order']);
            $office_ids[$slug] = $id;
            $this->set_meta($id, [
                'address' => $data['address'], 'office_city' => $data['city'], 'office_state' => 'CA', 'zip_code' => $data['zip'],
                'latitude' => $data['latitude'], 'longitude' => $data['longitude'], 'telephone' => $data['telephone'],
                'office_hours' => "Monday–Friday\n8:30 a.m.–6:00 p.m.", 'consultation_url' => home_url('/consultation/?office=' . $id),
                'served_practice_areas' => array_values($practice_ids), 'map_information' => $data['access'],
                'local_business_name' => 'JusticePoint Employment Law — ' . $data['city'],
                'seo_title' => $data['city'] . ' Employment Law Office | JusticePoint', 'meta_description' => $data['meta'], 'indexation' => 'index',
            ]);
            foreach (['city' => [$data['city'], $slug], 'state' => ['California', 'california']] as $taxonomy => [$name, $term_slug]) {
                $term = term_exists($term_slug, $taxonomy) ?: wp_insert_term($name, $taxonomy, ['slug' => $term_slug]);
                if (! is_wp_error($term)) {
                    wp_set_object_terms($id, [(int) (is_array($term) ? $term['term_id'] : $term)], $taxonomy);
                }
            }
            wp_set_object_terms($id, array_keys($practice_ids), 'practice_category');
        }

        $image_ids = [];
        foreach (['maya-chen', 'andre-bennett', 'elena-ramirez', 'jonah-reed'] as $name) {
            $image_ids[$name] = $this->import_image('justicepoint-' . $name . '.webp', ucwords(str_replace('-', ' ', $name)) . ', fictional JusticePoint employment attorney');
        }

        $attorney_ids = [];
        foreach ($this->attorney_data() as $slug => $data) {
            $id = $this->upsert_post('attorney', 'attorney:' . $slug, $data['name'], $data['bio'], $data['excerpt'], $slug, $data['order']);
            $attorney_ids[$slug] = $id;
            $practices = array_values(array_intersect_key($practice_ids, array_flip($data['practices'])));
            $offices = array_values(array_intersect_key($office_ids, array_flip($data['offices'])));
            $this->set_meta($id, [
                'fictional_name' => $data['name'], 'position' => $data['position'], 'biography' => $data['bio'],
                'practice_areas' => $practices, 'offices' => $offices, 'education' => implode("\n", $data['education']),
                'admissions' => implode("\n", $data['admissions']), 'professional_image' => $image_ids[$slug] ?? 0,
                'contact_email' => $slug . '@justicepoint.example', 'contact_phone' => '(213) 555-0148',
                'seo_title' => $data['name'] . ' | Fictional Employment Attorney', 'meta_description' => $data['excerpt'], 'indexation' => 'index',
            ]);
            if (! empty($image_ids[$slug])) {
                set_post_thumbnail($id, $image_ids[$slug]);
            }
            wp_set_object_terms($id, $data['specialties'], 'attorney_specialty');
        }

        $faq_ids = [];
        foreach ($this->faq_data() as $slug => $data) {
            $id = $this->upsert_post('faq', 'faq:' . $slug, $data['question'], $data['answer'], '', $slug, 0);
            $faq_ids[$slug] = $id;
            $this->set_meta($id, ['answer' => $data['answer'], 'related_practices' => array_values($practice_ids)]);
        }

        foreach ($practice_ids as $slug => $id) {
            $index = array_search($slug, array_keys($practice_ids), true);
            $assigned = array_values(array_filter($attorney_ids, static fn ($attorney_id, $attorney_slug): bool => in_array($slug, self::attorney_practices($attorney_slug), true), ARRAY_FILTER_USE_BOTH));
            update_post_meta($id, 'related_attorneys', array_slice($assigned, 0, 3));
            update_post_meta($id, 'related_faqs', array_values(array_slice($faq_ids, (int) $index % 4, 3, true)));
        }

        $service_count = 0;
        $service_ids = [];
        $service_practices = array_slice($practice_ids, 0, 5, true);
        foreach ($office_ids as $office_slug => $office_id) {
            $office = $this->office_data()[$office_slug];
            foreach ($service_practices as $practice_slug => $practice_id) {
                $practice = $practice_data[$practice_slug];
                $title = $practice['title'] . ' in ' . $office['city'];
                $slug = $practice_slug . '-' . $office_slug;
                $local_intro = sprintf('<p>Workplace concerns in %1$s can move quickly from an internal conversation to a consequential legal decision. JusticePoint’s fictional %1$s team connects local access with a platform-wide %2$s practice.</p>', esc_html($office['city']), esc_html($practice['title']));
                $considerations = sprintf('<p>California rules may interact with employer size, work location, agreements, internal policies, and filing deadlines. In %1$s, the first useful step is to preserve relevant records and clarify which decision or event needs attention now.</p><p>This sample content is intentionally general. A production page would be reviewed by licensed counsel for accuracy, jurisdiction, and substantiation before publication.</p>', esc_html($office['city']));
                $id = $this->upsert_post('service_area', 'service:' . $slug, $title, $local_intro . $considerations, wp_strip_all_tags($local_intro), $slug, ++$service_count);
                $service_ids[$slug] = $id;
                $assigned = array_values(array_filter($attorney_ids, static fn ($attorney_id, $attorney_slug): bool => in_array($practice_slug, self::attorney_practices($attorney_slug), true), ARRAY_FILTER_USE_BOTH));
                $this->set_meta($id, [
                    'practice_area' => $practice_id, 'office' => $office_id, 'unique_local_intro' => $local_intro,
                    'local_legal_considerations' => $considerations, 'nearby_areas' => implode("\n", $office['nearby']),
                    'related_faqs' => array_values(array_slice($faq_ids, $service_count % 4, 3, true)),
                    'assigned_attorneys' => array_slice($assigned ?: array_values($attorney_ids), 0, 3),
                    'crm_campaign_id' => 'JP-' . strtoupper(substr($office_slug, 0, 3)) . '-' . strtoupper(substr($practice_slug, 0, 4)),
                    'seo_title' => $title . ' Lawyers | JusticePoint',
                    'meta_description' => sprintf('Fictional local guidance for %s matters in %s, California. Meet the JusticePoint office and request a consultation.', strtolower($practice['title']), $office['city']),
                    'indexation' => 'index',
                ]);
                wp_set_object_terms($id, [$office_slug], 'city');
                wp_set_object_terms($id, ['california'], 'state');
                wp_set_object_terms($id, [$practice_slug], 'practice_category');
            }
        }

        $pages = [
            'home' => ['title' => 'JusticePoint Employment Law', 'excerpt' => 'Clarity when work gets complicated.'],
            'office-directory' => ['title' => 'Office Directory & Map', 'excerpt' => 'Search JusticePoint offices by market and practice area.'],
            'consultation' => ['title' => 'Request a Consultation', 'excerpt' => 'Start a focused, confidentiality-minded intake conversation.'],
            'contact' => ['title' => 'Contact', 'excerpt' => 'Choose the clearest way to reach JusticePoint.'],
            'privacy' => ['title' => 'Privacy', 'excerpt' => 'How this fictional demonstration handles intake and measurement data.'],
        ];
        $page_ids = [];
        foreach ($pages as $slug => $data) {
            $page_ids[$slug] = $this->upsert_post('page', 'page:' . $slug, $data['title'], '', $data['excerpt'], $slug, 0);
        }
        update_option('show_on_front', 'page');
        update_option('page_on_front', $page_ids['home']);

        $this->seed_menu($page_ids);
        $this->seed_elementor_template();
        flush_rewrite_rules(false);
        \WP_CLI::success(sprintf('Seeded %d practices, %d offices, %d service areas, %d attorneys, %d FAQs, and %d pages.', count($practice_ids), count($office_ids), count($service_ids), count($attorney_ids), count($faq_ids), count($page_ids)));
    }

    private function reset(): void
    {
        $ids = get_posts(['post_type' => ['practice_area', 'office', 'service_area', 'attorney', 'faq', 'page', 'elementor_library'], 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_jp_seeded', 'meta_value' => '1']);
        foreach ($ids as $id) {
            wp_trash_post((int) $id);
        }
        \WP_CLI::log(sprintf('Trashed %d previously seeded records.', count($ids)));
    }

    private function upsert_post(string $type, string $key, string $title, string $content, string $excerpt, string $slug, int $order): int
    {
        $existing = get_posts(['post_type' => $type, 'post_status' => ['publish', 'draft', 'trash'], 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_jp_seed_key', 'meta_value' => $key]);
        $data = ['post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_content' => $content, 'post_excerpt' => $excerpt, 'post_name' => $slug, 'menu_order' => $order, 'comment_status' => 'closed'];
        if ($existing) {
            $data['ID'] = (int) $existing[0];
            $id = wp_update_post($data, true);
        } else {
            $id = wp_insert_post($data, true);
        }
        if (is_wp_error($id)) {
            \WP_CLI::error($id->get_error_message());
        }
        update_post_meta((int) $id, '_jp_seeded', 1);
        update_post_meta((int) $id, '_jp_seed_key', $key);
        return (int) $id;
    }

    /** @param array<string,mixed> $values */
    private function set_meta(int $post_id, array $values): void
    {
        foreach ($values as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
    }

    private function import_image(string $filename, string $alt): int
    {
        $existing = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_jp_asset_source', 'meta_value' => $filename]);
        if ($existing) {
            return (int) $existing[0];
        }
        $path = get_theme_file_path('/assets/images/' . $filename);
        if (! is_readable($path)) {
            \WP_CLI::warning('Image not found: ' . $path);
            return 0;
        }
        $upload = wp_upload_bits($filename, null, file_get_contents($path));
        if (! empty($upload['error'])) {
            \WP_CLI::warning((string) $upload['error']);
            return 0;
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment = wp_insert_attachment(['post_mime_type' => 'image/webp', 'post_title' => pathinfo($filename, PATHINFO_FILENAME), 'post_status' => 'inherit'], $upload['file']);
        if (is_wp_error($attachment)) {
            return 0;
        }
        wp_update_attachment_metadata($attachment, wp_generate_attachment_metadata($attachment, $upload['file']));
        update_post_meta($attachment, '_wp_attachment_image_alt', $alt);
        update_post_meta($attachment, '_jp_asset_source', $filename);
        return (int) $attachment;
    }

    /** @param array<string,int> $pages */
    private function seed_menu(array $pages): void
    {
        $menu = wp_get_nav_menu_object('JusticePoint Primary');
        $menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu('JusticePoint Primary');
        foreach (wp_get_nav_menu_items($menu_id) ?: [] as $item) {
            wp_delete_post($item->ID, true);
        }
        $items = [
            ['Practice Areas', get_post_type_archive_link('practice_area')],
            ['Offices', get_post_type_archive_link('office')],
            ['Attorneys', get_post_type_archive_link('attorney')],
            ['Directory', get_permalink($pages['office-directory'])],
            ['Contact', get_permalink($pages['contact'])],
        ];
        foreach ($items as [$title, $url]) {
            wp_update_nav_menu_item($menu_id, 0, ['menu-item-title' => $title, 'menu-item-url' => $url, 'menu-item-status' => 'publish', 'menu-item-type' => 'custom']);
        }
        $locations = get_theme_mod('nav_menu_locations', []);
        $locations['primary'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }

    private function seed_elementor_template(): void
    {
        if (! post_type_exists('elementor_library')) {
            return;
        }
        $data = [[
            'id' => 'jpservice01', 'elType' => 'container', 'isInner' => false,
            'settings' => ['content_width' => 'full', 'html_tag' => 'div'],
            'elements' => [[
                'id' => 'jpshortcode1', 'elType' => 'widget', 'widgetType' => 'shortcode',
                'settings' => ['shortcode' => '[justicepoint_dynamic_service_area]'], 'elements' => [],
            ]],
        ]];
        $id = $this->upsert_post('elementor_library', 'elementor:service-area-template', 'JusticePoint — Dynamic Service Area', '', '', 'justicepoint-dynamic-service-area', 0);
        $this->set_meta($id, [
            '_elementor_edit_mode' => 'builder', '_elementor_template_type' => 'single-post', '_elementor_data' => wp_slash(wp_json_encode($data)),
            '_elementor_page_settings' => [], '_elementor_conditions' => ['include/singular/service_area'], '_wp_page_template' => 'elementor_header_footer',
        ]);
    }

    /** @return array<string,array<string,mixed>> */
    private function practice_data(): array
    {
        $steps = ['Focused intake and issue framing', 'Document and timeline review', 'Options, risk, and priority setting', 'Negotiation, response, or next filing step'];
        return [
            'wrongful-termination' => ['title' => 'Wrongful Termination', 'order' => 1, 'excerpt' => 'Assess the reason, timing, documentation, and protected activity behind a job loss.', 'content' => '', 'intro' => '<p>A termination can be unfair without being unlawful. A careful review focuses on the stated reason, what happened before the decision, how comparable situations were handled, and whether protected activity or status played a role.</p>', 'claims' => ['Retaliatory discharge', 'Discrimination-based termination', 'Public-policy violations', 'Contract and policy issues'], 'eligibility' => '<p>People often seek review when the explanation shifts, timing follows a complaint or leave, policies were applied unevenly, or relevant records contradict the stated reason.</p>', 'steps' => $steps, 'cta' => 'Bring the timeline, the stated reason, and the records you already have.', 'meta' => 'Fictional wrongful termination guidance, local service pages, related attorneys, and a clear consultation path.'],
            'workplace-discrimination' => ['title' => 'Workplace Discrimination', 'order' => 2, 'excerpt' => 'Identify patterns, comparators, decision-makers, and workplace impacts tied to a protected characteristic.', 'content' => '', 'intro' => '<p>Discrimination analysis depends on facts and context: who made the decision, what explanations were offered, how others were treated, and whether comments or patterns connect the outcome to a protected characteristic.</p>', 'claims' => ['Hiring and promotion decisions', 'Pay and assignment disparities', 'Disability and accommodation issues', 'Pregnancy and family-status concerns'], 'eligibility' => '<p>A closer review may be useful when similarly situated people receive different treatment, an accommodation process stalls, or a pattern of comments and decisions points in the same direction.</p>', 'steps' => $steps, 'cta' => 'Start with the decision, the people involved, and the comparison that concerns you.', 'meta' => 'Fictional workplace discrimination information with structured local pages and attorney relationships.'],
            'wage-hour' => ['title' => 'Wage & Hour Claims', 'order' => 3, 'excerpt' => 'Review pay practices, time records, classification, breaks, expenses, and final wages.', 'content' => '', 'intro' => '<p>Wage and hour questions often hide inside routine systems. The useful evidence may be a schedule, payroll record, timekeeping rule, classification decision, or repeated off-the-clock expectation.</p>', 'claims' => ['Unpaid overtime', 'Missed meal or rest periods', 'Worker misclassification', 'Expense and final-pay issues'], 'eligibility' => '<p>Patterns matter. Review can begin with a sample of time records, pay statements, written policies, and the real expectations of the role.</p>', 'steps' => $steps, 'cta' => 'A small, representative record set is often enough to frame the first conversation.', 'meta' => 'Fictional wage and hour guidance for employees and employers across Southern California.'],
            'retaliation' => ['title' => 'Workplace Retaliation', 'order' => 4, 'excerpt' => 'Connect protected activity, decision timing, changing treatment, and the employer’s stated rationale.', 'content' => '', 'intro' => '<p>Retaliation questions are built around sequence and causation. The analysis asks what protected activity occurred, who knew, what changed, when it changed, and how the employer explains the decision.</p>', 'claims' => ['Complaints about unlawful conduct', 'Leave- or accommodation-related activity', 'Wage complaints', 'Participation in investigations'], 'eligibility' => '<p>Warning signs can include sudden scrutiny, shifting goals, exclusion, discipline, or termination after a complaint, request, report, or investigation.</p>', 'steps' => $steps, 'cta' => 'A clean before-and-after timeline makes the first review more useful.', 'meta' => 'Fictional workplace retaliation guidance with local context and a structured intake path.'],
            'workplace-harassment' => ['title' => 'Workplace Harassment', 'order' => 5, 'excerpt' => 'Evaluate conduct, frequency, context, reporting, response, and impact on the workplace.', 'content' => '', 'intro' => '<p>Not every difficult interaction is unlawful harassment. A useful review considers what occurred, how often, whether it relates to a protected characteristic, who was told, and how the organization responded.</p>', 'claims' => ['Sexual harassment', 'Hostile work environment', 'Supervisor misconduct', 'Inadequate response to reports'], 'eligibility' => '<p>Contemporaneous notes, communications, witness names, policy documents, and reporting history can help separate the events from later assumptions.</p>', 'steps' => $steps, 'cta' => 'Share the pattern and reporting history without sending sensitive documents first.', 'meta' => 'Fictional workplace harassment information designed for accessible, structured WordPress publishing.'],
            'employer-counsel' => ['title' => 'Employer Counsel', 'order' => 6, 'excerpt' => 'Practical advice for policies, investigations, accommodations, pay practices, and workplace decisions.', 'content' => '', 'intro' => '<p>Strong employer counsel begins before a dispute hardens. The work connects compliant systems with the real decisions managers need to make under time pressure.</p>', 'claims' => ['Policy and handbook review', 'Workplace investigations', 'Accommodation processes', 'Pre-termination risk review'], 'eligibility' => '<p>Organizations may seek counsel when a decision carries heightened risk, an internal process has stalled, or a recurring system needs a defensible redesign.</p>', 'steps' => $steps, 'cta' => 'Bring the decision point, business constraint, and current process.', 'meta' => 'Fictional employer-side employment counsel for proactive workplace decisions.'],
        ];
    }

    /** @return array<string,array<string,mixed>> */
    private function office_data(): array
    {
        return [
            'los-angeles' => ['title' => 'Los Angeles Office', 'city' => 'Los Angeles', 'order' => 1, 'address' => '1800 Grand Avenue, Suite 620', 'zip' => '90017', 'latitude' => '34.05231', 'longitude' => '-118.25118', 'telephone' => '(213) 555-0148', 'access' => 'Near regional rail and paid public parking; appointments are required.', 'nearby' => ['Downtown Los Angeles', 'Echo Park', 'Silver Lake', 'Koreatown', 'Culver City'], 'excerpt' => 'A central Los Angeles office for employees and employers across the metro area.', 'content' => '<p>Our fictional Los Angeles office connects a central intake team with attorneys working across employee and employer matters. The office is designed for scheduled, accessible consultations and efficient document review.</p>', 'meta' => 'Visit the fictional JusticePoint Los Angeles employment law office and explore local practice pages.'],
            'pasadena' => ['title' => 'Pasadena Office', 'city' => 'Pasadena', 'order' => 2, 'address' => '680 Arroyo Parkway, Suite 240', 'zip' => '91105', 'latitude' => '34.13970', 'longitude' => '-118.14710', 'telephone' => '(626) 555-0186', 'access' => 'Two blocks from light rail with accessible visitor parking.', 'nearby' => ['South Pasadena', 'San Marino', 'Altadena', 'Arcadia', 'Alhambra'], 'excerpt' => 'Employment counsel for Pasadena and the western San Gabriel Valley.', 'content' => '<p>The fictional Pasadena team serves individuals, growing companies, and established organizations across the western San Gabriel Valley, with a practical focus on early issue framing.</p>', 'meta' => 'Fictional Pasadena employment law office information, local services, and consultation details.'],
            'glendale' => ['title' => 'Glendale Office', 'city' => 'Glendale', 'order' => 3, 'address' => '410 North Brand Boulevard, Suite 420', 'zip' => '91203', 'latitude' => '34.15242', 'longitude' => '-118.25440', 'telephone' => '(818) 555-0129', 'access' => 'Central Brand Boulevard location with nearby bus service and garage parking.', 'nearby' => ['Burbank', 'Atwater Village', 'Eagle Rock', 'La Cañada Flintridge', 'North Hollywood'], 'excerpt' => 'A connected employment law team serving Glendale and nearby business corridors.', 'content' => '<p>JusticePoint’s fictional Glendale office supports workplace matters across a diverse regional economy, pairing market-specific access with shared firmwide resources.</p>', 'meta' => 'Explore the fictional JusticePoint Glendale office, attorneys, and curated employment law pages.'],
            'santa-monica' => ['title' => 'Santa Monica Office', 'city' => 'Santa Monica', 'order' => 4, 'address' => '1320 Ocean Park Boulevard, Suite 210', 'zip' => '90405', 'latitude' => '34.01102', 'longitude' => '-118.46950', 'telephone' => '(310) 555-0174', 'access' => 'Accessible ground-floor lobby, nearby transit, and reserved appointment parking.', 'nearby' => ['Venice', 'Mar Vista', 'West Los Angeles', 'Pacific Palisades', 'Brentwood'], 'excerpt' => 'Employment law guidance for Santa Monica and the Westside.', 'content' => '<p>The fictional Santa Monica office provides a Westside consultation point for individuals, founders, managers, and organizations navigating employment decisions.</p>', 'meta' => 'Fictional Santa Monica employment law office information and local service-area guidance.'],
        ];
    }

    /** @return array<string,array<string,mixed>> */
    private function attorney_data(): array
    {
        return [
            'maya-chen' => ['name' => 'Maya Chen', 'position' => 'Managing Attorney', 'order' => 1, 'excerpt' => 'A calm strategist focused on discrimination, retaliation, and complex workplace decisions.', 'bio' => '<p>Maya Chen is a completely fictional attorney created for this demonstration. Her sample practice emphasizes careful fact development, plain-language advice, and coordinated strategy in discrimination and retaliation matters.</p><p>She works with individuals and leadership teams to identify the decision that matters now, while keeping the longer arc of the matter in view.</p>', 'practices' => ['workplace-discrimination', 'retaliation', 'wrongful-termination'], 'offices' => ['los-angeles', 'pasadena'], 'education' => ['J.D., Pacific Crest School of Law (fictional)', 'B.A., Westbridge University (fictional)'], 'admissions' => ['California (fictional profile)'], 'specialties' => ['Discrimination', 'Retaliation']],
            'andre-bennett' => ['name' => 'Andre Bennett', 'position' => 'Partner, Wage & Hour', 'order' => 2, 'excerpt' => 'A systems-minded advocate for wage, classification, and workplace process disputes.', 'bio' => '<p>Andre Bennett is a completely fictional attorney. His sample profile centers on wage systems, worker classification, timekeeping practices, and the operational records that often decide a case.</p><p>His approach combines rigorous detail with an accessible explanation of options and tradeoffs.</p>', 'practices' => ['wage-hour', 'wrongful-termination', 'employer-counsel'], 'offices' => ['los-angeles', 'glendale'], 'education' => ['J.D., Harbor State College of Law (fictional)', 'B.S., Ridgeline University (fictional)'], 'admissions' => ['California (fictional profile)', 'U.S. District Court, sample listing'], 'specialties' => ['Wage & Hour', 'Classification']],
            'elena-ramirez' => ['name' => 'Elena Ramirez', 'position' => 'Senior Counsel', 'order' => 3, 'excerpt' => 'An empathetic investigator and adviser on harassment, accommodations, and workplace response.', 'bio' => '<p>Elena Ramirez is a completely fictional attorney. Her demonstration profile focuses on harassment response, accommodations, investigations, and the human details that determine whether a process earns trust.</p><p>She translates complex narratives into organized facts, open questions, and responsible next steps.</p>', 'practices' => ['workplace-harassment', 'workplace-discrimination', 'retaliation'], 'offices' => ['pasadena', 'santa-monica'], 'education' => ['J.D., Arroyo School of Law (fictional)', 'B.A., Mission Valley College (fictional)'], 'admissions' => ['California (fictional profile)'], 'specialties' => ['Harassment', 'Investigations']],
            'jonah-reed' => ['name' => 'Jonah Reed', 'position' => 'Employer Advisory Counsel', 'order' => 4, 'excerpt' => 'Practical counsel for policies, investigations, accommodations, and high-stakes decisions.', 'bio' => '<p>Jonah Reed is a completely fictional attorney whose sample practice helps organizations connect legal requirements with day-to-day management decisions.</p><p>He emphasizes early risk identification, well-scoped investigations, and policies that people can actually use.</p>', 'practices' => ['employer-counsel', 'wage-hour', 'workplace-harassment'], 'offices' => ['glendale', 'santa-monica'], 'education' => ['J.D., California Meridian Law School (fictional)', 'B.A., North Coast University (fictional)'], 'admissions' => ['California (fictional profile)'], 'specialties' => ['Employer Advice', 'Investigations']],
        ];
    }

    /** @return array<string,array{question:string,answer:string}> */
    private function faq_data(): array
    {
        return [
            'what-to-bring' => ['question' => 'What should I bring to an initial consultation?', 'answer' => '<p>A short timeline, the key communications, relevant policies or agreements, and the outcome you are trying to understand are usually enough to begin. Do not send confidential documents before the firm confirms how to share them.</p>'],
            'deadlines' => ['question' => 'Do employment claims have deadlines?', 'answer' => '<p>Yes. Different claims, forums, contracts, and agency processes can use different deadlines. A prompt, jurisdiction-specific review is important; this demonstration does not calculate or provide a legal deadline.</p>'],
            'employer-clients' => ['question' => 'Does JusticePoint advise employers as well as individuals?', 'answer' => '<p>In this fictional model, yes. Separate intake and conflict procedures would route employee and employer matters to appropriate teams before confidential information is accepted.</p>'],
            'fees' => ['question' => 'How are legal fees handled?', 'answer' => '<p>Fee arrangements depend on the matter, scope, and client. A real firm would explain the applicable structure and provide a written agreement before representation begins. No fee promises are made in this demo.</p>'],
            'remote' => ['question' => 'Can a consultation happen remotely?', 'answer' => '<p>Yes. The fictional offices offer scheduled video and telephone consultations, subject to identity, conflicts, jurisdiction, and matter-fit checks.</p>'],
            'confidentiality' => ['question' => 'Is the website form confidential?', 'answer' => '<p>No attorney-client relationship is created by submitting a form. Avoid highly sensitive information until a real firm confirms representation and a secure sharing method.</p>'],
            'investigation' => ['question' => 'What makes a workplace investigation credible?', 'answer' => '<p>A well-scoped process uses a neutral investigator, preserves relevant evidence, gives people a meaningful opportunity to respond, documents findings, and connects follow-up actions to policy and facts.</p>'],
            'service-area' => ['question' => 'Why does the site have local service pages?', 'answer' => '<p>Curated local pages help people find the right office and explain genuine market context. This platform prevents duplicate practice-and-office combinations and requires unique local content before publication.</p>'],
        ];
    }

    /** @return array<int,string> */
    private static function attorney_practices(string $slug): array
    {
        $map = [
            'maya-chen' => ['workplace-discrimination', 'retaliation', 'wrongful-termination'],
            'andre-bennett' => ['wage-hour', 'wrongful-termination', 'employer-counsel'],
            'elena-ramirez' => ['workplace-harassment', 'workplace-discrimination', 'retaliation'],
            'jonah-reed' => ['employer-counsel', 'wage-hour', 'workplace-harassment'],
        ];
        return $map[$slug] ?? [];
    }
}

