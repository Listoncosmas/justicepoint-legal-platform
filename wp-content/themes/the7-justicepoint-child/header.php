<?php

declare(strict_types=1);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="jp-skip-link" href="#main-content">Skip to main content</a>
<aside class="jp-demo-bar" aria-label="Demonstration notice"><div class="jp-container"><span>Fictional demo — not legal advice</span><span aria-hidden="true">•</span><span>Not a law firm</span></div></aside>
<header class="jp-header" data-jp-header>
    <div class="jp-container jp-header__inner">
        <a class="jp-logo" href="<?php echo esc_url(home_url('/')); ?>">
            <svg width="37" height="37" viewBox="0 0 37 37" aria-hidden="true" focusable="false"><path d="M4 4h29v29H4z" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M11 9v12.2c0 4.7 2.8 7.1 7.3 7.1 4.7 0 7.7-2.7 7.7-8V9h-5v11.4c0 2.2-.9 3.3-2.7 3.3-1.6 0-2.4-.9-2.4-3V9z" fill="currentColor"/><circle cx="26" cy="26" r="2.5" fill="#8f1d35"/></svg>
            <span><strong>JusticePoint</strong><small>Employment Law</small></span>
        </a>
        <button class="jp-menu-toggle" type="button" aria-expanded="false" aria-controls="jp-primary-menu" data-jp-menu-toggle><span></span><span></span><span></span><span class="screen-reader-text">Toggle navigation</span></button>
        <nav class="jp-nav" id="jp-primary-menu" aria-label="Primary navigation" data-jp-navigation>
            <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'menu_class' => 'jp-nav__list', 'fallback_cb' => 'jp_nav_fallback', 'depth' => 2]); ?>
        </nav>
        <div class="jp-header__actions"><a class="jp-header__phone" href="tel:+12135550148"><span>Call</span>(213) 555-0148</a><a class="jp-button jp-button--primary jp-button--header" href="<?php echo esc_url(home_url('/consultation/')); ?>">Request consultation</a></div>
    </div>
</header>
<?php
function jp_nav_fallback(): void
{
    echo '<ul class="jp-nav__list"><li><a href="' . esc_url(get_post_type_archive_link('practice_area')) . '">Practice Areas</a></li><li><a href="' . esc_url(get_post_type_archive_link('office')) . '">Offices</a></li><li><a href="' . esc_url(get_post_type_archive_link('attorney')) . '">Attorneys</a></li><li><a href="' . esc_url(home_url('/contact/')) . '">Contact</a></li></ul>';
}
