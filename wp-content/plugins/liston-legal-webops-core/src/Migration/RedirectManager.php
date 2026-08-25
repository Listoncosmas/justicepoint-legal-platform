<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Migration;

final class RedirectManager
{
    public function __construct(private RedirectRepository $repository)
    {
    }

    public function register(): void
    {
        add_action('template_redirect', [$this, 'maybe_redirect'], 0);
        add_action('admin_menu', [$this, 'admin_menu']);
    }

    public function maybe_redirect(): void
    {
        if (is_admin() || wp_doing_ajax()) {
            return;
        }
        $request = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $path = RedirectRepository::normalize_path((string) strtok((string) $request, '?'));
        $home_path = rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
        if ($home_path !== '' && ($path === $home_path || str_starts_with($path, $home_path . '/'))) {
            $path = RedirectRepository::normalize_path((string) substr($path, strlen($home_path)));
        }
        $redirect = $this->repository->find($path);
        if (! $redirect) {
            return;
        }
        $destination = (string) $redirect['destination_url'];
        if (RedirectRepository::normalize_path($destination) === $path) {
            return;
        }
        $this->repository->increment_hits((int) $redirect['id']);
        wp_safe_redirect($destination, (int) $redirect['status_code'], 'JusticePoint Redirect Manager');
        exit;
    }

    public function admin_menu(): void
    {
        add_management_page('JusticePoint Redirects', 'JusticePoint Redirects', 'manage_options', 'justicepoint-redirects', [$this, 'render_admin']);
    }

    public function render_admin(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage redirects.', 'liston-legal-webops'));
        }
        $rows = $this->repository->all();
        echo '<div class="wrap"><h1>JusticePoint Redirects</h1><p>Redirects are validated and imported with <code>wp liston-webops redirects …</code>. This read-only screen intentionally avoids unsafe browser uploads.</p>';
        echo '<table class="widefat striped"><thead><tr><th>Source</th><th>Destination</th><th>Status</th><th>Hits</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            printf('<tr><td><code>%1$s</code></td><td><a href="%2$s">%3$s</a></td><td>%4$d</td><td>%5$d</td></tr>', esc_html((string) $row['source_path']), esc_url((string) $row['destination_url']), esc_html((string) $row['destination_url']), (int) $row['status_code'], (int) $row['hits']);
        }
        if ($rows === []) {
            echo '<tr><td colspan="4">No redirects imported.</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}
