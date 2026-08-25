<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Content;

final class ContentTypes
{
    /** @var array<string,array<string,mixed>> */
    private array $types = [
        'practice_area' => [
            'singular' => 'Practice Area',
            'plural'   => 'Practice Areas',
            'menu'     => 'dashicons-portfolio',
            'slug'     => 'employment-law',
            'archive'  => 'practice-areas',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        ],
        'office' => [
            'singular' => 'Office',
            'plural'   => 'Offices',
            'menu'     => 'dashicons-location-alt',
            'slug'     => 'locations',
            'archive'  => 'offices',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        ],
        'service_area' => [
            'singular' => 'Service Area',
            'plural'   => 'Service Areas',
            'menu'     => 'dashicons-admin-site-alt3',
            'slug'     => 'local-employment-law',
            'archive'  => 'service-areas',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        ],
        'attorney' => [
            'singular' => 'Attorney',
            'plural'   => 'Attorneys',
            'menu'     => 'dashicons-businessperson',
            'slug'     => 'attorneys',
            'archive'  => 'attorneys',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        ],
        'faq' => [
            'singular' => 'FAQ',
            'plural'   => 'FAQs',
            'menu'     => 'dashicons-editor-help',
            'slug'     => 'legal-questions',
            'archive'  => false,
            'supports' => ['title', 'editor', 'revisions'],
        ],
    ];

    public function register(): void
    {
        add_action('init', [$this, 'register_types'], 5);
        add_action('init', [$this, 'register_meta'], 7);
        add_filter('enter_title_here', [$this, 'title_placeholder'], 10, 2);
    }

    public function register_types(): void
    {
        foreach ($this->types as $name => $config) {
            $labels = [
                'name'                  => $config['plural'],
                'singular_name'         => $config['singular'],
                'add_new_item'          => sprintf('Add New %s', $config['singular']),
                'edit_item'             => sprintf('Edit %s', $config['singular']),
                'new_item'              => sprintf('New %s', $config['singular']),
                'view_item'             => sprintf('View %s', $config['singular']),
                'view_items'            => sprintf('View %s', $config['plural']),
                'search_items'          => sprintf('Search %s', $config['plural']),
                'not_found'             => sprintf('No %s found', strtolower((string) $config['plural'])),
                'not_found_in_trash'    => sprintf('No %s found in Trash', strtolower((string) $config['plural'])),
                'all_items'             => sprintf('All %s', $config['plural']),
                'archives'              => sprintf('%s archive', $config['singular']),
                'attributes'            => sprintf('%s attributes', $config['singular']),
                'item_published'        => sprintf('%s published.', $config['singular']),
                'item_updated'          => sprintf('%s updated.', $config['singular']),
                'item_reverted_to_draft'=> sprintf('%s reverted to draft.', $config['singular']),
            ];

            register_post_type(
                $name,
                [
                    'labels'             => $labels,
                    'public'             => true,
                    'show_ui'            => true,
                    'show_in_rest'       => true,
                    'rest_base'          => str_replace('_', '-', $name) . 's',
                    'has_archive'        => $config['archive'],
                    'rewrite'            => ['slug' => $config['slug'], 'with_front' => false],
                    'menu_icon'          => $config['menu'],
                    'supports'           => $config['supports'],
                    'capability_type'    => [$name, $name . 's'],
                    'map_meta_cap'       => true,
                    'delete_with_user'   => false,
                    'menu_position'      => 21,
                    'show_in_nav_menus'  => $name !== 'faq',
                    'publicly_queryable' => true,
                    'query_var'          => true,
                ]
            );
        }

        $this->register_taxonomies();
    }

    private function register_taxonomies(): void
    {
        $taxonomies = [
            'practice_category' => [
                'singular' => 'Practice Category',
                'plural'   => 'Practice Categories',
                'types'    => ['practice_area', 'service_area', 'office'],
                'slug'     => 'practice-category',
                'hierarchical' => true,
            ],
            'city' => [
                'singular' => 'City',
                'plural'   => 'Cities',
                'types'    => ['office', 'service_area'],
                'slug'     => 'city',
                'hierarchical' => false,
            ],
            'state' => [
                'singular' => 'State',
                'plural'   => 'States',
                'types'    => ['office', 'service_area'],
                'slug'     => 'state',
                'hierarchical' => false,
            ],
            'attorney_specialty' => [
                'singular' => 'Attorney Specialty',
                'plural'   => 'Attorney Specialties',
                'types'    => ['attorney'],
                'slug'     => 'attorney-specialty',
                'hierarchical' => false,
            ],
        ];

        foreach ($taxonomies as $name => $config) {
            register_taxonomy(
                $name,
                $config['types'],
                [
                    'labels' => [
                        'name'          => $config['plural'],
                        'singular_name' => $config['singular'],
                        'search_items'  => sprintf('Search %s', $config['plural']),
                        'all_items'     => sprintf('All %s', $config['plural']),
                        'edit_item'     => sprintf('Edit %s', $config['singular']),
                        'add_new_item'  => sprintf('Add New %s', $config['singular']),
                    ],
                    'public'            => true,
                    'show_ui'           => true,
                    'show_admin_column' => true,
                    'show_in_rest'      => true,
                    'hierarchical'      => $config['hierarchical'],
                    'rewrite'           => ['slug' => $config['slug'], 'with_front' => false],
                    'capabilities'      => [
                        'manage_terms' => 'manage_categories',
                        'edit_terms'   => 'manage_categories',
                        'delete_terms' => 'manage_categories',
                        'assign_terms' => 'edit_posts',
                    ],
                ]
            );
        }
    }

    public function register_meta(): void
    {
        foreach (Fields::definitions() as $post_type => $fields) {
            foreach ($fields as $key => $field) {
                $rest_type = $field['rest_type'] ?? 'string';
                $show_in_rest = true;

                if ('array' === $rest_type) {
                    $show_in_rest = [
                        'schema' => [
                            'type'  => 'array',
                            'items' => ['type' => 'integer'],
                        ],
                    ];
                }

                register_post_meta(
                    $post_type,
                    $key,
                    [
                        'single'            => true,
                        'type'              => $rest_type,
                        'show_in_rest'      => $show_in_rest,
                        'sanitize_callback' => [Fields::class, 'sanitize_registered_meta'],
                        'auth_callback'     => static fn (): bool => current_user_can('edit_posts'),
                    ]
                );
            }
        }
    }

    public function title_placeholder(string $title, \WP_Post $post): string
    {
        return match ($post->post_type) {
            'service_area' => 'e.g. Wrongful Termination in Los Angeles',
            'office'       => 'e.g. Los Angeles Office',
            'attorney'     => 'Fictional attorney name',
            'faq'          => 'Enter a genuine, visible question',
            default        => $title,
        };
    }
}
