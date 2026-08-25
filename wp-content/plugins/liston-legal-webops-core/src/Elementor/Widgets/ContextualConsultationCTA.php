<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use function Liston\LegalWebOps\field;

final class ContextualConsultationCTA extends Widget_Base
{
    public function get_name(): string { return 'jp-contextual-consultation'; }
    public function get_title(): string { return 'Contextual Consultation CTA'; }
    public function get_icon(): string { return 'eicon-call-to-action'; }
    public function get_categories(): array { return ['legal-webops']; }
    public function get_style_depends(): array { return ['jp-elementor-widgets']; }

    protected function register_controls(): void
    {
        $this->start_controls_section('content', ['label' => 'Context']);
        $this->add_control('eyebrow', ['label' => 'Eyebrow', 'type' => Controls_Manager::TEXT, 'default' => 'A clear next step']);
        $this->add_control('heading', ['label' => 'Heading', 'type' => Controls_Manager::TEXT, 'default' => 'Talk with an employment law team']);
        $this->add_control('variant', ['label' => 'Visual variant', 'type' => Controls_Manager::SELECT, 'default' => 'light', 'options' => ['light' => 'Light', 'accent' => 'Burgundy accent', 'compact' => 'Compact']]);
        $this->add_control('button_label', ['label' => 'Button label', 'type' => Controls_Manager::TEXT, 'default' => 'Request a consultation']);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $office_id = is_singular('office') ? (int) get_the_ID() : (is_singular('service_area') ? absint(field('office')) : 0);
        $practice_id = is_singular('practice_area') ? (int) get_the_ID() : (is_singular('service_area') ? absint(field('practice_area')) : 0);
        if (! $office_id) {
            $found_offices = get_posts(['post_type' => 'office', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'orderby' => 'menu_order title', 'order' => 'ASC']);
            $office_id = (int) ($found_offices[0] ?? 0);
        }
        $telephone = (string) field('telephone', $office_id, '(213) 555-0148');
        $consultation = (string) field('consultation_url', $office_id, home_url('/consultation/'));
        $context = $practice_id ? get_the_title($practice_id) : ($office_id ? get_the_title($office_id) : 'Employment law');
        ?>
        <aside class="jp-context-cta jp-context-cta--<?php echo esc_attr((string) $settings['variant']); ?>" aria-labelledby="jp-context-cta-<?php echo esc_attr((string) $this->get_id()); ?>">
            <div><p class="jp-eyebrow"><?php echo esc_html((string) $settings['eyebrow']); ?></p><h2 id="jp-context-cta-<?php echo esc_attr((string) $this->get_id()); ?>"><?php echo esc_html((string) $settings['heading']); ?></h2><p>Connect with the team serving <?php echo esc_html($context); ?> matters. No confidential details are needed to start.</p></div>
            <div class="jp-context-cta__actions"><a class="jp-button jp-button--primary" href="<?php echo esc_url($consultation); ?>"><?php echo esc_html((string) $settings['button_label']); ?></a><a class="jp-context-cta__phone" href="tel:<?php echo esc_attr((string) preg_replace('/[^+\d]/', '', $telephone)); ?>"><span>Call this office</span><?php echo esc_html($telephone); ?></a></div>
        </aside>
        <?php
    }
}
