<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Content;

final class Fields
{
    /** @var array<string,array<string,mixed>> */
    private static array $definitions = [
        'practice_area' => [
            'short_description'  => ['label' => 'Short description', 'type' => 'textarea'],
            'detailed_intro'     => ['label' => 'Detailed introduction', 'type' => 'wysiwyg'],
            'common_claim_types' => ['label' => 'Common claim types (one per line)', 'type' => 'textarea'],
            'eligibility'        => ['label' => 'Eligibility information', 'type' => 'wysiwyg'],
            'process_steps'      => ['label' => 'Process steps (one per line)', 'type' => 'textarea'],
            'related_faqs'       => ['label' => 'Related FAQs', 'type' => 'relationship', 'post_type' => 'faq', 'rest_type' => 'array'],
            'related_attorneys'  => ['label' => 'Related attorneys', 'type' => 'relationship', 'post_type' => 'attorney', 'rest_type' => 'array'],
            'primary_cta'        => ['label' => 'Primary call to action', 'type' => 'text'],
            'seo_title'          => ['label' => 'SEO title override', 'type' => 'text'],
            'meta_description'   => ['label' => 'Meta description', 'type' => 'textarea'],
            'canonical_override' => ['label' => 'Canonical override', 'type' => 'url'],
            'indexation'         => ['label' => 'Indexation', 'type' => 'select', 'choices' => ['index' => 'Index', 'noindex' => 'Noindex']],
        ],
        'office' => [
            'address'               => ['label' => 'Street address', 'type' => 'text'],
            'office_city'           => ['label' => 'City', 'type' => 'text'],
            'office_state'          => ['label' => 'State', 'type' => 'text'],
            'zip_code'              => ['label' => 'ZIP code', 'type' => 'text'],
            'latitude'              => ['label' => 'Latitude', 'type' => 'number'],
            'longitude'             => ['label' => 'Longitude', 'type' => 'number'],
            'telephone'             => ['label' => 'Telephone number', 'type' => 'tel'],
            'office_hours'          => ['label' => 'Office hours', 'type' => 'textarea'],
            'consultation_url'      => ['label' => 'Consultation URL', 'type' => 'url'],
            'served_practice_areas' => ['label' => 'Served practice areas', 'type' => 'relationship', 'post_type' => 'practice_area', 'rest_type' => 'array'],
            'map_information'       => ['label' => 'Transit and access information', 'type' => 'textarea'],
            'local_business_name'   => ['label' => 'LocalBusiness name', 'type' => 'text'],
            'seo_title'             => ['label' => 'SEO title override', 'type' => 'text'],
            'meta_description'      => ['label' => 'Meta description', 'type' => 'textarea'],
            'canonical_override'    => ['label' => 'Canonical override', 'type' => 'url'],
            'indexation'            => ['label' => 'Indexation', 'type' => 'select', 'choices' => ['index' => 'Index', 'noindex' => 'Noindex']],
        ],
        'service_area' => [
            'practice_area'             => ['label' => 'Practice area', 'type' => 'post_object', 'post_type' => 'practice_area', 'rest_type' => 'integer'],
            'office'                    => ['label' => 'Office / market', 'type' => 'post_object', 'post_type' => 'office', 'rest_type' => 'integer'],
            'unique_local_intro'        => ['label' => 'Unique local introduction', 'type' => 'wysiwyg'],
            'local_legal_considerations'=> ['label' => 'Local legal considerations', 'type' => 'wysiwyg'],
            'nearby_areas'              => ['label' => 'Nearby areas served (one per line)', 'type' => 'textarea'],
            'related_faqs'              => ['label' => 'Related FAQs', 'type' => 'relationship', 'post_type' => 'faq', 'rest_type' => 'array'],
            'assigned_attorneys'        => ['label' => 'Assigned attorneys', 'type' => 'relationship', 'post_type' => 'attorney', 'rest_type' => 'array'],
            'crm_campaign_id'           => ['label' => 'CRM campaign ID', 'type' => 'text'],
            'seo_title'                 => ['label' => 'SEO title override', 'type' => 'text'],
            'meta_description'          => ['label' => 'Meta description', 'type' => 'textarea'],
            'canonical_override'        => ['label' => 'Canonical override', 'type' => 'url'],
            'indexation'                => ['label' => 'Indexation', 'type' => 'select', 'choices' => ['index' => 'Index', 'noindex' => 'Noindex']],
        ],
        'attorney' => [
            'fictional_name'     => ['label' => 'Fictional name', 'type' => 'text'],
            'position'            => ['label' => 'Position', 'type' => 'text'],
            'biography'           => ['label' => 'Biography', 'type' => 'wysiwyg'],
            'practice_areas'      => ['label' => 'Practice areas', 'type' => 'relationship', 'post_type' => 'practice_area', 'rest_type' => 'array'],
            'offices'             => ['label' => 'Offices', 'type' => 'relationship', 'post_type' => 'office', 'rest_type' => 'array'],
            'education'           => ['label' => 'Education (one per line)', 'type' => 'textarea'],
            'admissions'          => ['label' => 'Admissions (one per line)', 'type' => 'textarea'],
            'professional_image' => ['label' => 'Professional image attachment ID', 'type' => 'number', 'rest_type' => 'integer'],
            'contact_email'       => ['label' => 'Contact email', 'type' => 'email'],
            'contact_phone'       => ['label' => 'Contact telephone', 'type' => 'tel'],
            'seo_title'           => ['label' => 'SEO title override', 'type' => 'text'],
            'meta_description'    => ['label' => 'Meta description', 'type' => 'textarea'],
            'canonical_override'  => ['label' => 'Canonical override', 'type' => 'url'],
            'indexation'          => ['label' => 'Indexation', 'type' => 'select', 'choices' => ['index' => 'Index', 'noindex' => 'Noindex']],
        ],
        'faq' => [
            'answer'             => ['label' => 'Answer', 'type' => 'wysiwyg'],
            'related_practices'  => ['label' => 'Related practice areas', 'type' => 'relationship', 'post_type' => 'practice_area', 'rest_type' => 'array'],
            'related_offices'    => ['label' => 'Related offices', 'type' => 'relationship', 'post_type' => 'office', 'rest_type' => 'array'],
        ],
    ];

