<?php

declare(strict_types=1);
get_header();
$ids = get_posts(['post_type' => 'attorney', 'post_status' => 'publish', 'posts_per_page' => 100, 'fields' => 'ids', 'orderby' => 'menu_order title', 'order' => 'ASC']);
?>
<main id="main-content"><header class="jp-page-hero jp-page-hero--archive"><div class="jp-container"><?php jp_breadcrumbs(); ?><p class="jp-eyebrow">Fictional professional profiles</p><h1>Attorneys</h1><p>A multidisciplinary employment law team designed to demonstrate relationships across practices, offices, and local service pages.</p></div></header><section class="jp-container jp-section"><?php jp_render_attorneys($ids, 'Meet the JusticePoint team'); ?></section><section class="jp-container jp-section"><?php jp_context_cta(); ?></section></main>
<?php get_footer(); ?>

