<?php

declare(strict_types=1);
$footer_offices = get_posts(['post_type' => 'office', 'post_status' => 'publish', 'posts_per_page' => 4, 'orderby' => 'menu_order title', 'order' => 'ASC']);
?>
<footer class="jp-footer">
    <div class="jp-container jp-footer__top">
        <div class="jp-footer__brand"><a class="jp-logo jp-logo--footer" href="<?php echo esc_url(home_url('/')); ?>"><svg width="38" height="38" viewBox="0 0 37 37" aria-hidden="true"><path d="M4 4h29v29H4z" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M11 9v12.2c0 4.7 2.8 7.1 7.3 7.1 4.7 0 7.7-2.7 7.7-8V9h-5v11.4c0 2.2-.9 3.3-2.7 3.3-1.6 0-2.4-.9-2.4-3V9z" fill="currentColor"/><circle cx="26" cy="26" r="2.5" fill="#d96a7d"/></svg><span><strong>JusticePoint</strong><small>Employment Law</small></span></a><p>A fictional multi-location legal platform demonstrating scalable WordPress engineering and WebOps craftsmanship.</p></div>
        <div><h2>Explore</h2><ul><li><a href="<?php echo esc_url(get_post_type_archive_link('practice_area')); ?>">Practice areas</a></li><li><a href="<?php echo esc_url(get_post_type_archive_link('attorney')); ?>">Attorneys</a></li><li><a href="<?php echo esc_url(home_url('/office-directory/')); ?>">Office directory</a></li><li><a href="<?php echo esc_url(home_url('/consultation/')); ?>">Consultation</a></li></ul></div>
        <div><h2>Offices</h2><ul><?php foreach ($footer_offices as $office) : ?><li><a href="<?php echo esc_url(get_permalink($office)); ?>"><?php echo esc_html((string) jp_field('office_city', $office->ID, $office->post_title)); ?>, <?php echo esc_html((string) jp_field('office_state', $office->ID, 'CA')); ?></a></li><?php endforeach; ?></ul></div>
        <div><h2>Contact</h2><p><a href="tel:+12135550148">(213) 555-0148</a><br><a href="mailto:hello@justicepoint.example">hello@justicepoint.example</a></p><p>Monday–Friday<br>8:30 a.m.–6:00 p.m.</p></div>
    </div>
    <div class="jp-container jp-footer__bottom"><p>© <?php echo esc_html(wp_date('Y')); ?> JusticePoint Employment Law. Fictional demonstration.</p><nav aria-label="Legal"><a href="<?php echo esc_url(home_url('/privacy/')); ?>">Privacy</a><a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a></nav></div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

