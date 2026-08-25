<?php

declare(strict_types=1);
get_header();
the_post();
?>
<main id="main-content">
    <?php
    $template_ids = get_posts([
        'post_type'      => 'elementor_library',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_jp_seed_key',
        'meta_value'     => 'elementor:service-area-template',
    ]);
    $template_id = (int) ($template_ids[0] ?? 0);

    if ($template_id && class_exists('\\Elementor\\Plugin')) {
        echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($template_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor sanitizes its rendered template output.
    } else {
        jp_render_service_area_content();
    }
    ?>
</main>
<?php get_footer(); ?>
