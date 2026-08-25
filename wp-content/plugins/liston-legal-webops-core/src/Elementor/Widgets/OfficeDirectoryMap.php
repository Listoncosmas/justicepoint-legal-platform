<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Liston\LegalWebOps\Elementor\DirectoryRenderer;

final class OfficeDirectoryMap extends Widget_Base
{
    public function get_name(): string { return 'jp-office-directory'; }
    public function get_title(): string { return 'Office Directory and Map'; }
    public function get_icon(): string { return 'eicon-google-maps'; }
    public function get_categories(): array { return ['legal-webops']; }
    public function get_style_depends(): array { return ['jp-elementor-widgets', 'jp-maplibre']; }
    public function get_script_depends(): array { return ['jp-office-directory']; }

    protected function register_controls(): void
    {
        $this->start_controls_section('directory', ['label' => 'Directory']);
        $this->add_control('per_page', ['label' => 'Offices per page', 'type' => Controls_Manager::NUMBER, 'default' => 12, 'min' => 1, 'max' => 50]);
        $this->add_control('intro', ['label' => 'Accessible introduction', 'type' => Controls_Manager::TEXTAREA, 'default' => 'Filter by city, state, or practice area. Every map location is also available in the office list.']);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        echo '<p class="jp-directory-intro">' . esc_html((string) $settings['intro']) . '</p>';
        echo DirectoryRenderer::render(['per_page' => (int) $settings['per_page']]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

