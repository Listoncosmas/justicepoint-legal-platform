<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use function Liston\LegalWebOps\field;

final class AttorneyGrid extends Widget_Base
{
    public function get_name(): string { return 'jp-attorney-grid'; }
    public function get_title(): string { return 'Attorney Grid'; }
    public function get_icon(): string { return 'eicon-person'; }
    public function get_categories(): array { return ['legal-webops']; }
    public function get_style_depends(): array { return ['jp-elementor-widgets']; }

    protected function register_controls(): void
    {
        $this->start_controls_section('grid', ['label' => 'Automatic query']);
        $this->add_control('count', ['label' => 'Maximum attorneys', 'type' => Controls_Manager::NUMBER, 'default' => 4, 'min' => 1, 'max' => 12]);
        $this->add_control('columns', ['label' => 'Columns', 'type' => Controls_Manager::SELECT, 'default' => '4', 'options' => ['2' => 'Two', '3' => 'Three', '4' => 'Four']]);
        $this->add_control('order', ['label' => 'Order', 'type' => Controls_Manager::SELECT, 'default' => 'ASC', 'options' => ['ASC' => 'A–Z', 'DESC' => 'Z–A']]);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $context_key = '';
        $context_id = 0;
        if (is_singular('office')) {
            $context_key = 'offices';
            $context_id = (int) get_the_ID();
        } elseif (is_singular('practice_area')) {
            $context_key = 'practice_areas';
            $context_id = (int) get_the_ID();
        } elseif (is_singular('service_area')) {
            $assigned = array_map('absint', (array) field('assigned_attorneys', false, []));
            if ($assigned) {
                $this->render_cards($assigned, $settings);
                return;
            }
            $context_key = 'practice_areas';
            $context_id = absint(field('practice_area'));
        }

        $ids = get_posts(['post_type' => 'attorney', 'post_status' => 'publish', 'posts_per_page' => 100, 'fields' => 'ids', 'orderby' => 'title', 'order' => $settings['order'] === 'DESC' ? 'DESC' : 'ASC']);
        if ($context_key && $context_id) {
            update_meta_cache('post', $ids);
            $ids = array_values(array_filter($ids, static fn ($id): bool => in_array($context_id, array_map('absint', (array) field($context_key, (int) $id, [])), true)));
        }
        $this->render_cards(array_slice($ids, 0, min(12, max(1, (int) $settings['count']))), $settings);
    }

    /** @param array<int,int|string> $ids @param array<string,mixed> $settings */
    private function render_cards(array $ids, array $settings): void
    {
        if (! $ids) {
            return;
        }
        echo '<div class="jp-attorney-grid jp-attorney-grid--' . esc_attr((string) $settings['columns']) . '">';
        foreach (array_slice(array_map('absint', $ids), 0, min(12, max(1, (int) $settings['count']))) as $id) {
            $image_id = absint(field('professional_image', $id, get_post_thumbnail_id($id)));
            echo '<article class="jp-attorney-card"><a class="jp-attorney-card__image" href="' . esc_url(get_permalink($id)) . '" tabindex="-1" aria-hidden="true">';
            echo $image_id ? wp_get_attachment_image($image_id, 'medium_large', false, ['loading' => 'lazy', 'alt' => '']) : '<span class="jp-attorney-card__placeholder" aria-hidden="true"></span>';
            echo '</a><p class="jp-eyebrow">' . esc_html((string) field('position', $id, 'Employment Attorney')) . '</p><h3><a href="' . esc_url(get_permalink($id)) . '">' . esc_html((string) field('fictional_name', $id, get_the_title($id))) . '</a></h3><p>' . esc_html(wp_trim_words(wp_strip_all_tags((string) field('biography', $id, get_the_excerpt($id))), 24)) . '</p><a class="jp-text-link" href="' . esc_url(get_permalink($id)) . '">View profile <span aria-hidden="true">→</span></a></article>';
        }
        echo '</div>';
    }
}

