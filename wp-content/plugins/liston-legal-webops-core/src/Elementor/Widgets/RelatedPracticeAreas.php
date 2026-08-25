<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use function Liston\LegalWebOps\field;

final class RelatedPracticeAreas extends Widget_Base
{
    public function get_name(): string { return 'jp-related-practices'; }
    public function get_title(): string { return 'Related Practice Areas'; }
    public function get_icon(): string { return 'eicon-posts-grid'; }
    public function get_categories(): array { return ['legal-webops']; }
    public function get_style_depends(): array { return ['jp-elementor-widgets']; }

    protected function register_controls(): void
    {
        $this->start_controls_section('query', ['label' => 'Query']);
        $this->add_control('count', ['label' => 'Count', 'type' => Controls_Manager::NUMBER, 'default' => 3, 'min' => 1, 'max' => 8]);
        $this->add_control('orderby', ['label' => 'Order by', 'type' => Controls_Manager::SELECT, 'default' => 'menu_order', 'options' => ['menu_order' => 'Editorial order', 'title' => 'Title', 'date' => 'Date']]);
        $this->add_control('order', ['label' => 'Order', 'type' => Controls_Manager::SELECT, 'default' => 'ASC', 'options' => ['ASC' => 'Ascending', 'DESC' => 'Descending']]);
        $this->add_control('layout', ['label' => 'Layout', 'type' => Controls_Manager::SELECT, 'default' => 'grid', 'options' => ['grid' => 'Grid', 'list' => 'List']]);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $current = is_singular('practice_area') ? (int) get_the_ID() : (is_singular('service_area') ? absint(field('practice_area')) : 0);
        $terms = $current ? wp_get_post_terms($current, 'practice_category', ['fields' => 'ids']) : [];
        $args = [
            'post_type' => 'practice_area',
            'post_status' => 'publish',
            'posts_per_page' => min(8, max(1, (int) $settings['count'])),
            'post__not_in' => $current ? [$current] : [],
            'orderby' => in_array($settings['orderby'], ['menu_order', 'title', 'date'], true) ? $settings['orderby'] : 'menu_order',
            'order' => $settings['order'] === 'DESC' ? 'DESC' : 'ASC',
            'no_found_rows' => true,
        ];
        if (! is_wp_error($terms) && $terms) {
            $args['tax_query'] = [['taxonomy' => 'practice_category', 'field' => 'term_id', 'terms' => $terms]];
        }
        $query = new \WP_Query($args);
        if (! $query->have_posts()) {
            return;
        }
        echo '<div class="jp-related-practices jp-related-practices--' . esc_attr((string) $settings['layout']) . '">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<article><p class="jp-eyebrow">Employment law</p><h3><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3><p>' . esc_html((string) field('short_description', false, get_the_excerpt())) . '</p><a class="jp-text-link" href="' . esc_url(get_permalink()) . '">Explore this practice <span aria-hidden="true">→</span></a></article>';
        }
        wp_reset_postdata();
        echo '</div>';
    }
}