    public function register(): void
    {
        add_action('acf/init', [$this, 'register_acf_groups']);
        add_filter('acf/settings/save_json', [$this, 'acf_json_path']);
        add_filter('acf/settings/load_json', [$this, 'acf_json_load_paths']);
        add_action('add_meta_boxes', [$this, 'register_native_meta_boxes']);
        add_action('save_post', [$this, 'save_native_fields'], 20, 2);
        add_action('admin_notices', [$this, 'acf_notice']);
    }

    /** @return array<string,array<string,mixed>> */
    public static function definitions(): array
    {
        return self::$definitions;
    }

    public function acf_json_path(): string
    {
        return JP_WEBOPS_PATH . 'acf-json';
    }

    /** @param array<int,string> $paths @return array<int,string> */
    public function acf_json_load_paths(array $paths): array
    {
        $paths[] = JP_WEBOPS_PATH . 'acf-json';
        return array_values(array_unique($paths));
    }

    public function register_acf_groups(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        foreach (self::$definitions as $post_type => $fields) {
            $acf_fields = [];
            foreach ($fields as $name => $field) {
                $acf = [
                    'key'   => 'field_jp_' . $post_type . '_' . $name,
                    'label' => $field['label'],
                    'name'  => $name,
                    'type'  => $field['type'],
                ];
                if (isset($field['post_type'])) {
                    $acf['post_type'] = [$field['post_type']];
                    $acf['return_format'] = 'id';
                    $acf['filters'] = ['search', 'post_type'];
                }
                if (isset($field['choices'])) {
                    $acf['choices'] = $field['choices'];
                    $acf['default_value'] = 'index';
                }
                if ($field['type'] === 'wysiwyg') {
                    $acf['tabs'] = 'visual';
                    $acf['toolbar'] = 'basic';
                    $acf['media_upload'] = 0;
                }
                $acf_fields[] = $acf;
            }

            acf_add_local_field_group(
                [
                    'key'      => 'group_jp_' . $post_type,
                    'title'    => 'JusticePoint ' . get_post_type_object($post_type)?->labels->singular_name . ' Details',
                    'fields'   => $acf_fields,
                    'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => $post_type]]],
                    'position' => 'normal',
                    'style'    => 'seamless',
                    'active'   => true,
                    'show_in_rest' => 1,
                ]
            );
        }
    }

