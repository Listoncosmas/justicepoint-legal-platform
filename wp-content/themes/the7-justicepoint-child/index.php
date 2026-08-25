<?php

declare(strict_types=1);
get_header();
?>
<main id="main-content"><header class="jp-page-hero jp-page-hero--compact"><div class="jp-container"><p class="jp-eyebrow">JusticePoint insights</p><h1><?php echo esc_html(is_search() ? 'Search results' : get_bloginfo('name')); ?></h1></div></header><section class="jp-container jp-section"><div class="jp-search-results"><?php if (have_posts()) : while (have_posts()) : the_post(); ?><article><p class="jp-eyebrow"><?php echo esc_html(get_post_type_object(get_post_type())?->labels->singular_name ?? 'Page'); ?></p><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 28)); ?></p></article><?php endwhile; the_posts_pagination(); else : ?><div class="jp-empty"><h2>No results found</h2><p>Try a broader search or browse our practice areas.</p></div><?php endif; ?></div></section></main>
<?php get_footer(); ?>

