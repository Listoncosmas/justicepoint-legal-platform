<?php

declare(strict_types=1);
get_header();
the_post();
?>
<main id="main-content"><header class="jp-page-hero jp-page-hero--compact"><div class="jp-container"><?php jp_breadcrumbs(); ?><p class="jp-eyebrow">JusticePoint</p><h1><?php the_title(); ?></h1><?php if (has_excerpt()) : ?><p><?php the_excerpt(); ?></p><?php endif; ?></div></header><article class="jp-container jp-section jp-prose jp-prose--page"><?php the_content(); ?></article></main>
<?php get_footer(); ?>

