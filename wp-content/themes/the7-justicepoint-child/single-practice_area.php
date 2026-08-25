<?php

declare(strict_types=1);
get_header();
the_post();
$id = get_the_ID();
$claims = jp_lines((string) jp_field('common_claim_types', $id));
$steps = jp_lines((string) jp_field('process_steps', $id));
$faqs = (array) jp_field('related_faqs', $id, []);
$attorneys = (array) jp_field('related_attorneys', $id, []);
$services = get_posts(['post_type' => 'service_area', 'post_status' => 'publish', 'posts_per_page' => 20, 'orderby' => 'title', 'order' => 'ASC', 'meta_key' => 'practice_area', 'meta_value' => $id]);
?>
<main id="main-content"><article>
    <header class="jp-page-hero"><div class="jp-container jp-page-hero__grid"><div><?php jp_breadcrumbs(); ?><p class="jp-eyebrow">Practice area</p><h1><?php the_title(); ?></h1><p class="jp-page-hero__lede"><?php echo esc_html((string) jp_field('short_description', $id, get_the_excerpt())); ?></p><div class="jp-page-hero__actions"><a class="jp-button jp-button--primary" href="<?php echo esc_url(home_url('/consultation/?practice_area=' . $id)); ?>">Discuss your situation</a><a class="jp-text-link" href="#what-to-know">What to know <span aria-hidden="true">↓</span></a></div></div><aside class="jp-hero-fact"><p class="jp-eyebrow">Where we begin</p><p><?php echo esc_html((string) jp_field('primary_cta', $id, 'A focused intake conversation helps identify the best next step.')); ?></p><span>Fictional demonstration content</span></aside></div></header>
    <section class="jp-container jp-section jp-content-grid" id="what-to-know"><div class="jp-prose"><p class="jp-eyebrow">The essentials</p><h2>Understand the issue before choosing the move.</h2><?php echo wp_kses_post(wpautop((string) jp_field('detailed_intro', $id, get_the_content()))); ?><h3>Who may need to take a closer look</h3><?php echo wp_kses_post(wpautop((string) jp_field('eligibility', $id))); ?></div><?php if ($claims) : ?><aside class="jp-aside-list"><p class="jp-eyebrow">Common claim types</p><ul><?php foreach ($claims as $claim) : ?><li><?php echo esc_html($claim); ?></li><?php endforeach; ?></ul><p class="jp-small">A label is only a starting point. Facts, timing, and applicable law determine the analysis.</p></aside><?php endif; ?></section>
    <?php if ($steps) : ?><section class="jp-section jp-surface"><div class="jp-container"><div class="jp-section-heading jp-section-heading--split"><div><p class="jp-eyebrow">A practical route</p><h2>What the process can look like.</h2></div><p>Every matter is different. This sample workflow shows how structured content turns a complex service into a scan-friendly page.</p></div><ol class="jp-step-grid"><?php foreach ($steps as $index => $step) : ?><li><span><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span><h3><?php echo esc_html($step); ?></h3></li><?php endforeach; ?></ol></div></section><?php endif; ?>
    <?php if ($services) : ?><section class="jp-container jp-section"><div class="jp-section-heading jp-section-heading--split"><div><p class="jp-eyebrow">Local guidance</p><h2><?php the_title(); ?> by office.</h2></div><p>Shared service data combines with unique local details—without rebuilding the layout.</p></div><div class="jp-link-grid"><?php foreach ($services as $service) : $office = absint(jp_field('office', $service->ID)); ?><a href="<?php echo esc_url(get_permalink($service)); ?>"><span><?php echo esc_html((string) jp_field('office_city', $office, get_the_title($office))); ?></span><strong><?php echo esc_html($service->post_title); ?></strong><span aria-hidden="true">→</span></a><?php endforeach; ?></div></section><?php endif; ?>
    <div class="jp-container"><?php jp_render_attorneys($attorneys, 'People who know this work'); jp_render_faqs($faqs); ?><section class="jp-section"><?php jp_context_cta(0, $id); ?></section></div>
</article></main>
<?php get_footer(); ?>

