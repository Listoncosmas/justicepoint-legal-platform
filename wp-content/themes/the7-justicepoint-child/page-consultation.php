<?php

declare(strict_types=1);
get_header();
the_post();
$practice = isset($_GET['practice_area']) ? absint($_GET['practice_area']) : 0;
$office = isset($_GET['office']) ? absint($_GET['office']) : 0;
?>
<main id="main-content"><header class="jp-page-hero jp-page-hero--form"><div class="jp-container jp-page-hero__grid"><div><?php jp_breadcrumbs(); ?><p class="jp-eyebrow">Confidentiality-minded intake</p><h1>Request a consultation</h1><p class="jp-page-hero__lede">Share the essentials—not confidential documents or sensitive details. Our fictional intake workflow validates, routes, retries, and confirms delivery before recording an analytics event.</p></div><aside class="jp-hero-fact"><p class="jp-eyebrow">What happens next</p><ol><li>Intake reviews the request.</li><li>The right office follows up.</li><li>You decide whether to proceed.</li></ol><span>Submitting does not create an attorney-client relationship.</span></aside></div></header><section class="jp-container jp-section jp-form-layout"><div><p class="jp-eyebrow">Secure request form</p><h2>Start with what you know.</h2><p>Required fields are marked. This demo uses a mock CRM adapter unless a webhook is configured through environment variables.</p></div><div><?php echo do_shortcode(sprintf('[justicepoint_consultation_form practice_area="%d" office="%d"]', $practice, $office)); ?></div></section></main>
<?php get_footer(); ?>

