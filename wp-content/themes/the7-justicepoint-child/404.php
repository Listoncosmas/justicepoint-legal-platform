<?php

declare(strict_types=1);
get_header();
?>
<main id="main-content"><section class="jp-error-page"><div class="jp-container"><p class="jp-error-page__code">404</p><p class="jp-eyebrow">The path ends here</p><h1>We couldn’t find that page.</h1><p>The address may have changed during the fictional migration demonstration. Try a primary destination below.</p><div><a class="jp-button jp-button--primary" href="<?php echo esc_url(home_url('/')); ?>">Return home</a><a class="jp-text-link" href="<?php echo esc_url(get_post_type_archive_link('practice_area')); ?>">Browse practice areas <span aria-hidden="true">→</span></a></div></div></section></main>
<?php get_footer(); ?>

