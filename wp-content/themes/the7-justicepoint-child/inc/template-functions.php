<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

function jp_field(string $name, int|false $post_id = false, mixed $default = ''): mixed
{
    if (function_exists('Liston\\LegalWebOps\\field')) {
        return \Liston\LegalWebOps\field($name, $post_id, $default);
    }
    $post_id = $post_id ?: (int) get_the_ID();
    $value = get_post_meta($post_id, $name, true);
    return ($value === '' || $value === null || $value === false) ? $default : $value;
}

function jp_breadcrumbs(): void
{
    if (class_exists('Liston\\LegalWebOps\\SEO\\Breadcrumbs')) {
        echo \Liston\LegalWebOps\SEO\Breadcrumbs::render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

/** @return array<int,string> */
function jp_lines(string $value): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value) ?: [])));
}

/** @param array<int,int|string> $ids */
function jp_render_faqs(array $ids): void
{
    $ids = array_values(array_filter(array_map('absint', $ids)));
    if (! $ids) {
        return;
    }
    ?>
    <section class="jp-section jp-faq-section" aria-labelledby="jp-faq-title">
        <div class="jp-section-heading jp-section-heading--split"><div><p class="jp-eyebrow">Straight answers</p><h2 id="jp-faq-title">Questions people ask first</h2></div><p>General information for this fictional demonstration—not legal advice for a specific situation.</p></div>
        <div class="jp-faq-list">
            <?php foreach ($ids as $index => $id) : $answer = (string) jp_field('answer', $id, get_post_field('post_content', $id)); if ($answer === '') { continue; } ?>
                <details class="jp-faq" <?php echo $index === 0 ? 'open' : ''; ?>><summary><span><?php echo esc_html(get_the_title($id)); ?></span><span aria-hidden="true">+</span></summary><div class="jp-faq__answer"><?php echo wp_kses_post(wpautop($answer)); ?></div></details>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

/** @param array<int,int|string> $ids */
function jp_render_attorneys(array $ids = [], string $title = 'A team built around the work'): void
{
    $ids = array_values(array_filter(array_map('absint', $ids)));
    if (! $ids) {
        $ids = get_posts(['post_type' => 'attorney', 'post_status' => 'publish', 'posts_per_page' => 4, 'fields' => 'ids', 'orderby' => 'menu_order title', 'order' => 'ASC']);
    }
    if (! $ids) {
        return;
    }
    ?>
    <section class="jp-section" aria-labelledby="jp-team-title">
        <div class="jp-section-heading jp-section-heading--split"><div><p class="jp-eyebrow">Fictional professionals</p><h2 id="jp-team-title"><?php echo esc_html($title); ?></h2></div><a class="jp-text-link" href="<?php echo esc_url(get_post_type_archive_link('attorney')); ?>">Meet the whole team <span aria-hidden="true">→</span></a></div>
        <div class="jp-attorney-grid jp-attorney-grid--4">
            <?php foreach (array_slice($ids, 0, 4) as $id) : $image_id = absint(jp_field('professional_image', $id, get_post_thumbnail_id($id))); ?>
                <article class="jp-attorney-card"><a class="jp-attorney-card__image" href="<?php echo esc_url(get_permalink($id)); ?>" tabindex="-1" aria-hidden="true"><?php echo $image_id ? wp_get_attachment_image($image_id, 'jp-attorney-card', false, ['loading' => 'lazy', 'alt' => '', 'width' => 720, 'height' => 900]) : ''; ?></a><p class="jp-eyebrow"><?php echo esc_html((string) jp_field('position', $id, 'Employment Attorney')); ?></p><h3><a href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html((string) jp_field('fictional_name', $id, get_the_title($id))); ?></a></h3><p><?php echo esc_html(wp_trim_words(wp_strip_all_tags((string) jp_field('biography', $id, get_the_excerpt($id))), 22)); ?></p><a class="jp-text-link" href="<?php echo esc_url(get_permalink($id)); ?>">View profile <span aria-hidden="true">→</span></a></article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function jp_render_practices(int $limit = 6): void
{
    $practices = get_posts(['post_type' => 'practice_area', 'post_status' => 'publish', 'posts_per_page' => $limit, 'orderby' => 'menu_order title', 'order' => 'ASC']);
    if (! $practices) {
        return;
    }
    ?>
    <div class="jp-practice-list">
        <?php foreach ($practices as $index => $practice) : ?>
            <article><span class="jp-practice-list__number" aria-hidden="true"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span><div><h3><a href="<?php echo esc_url(get_permalink($practice)); ?>"><?php echo esc_html($practice->post_title); ?></a></h3><p><?php echo esc_html((string) jp_field('short_description', $practice->ID, $practice->post_excerpt)); ?></p></div><a class="jp-practice-list__arrow" href="<?php echo esc_url(get_permalink($practice)); ?>" aria-label="Learn about <?php echo esc_attr($practice->post_title); ?>"><span aria-hidden="true">↗</span></a></article>
        <?php endforeach; ?>
    </div>
    <?php
}

function jp_context_cta(int $office_id = 0, int $practice_id = 0): void
{
    if (! $office_id) {
        $office_id = (int) (get_posts(['post_type' => 'office', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'orderby' => 'menu_order title', 'order' => 'ASC'])[0] ?? 0);
    }
    $phone = (string) jp_field('telephone', $office_id, '(213) 555-0148');
    $url = (string) jp_field('consultation_url', $office_id, home_url('/consultation/'));
    $label = $practice_id ? get_the_title($practice_id) : 'your employment matter';
    ?>
    <aside class="jp-context-cta jp-context-cta--accent" aria-labelledby="jp-context-heading">
        <div><p class="jp-eyebrow">A clear next step</p><h2 id="jp-context-heading">Start with a focused conversation.</h2><p>Tell us a little about <?php echo esc_html($label); ?>. Our fictional intake team will route your request to the right office.</p></div>
        <div class="jp-context-cta__actions"><a class="jp-button jp-button--light" href="<?php echo esc_url($url); ?>">Request a consultation</a><a class="jp-context-cta__phone" href="tel:<?php echo esc_attr((string) preg_replace('/[^+\d]/', '', $phone)); ?>"><span>Prefer to call?</span><?php echo esc_html($phone); ?></a></div>
    </aside>
    <?php
}

function jp_render_service_area_content(): void
{
    get_template_part('template-parts/content', 'service-area');
}

