<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\CLI;

final class Commands
{
    public function register(): void
    {
        $redirects = new RedirectsCommand();
        \WP_CLI::add_command('liston-webops redirects', $redirects);
        \WP_CLI::add_command('liston-webops redirects export-nginx', [$redirects, 'export_nginx']);
        \WP_CLI::add_command('liston-webops redirects export-apache', [$redirects, 'export_apache']);
        \WP_CLI::add_command('liston-webops seed', new SeedCommand());
    }
}
