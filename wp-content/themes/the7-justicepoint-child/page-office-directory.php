<?php

declare(strict_types=1);
get_header();
the_post();
?>
<main id="main-content"><header class="jp-page-hero jp-page-hero--archive"><div class="jp-container"><?php jp_breadcrumbs(); ?><p class="jp-eyebrow">Search by market and capability</p><h1><?php the_title(); ?></h1><p>Find a JusticePoint office by city, state, or practice area. Filters create shareable URLs; every location remains available in the accessible list without JavaScript.</p></div></header><section class="jp-container jp-section"><?php echo do_shortcode('[justicepoint_office_directory per_page="12"]'); ?></section></main>
<?php get_footer(); ?>

