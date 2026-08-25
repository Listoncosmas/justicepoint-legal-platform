<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Integrations;

final class ConsultationController
{
    public function __construct(private ?WebhookClient $client = null)
    {
        $this->client ??= new WebhookClient();
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_shortcode('justicepoint_consultation_form', [$this, 'shortcode']);
    }

    public function routes(): void
    {
        register_rest_route(
            'liston-webops/v1',
            '/consultations',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'submit'],
                'permission_callback' => '__return_true',
                'args'                => $this->arguments(),
            ]
        );
    }

    public function register_assets(): void
    {
        $asset = JP_WEBOPS_PATH . 'assets/js/consultation.js';
        wp_register_script(
            'jp-consultation',
            JP_WEBOPS_URL . 'assets/js/consultation.js',
            [],
            is_file($asset) ? (string) filemtime($asset) : JP_WEBOPS_VERSION,
            ['in_footer' => true, 'strategy' => 'defer']
        );
    }

    public function shortcode(array $attributes = []): string
    {
        $attributes = shortcode_atts(['practice_area' => '', 'office' => ''], $attributes, 'justicepoint_consultation_form');
        wp_enqueue_script('jp-consultation');
        $practices = get_posts(['post_type' => 'practice_area', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC']);
        $offices = get_posts(['post_type' => 'office', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC']);
        $nonce = wp_create_nonce('jp_consultation');

        ob_start();
        ?>
        <form class="jp-intake" data-jp-consultation-form data-endpoint="<?php echo esc_url(rest_url('liston-webops/v1/consultations')); ?>" novalidate>
            <div class="jp-intake__status" data-form-status role="status" aria-live="polite"></div>
            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="hidden" name="landing_page" data-capture="landing_page" value="">
            <input type="hidden" name="referrer" data-capture="referrer" value="">
            <?php foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'msclkid', 'fbclid'] as $parameter) : ?>
                <input type="hidden" name="<?php echo esc_attr($parameter); ?>" data-capture="<?php echo esc_attr($parameter); ?>" value="">
            <?php endforeach; ?>
            <div class="jp-intake__honeypot" aria-hidden="true"><label for="jp-company-website">Company website</label><input id="jp-company-website" name="company_website" type="text" tabindex="-1" autocomplete="off"></div>
            <div class="jp-intake__grid">
                <div class="jp-field"><label for="jp-name">Full name <span aria-hidden="true">*</span></label><input id="jp-name" name="name" type="text" autocomplete="name" required aria-describedby="jp-name-error"><span class="jp-field__error" id="jp-name-error" data-error-for="name"></span></div>
                <div class="jp-field"><label for="jp-email">Email <span aria-hidden="true">*</span></label><input id="jp-email" name="email" type="email" inputmode="email" autocomplete="email" required aria-describedby="jp-email-error"><span class="jp-field__error" id="jp-email-error" data-error-for="email"></span></div>
                <div class="jp-field"><label for="jp-phone">Telephone <span aria-hidden="true">*</span></label><input id="jp-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required aria-describedby="jp-phone-error"><span class="jp-field__error" id="jp-phone-error" data-error-for="phone"></span></div>
                <div class="jp-field"><label for="jp-client-type">I am seeking help as… <span aria-hidden="true">*</span></label><select id="jp-client-type" name="client_type" required aria-describedby="jp-client-type-error"><option value="">Select one</option><option value="individual">An individual</option><option value="employer">An employer</option></select><span class="jp-field__error" id="jp-client-type-error" data-error-for="client_type"></span></div>
                <div class="jp-field"><label for="jp-practice-area">Practice area</label><select id="jp-practice-area" name="practice_area"><option value="">Not sure yet</option><?php foreach ($practices as $practice) : ?><option value="<?php echo esc_attr((string) $practice->ID); ?>" <?php selected((int) $attributes['practice_area'], $practice->ID); ?>><?php echo esc_html($practice->post_title); ?></option><?php endforeach; ?></select></div>
                <div class="jp-field"><label for="jp-office">Preferred office</label><select id="jp-office" name="office"><option value="">No preference</option><?php foreach ($offices as $office) : ?><option value="<?php echo esc_attr((string) $office->ID); ?>" <?php selected((int) $attributes['office'], $office->ID); ?>><?php echo esc_html($office->post_title); ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="jp-field"><label for="jp-message">How can we help? <span aria-hidden="true">*</span></label><textarea id="jp-message" name="message" rows="6" minlength="20" maxlength="2500" required aria-describedby="jp-message-help jp-message-error"></textarea><span id="jp-message-help" class="jp-field__help">Please avoid sharing highly sensitive or confidential information. Submitting this form does not create an attorney-client relationship.</span><span class="jp-field__error" id="jp-message-error" data-error-for="message"></span></div>
            <label class="jp-checkbox"><input name="consent" type="checkbox" value="1" required> <span>I agree that JusticePoint may contact me about this request. <a href="<?php echo esc_url(home_url('/privacy/')); ?>">Privacy notice</a>.</span></label>
            <span class="jp-field__error" data-error-for="consent"></span>
            <button class="jp-button jp-button--primary" type="submit" data-submit-button>Send consultation request <span aria-hidden="true">→</span></button>
            <p class="jp-intake__disclaimer">Fictional demonstration only. Do not submit real confidential information.</p>
        </form>
        <?php
        return (string) ob_get_clean();
    }

    public function submit(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $nonce = sanitize_text_field((string) $request->get_param('nonce'));
        if (! wp_verify_nonce($nonce, 'jp_consultation')) {
            return new \WP_Error('jp_invalid_nonce', 'Your form session expired. Refresh the page and try again.', ['status' => 403]);
        }
        if ((string) $request->get_param('company_website') !== '') {
            return new \WP_Error('jp_spam_detected', 'Request could not be accepted.', ['status' => 400]);
        }
        if ($this->rate_limited()) {
            return new \WP_Error('jp_rate_limited', 'Too many requests. Please wait a few minutes or call an office.', ['status' => 429]);
        }

        $payload = $this->payload($request);
        $errors = $this->validate_payload($payload);
        if ($errors !== []) {
            return new \WP_Error('jp_validation_failed', 'Review the highlighted fields and try again.', ['status' => 422, 'fields' => $errors]);
        }

        $delivery = $this->client->send($payload);
        if (! $delivery['success']) {
            return new \WP_Error('jp_crm_unavailable', 'We could not confirm delivery. Please call the nearest office or try again shortly.', ['status' => 502, 'request_id' => $delivery['request_id']]);
        }

        return new \WP_REST_Response(
            [
                'success'    => true,
                'message'    => 'Thank you. Your request was confirmed, and our intake team will follow up shortly.',
                'request_id' => $delivery['request_id'],
                'mock'       => $delivery['mock'],
            ],
            201
        );
    }

    /** @return array<string,mixed> */
    private function arguments(): array
    {
        return [
            'nonce' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            'name' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            'email' => ['required' => true, 'sanitize_callback' => 'sanitize_email'],
            'phone' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            'client_type' => ['required' => true, 'sanitize_callback' => 'sanitize_key', 'validate_callback' => static fn ($value): bool => in_array($value, ['individual', 'employer'], true)],
            'message' => ['required' => true, 'sanitize_callback' => 'sanitize_textarea_field'],
            'practice_area' => ['sanitize_callback' => 'absint'],
            'office' => ['sanitize_callback' => 'absint'],
            'consent' => ['required' => true, 'sanitize_callback' => 'absint'],
            'company_website' => ['sanitize_callback' => 'sanitize_text_field'],
        ];
    }

    /** @return array<string,mixed> */
    private function payload(\WP_REST_Request $request): array
    {
        $tracking = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'msclkid', 'fbclid'] as $key) {
            $tracking[$key] = sanitize_text_field((string) $request->get_param($key));
        }
        return [
            'name'          => sanitize_text_field((string) $request->get_param('name')),
            'email'         => sanitize_email((string) $request->get_param('email')),
            'phone'         => sanitize_text_field((string) $request->get_param('phone')),
            'client_type'   => sanitize_key((string) $request->get_param('client_type')),
            'message'       => sanitize_textarea_field((string) $request->get_param('message')),
            'practice_area' => absint($request->get_param('practice_area')),
            'office'        => absint($request->get_param('office')),
            'campaign_id'   => sanitize_text_field((string) $request->get_param('campaign_id')),
            'consent'       => (bool) $request->get_param('consent'),
            'landing_page'  => esc_url_raw((string) $request->get_param('landing_page')),
            'referrer'      => esc_url_raw((string) $request->get_param('referrer')),
            'tracking'      => array_filter($tracking),
            'submitted_at'  => gmdate('c'),
            'site'          => home_url('/'),
        ];
    }

    /** @param array<string,mixed> $payload @return array<string,string> */
    private function validate_payload(array $payload): array
    {
        $errors = [];
        if (mb_strlen((string) $payload['name']) < 2) {
            $errors['name'] = 'Enter your full name.';
        }
        if (! is_email((string) $payload['email'])) {
            $errors['email'] = 'Enter a valid email address.';
        }
        $phone_digits = preg_replace('/\D/', '', (string) $payload['phone']);
        if (strlen((string) $phone_digits) < 10) {
            $errors['phone'] = 'Enter a valid telephone number.';
        }
        if (! in_array($payload['client_type'], ['individual', 'employer'], true)) {
            $errors['client_type'] = 'Choose individual or employer.';
        }
        $length = mb_strlen((string) $payload['message']);
        if ($length < 20 || $length > 2500) {
            $errors['message'] = 'Provide between 20 and 2,500 characters.';
        }
        if (! $payload['consent']) {
            $errors['consent'] = 'Consent is required before we can contact you.';
        }
        return $errors;
    }

    private function rate_limited(): bool
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $key = 'jp_rate_' . hash_hmac('sha256', $ip, wp_salt('nonce'));
        $count = (int) get_transient($key);
        if ($count >= 5) {
            return true;
        }
        set_transient($key, $count + 1, 15 * MINUTE_IN_SECONDS);
        return false;
    }
}