    public function register_native_meta_boxes(): void
    {
        if (function_exists('acf_add_local_field_group')) {
            return;
        }

        foreach (array_keys(self::$definitions) as $post_type) {
            add_meta_box(
                'jp_' . $post_type . '_details',
                'JusticePoint structured details',
                [$this, 'render_native_meta_box'],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function render_native_meta_box(\WP_Post $post): void
    {
        wp_nonce_field('jp_save_structured_fields', 'jp_fields_nonce');
        echo '<div class="jp-admin-fields"><p><em>ACF Pro is not installed, so the secure native-field fallback is active. The same meta keys are used when ACF Pro is enabled.</em></p>';

        foreach (self::$definitions[$post->post_type] as $name => $field) {
            $value = get_post_meta($post->ID, $name, true);
            echo '<p><label for="jp-' . esc_attr($name) . '"><strong>' . esc_html($field['label']) . '</strong></label><br>';

            if (in_array($field['type'], ['relationship', 'post_object'], true)) {
                $selected = array_map('intval', (array) $value);
                $multiple = $field['type'] === 'relationship';
                $posts = get_posts([
                    'post_type'      => $field['post_type'],
                    'post_status'    => ['publish', 'draft'],
                    'posts_per_page' => 100,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                ]);
                printf('<select id="jp-%1$s" name="jp_fields[%1$s]%2$s"%3$s>', esc_attr($name), $multiple ? '[]' : '', $multiple ? ' multiple size="5"' : '');
                echo '<option value="">Select…</option>';
                foreach ($posts as $related) {
                    printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr((string) $related->ID), selected(in_array($related->ID, $selected, true), true, false), esc_html($related->post_title));
                }
                echo '</select>';
            } elseif ($field['type'] === 'select') {
                printf('<select id="jp-%1$s" name="jp_fields[%1$s]">', esc_attr($name));
                foreach ($field['choices'] as $choice => $label) {
                    printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($choice), selected($value ?: 'index', $choice, false), esc_html($label));
                }
                echo '</select>';
            } elseif (in_array($field['type'], ['textarea', 'wysiwyg'], true)) {
                printf('<textarea class="widefat" rows="%1$d" id="jp-%2$s" name="jp_fields[%2$s]">%3$s</textarea>', $field['type'] === 'wysiwyg' ? 7 : 4, esc_attr($name), esc_textarea((string) $value));
            } else {
                printf('<input class="widefat" type="%1$s" id="jp-%2$s" name="jp_fields[%2$s]" value="%3$s">', esc_attr(in_array($field['type'], ['url', 'email', 'number', 'tel'], true) ? $field['type'] : 'text'), esc_attr($name), esc_attr((string) $value));
            }
            echo '</p>';
        }
        echo '</div>';
    }

    public function save_native_fields(int $post_id, \WP_Post $post): void
    {
        if (function_exists('acf_add_local_field_group') || ! isset(self::$definitions[$post->post_type])) {
            return;
        }
        if (wp_is_post_revision($post_id) || ! isset($_POST['jp_fields_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jp_fields_nonce'])), 'jp_save_structured_fields')) {
            return;
        }
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $submitted = isset($_POST['jp_fields']) && is_array($_POST['jp_fields']) ? wp_unslash($_POST['jp_fields']) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each value is sanitized against its registered field type below.
        foreach (self::$definitions[$post->post_type] as $name => $field) {
            if (! array_key_exists($name, $submitted)) {
                if ($field['type'] === 'relationship') {
                    delete_post_meta($post_id, $name);
                }
                continue;
            }
            $value = self::sanitize_by_type($submitted[$name], $field['type']);
            update_post_meta($post_id, $name, $value);
        }
    }

    public static function sanitize_registered_meta(mixed $value, string $meta_key = ''): mixed
    {
        foreach (self::$definitions as $fields) {
            if (isset($fields[$meta_key])) {
                return self::sanitize_by_type($value, $fields[$meta_key]['type']);
            }
        }
        return sanitize_text_field((string) $value);
    }

    private static function sanitize_by_type(mixed $value, string $type): mixed
    {
        return match ($type) {
            'relationship' => array_values(array_filter(array_map('absint', (array) $value))),
            'post_object'  => absint($value),
            'email'        => sanitize_email((string) $value),
            'url'          => esc_url_raw((string) $value, ['http', 'https']),
            'number'       => is_numeric($value) ? (string) (float) $value : '',
            'textarea'     => sanitize_textarea_field((string) $value),
            'wysiwyg'      => wp_kses_post((string) $value),
            default        => sanitize_text_field((string) $value),
        };
    }

    public function acf_notice(): void
    {
        if (function_exists('acf_add_local_field_group') || ! current_user_can('activate_plugins')) {
            return;
        }
        $screen = get_current_screen();
        if (! $screen || ! in_array($screen->post_type, array_keys(self::$definitions), true)) {
            return;
        }
        echo '<div class="notice notice-info"><p><strong>JusticePoint:</strong> ACF Pro is not installed. Version-controlled field definitions remain active through the native fallback; install the licensed dependency to use the ACF editing UI.</p></div>';
    }
}
