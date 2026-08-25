<?php

declare(strict_types=1);

namespace Liston\LegalWebOps;

use Liston\LegalWebOps\CLI\Commands;
use Liston\LegalWebOps\Content\ContentTypes;
use Liston\LegalWebOps\Content\DuplicateGuard;
use Liston\LegalWebOps\Content\Fields;
use Liston\LegalWebOps\Elementor\WidgetManager;
use Liston\LegalWebOps\Integrations\ConsultationController;
use Liston\LegalWebOps\Migration\RedirectManager;
use Liston\LegalWebOps\Migration\RedirectRepository;
use Liston\LegalWebOps\REST\OfficeDirectoryController;
use Liston\LegalWebOps\SEO\TechnicalSEO;
use Liston\LegalWebOps\Security\Hardening;

final class Bootstrap
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function register(): void
    {
        (new ContentTypes())->register();
        (new Fields())->register();
        (new DuplicateGuard())->register();
        (new TechnicalSEO())->register();
        (new RedirectManager(new RedirectRepository()))->register();
        (new OfficeDirectoryController())->register();
        (new ConsultationController())->register();
        (new WidgetManager())->register();
        (new Hardening())->register();

        if (defined('WP_CLI') && WP_CLI) {
            (new Commands())->register();
        }
    }

    public static function activate(): void
    {
        (new ContentTypes())->register_types();
        (new RedirectRepository())->install();
        update_option('jp_webops_version', JP_WEBOPS_VERSION, true);
        update_option('blog_public', 0, true);
        flush_rewrite_rules(false);
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules(false);
    }
}
