# Oracle deployment: `justicepoint.crmpl.us`

## Topology

- Host: CosmicJaunt Oracle Ubuntu 24.04 instance (ARM64)
- Origin: Nginx 1.24 over PHP 8.3 FPM and MariaDB 10.11
- Public hostname: `https://justicepoint.crmpl.us/`
- TLS: Let's Encrypt at the origin, with automated renewal
- Edge: Cloudflare-proxied `justicepoint` CNAME to the existing `crmpl.us` apex, with the zone's automatic TLS policy and conservative static caching
- Application root: `/var/www/justicepoint.crmpl.us/current`
- Shared state: `/var/www/justicepoint.crmpl.us/shared/{uploads,logs,backups,migration}`
- First release: `20260825T014800Z`

The vhost and FPM pool are versioned in `ops/`. Existing CosmicJaunt applications use separate roots, pools, databases, logs, and server blocks.

The shared `crmpl.us` zone currently uses Cloudflare Automatic SSL/TLS in Full mode. JusticePoint still presents a publicly trusted Let's Encrypt certificate at the origin. Moving the whole zone to Full (strict) requires a separate certificate audit of its other 100+ records; this deployment does not risk unrelated subdomains to improve one site's setting.

## Release procedure

1. Build and test custom assets locally.
2. Create a timestamped release containing WordPress core, the custom plugin/child theme, and locally licensed The7/Elementor dependencies. Commercial code is transferred directly and never committed.
3. Run Composer with `--no-dev --classmap-authoritative` for the application plugin.
4. Link the shared upload directory and production `wp-config.php`.
5. Install or update WordPress with WP-CLI, activate required plugins/theme, run the idempotent seed command, and verify database integrity.
6. Test the Nginx and PHP-FPM configurations before reloading either service.
7. Switch the `current` symlink atomically, flush WordPress rewrites and caches, and run smoke tests.
8. Keep the prior release and database backup for rollback.

The production configuration sets `FS_METHOD=direct`. The isolated release and upload paths are owned by the PHP-FPM user, so Elementor can generate CSS without falling through to WordPress's FTP adapter; configuration and credential files remain `root:www-data` with mode `0640`.

## Cache policy

Anonymous HTML receives a 10-minute Nginx FastCGI cache. Logged-in users, write methods, query-string variants, WordPress administration, login, cron, and REST routes bypass it. Static versioned assets receive one-year immutable caching. The cache can serve stale content briefly during an upstream error or background refresh. WordPress cron runs from the system scheduler rather than public requests.

The system also creates a restrictive nightly MariaDB export and deletes snapshots older than 14 days. This is a portfolio recovery baseline, not a substitute for encrypted off-host backups and regularly rehearsed restores.

## Indexation boundary

This remains a fictional public portfolio demonstration. `blog_public=0`, HTML robots metadata, and `X-Robots-Tag` intentionally keep it out of search indexes; Nginx also protects directly requested static media. The virtual `robots.txt` allows crawling so compliant engines can actually see the noindex directive; it does not use `Disallow: /` as a substitute for noindex. Production indexation should be enabled only after legal-content, identity, domain, analytics, Search Console, privacy, and conversion tracking sign-off.

## Rollback

Point `/var/www/justicepoint.crmpl.us/current` to the previous timestamped release, restore the pre-deploy database dump if the schema or content changed incompatibly, reload PHP-FPM to clear OPcache, clear `/var/cache/nginx/justicepoint`, and rerun the smoke test. No other virtual host is changed.

## Production acceptance checks

The deployment gate covers WordPress core checksums, database integrity, active dependency versions, PHP log cleanliness, all sitemap URLs, redirects, canonicals, metadata, headings, image alternatives, parseable JSON-LD, internal links, REST filtering, consultation delivery, cache behavior, TLS, security headers, and desktop/mobile browser rendering. Results that depend on the public hostname are recorded in [performance.md](performance.md) after DNS and certificate activation.
