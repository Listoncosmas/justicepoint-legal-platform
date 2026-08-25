# Performance and verification record

## Recorded environment

- Test date: August 24, 2026 (America/Chicago)
- URL: `http://localhost/law/`
- Host: local MAMP on macOS, Apache 2.4.54
- HTTP runtime: PHP 8.5.4; required application baseline: PHP 8.2+
- WordPress 7.1, The7 15.0.5, Elementor 4.2.3, Elementor Pro 4.2.2
- Lighthouse 13.4.1 in headless Chrome using its standard mobile simulation and desktop preset
- No CDN, full-page cache, persistent object cache, production TLS, or hosting edge optimization

These are local engineering measurements, not promises about production infrastructure or real-user Core Web Vitals.

## Lighthouse results

| Profile | Performance | Accessibility | Best Practices | SEO | FCP | LCP | CLS | TBT |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Mobile | 100 | 100 | 100 | 66* | 1.1 s | 1.4 s | 0 | 0 ms |
| Desktop | 100 | 100 | 100 | 66* | 0.3 s | 0.5 s | 0 | 0 ms |

`*` Lighthouse deducts the SEO score because the page is intentionally blocked from indexing. That is a required safety feature for this fictional demonstration: the HTML robots directive and `X-Robots-Tag` both enforce `noindex`. The remaining scored SEO audits passed. Removing demo protection solely to inflate a portfolio metric would be misleading.

Machine-readable reports are committed as `docs/lighthouse-home-mobile.json` and `docs/lighthouse-home-desktop.json`.

## What was optimized

- The7 FSE, global block, unused post-type, and local webfont presentation payloads are omitted from these custom templates.
- The child theme uses a system sans stack and Georgia, with no remote font request.
- The hero has a 23 KB mobile WebP candidate and an 86 KB desktop candidate, fixed dimensions, responsive discovery metadata, and the sole high-priority preload.
- Below-fold portraits retain native lazy loading and responsive WordPress image candidates.
- The global theme script is about 1.2 KB before gzip and deferred.
- MapLibre is conditionally registered and loaded only with the office-directory widget/shortcode. The accessible office list exists before it runs.
- Consultation JavaScript is registered but enqueued only when the form shortcode renders.
- REST directory queries are bounded, prime metadata/term caches, and cache normalized filter results for five minutes.

## Browser verification

Headless Chrome was run at 1440×1000 and 390×844. Verified flows include:

- compact and mobile navigation;
- full homepage rendering and responsive imagery;
- directory map initialization, keyboard-visible controls, and server-rendered fallback list;
- shareable `?city=los-angeles` filtering, one-result rendering, canonical stability, and filter robots controls;
- consultation invalid states, mock CRM success, success-only `dataLayer` emission, and absence of PII in analytics;
- Elementor-backed dynamic service-area rendering, visible FAQs, internal relationships, canonical metadata, and JSON-LD;
- zero browser errors on the verified pages and zero automated Axe violations after fixes.

Screenshots are in [`docs/screenshots`](screenshots). PHP error-log output was checked after the verification session; no new JusticePoint warnings, notices, or fatals remained.

## Production follow-up

A production launch should add representative traffic tests, CDN/page/object-cache measurements, TLS/HTTP/2 or HTTP/3 validation, field RUM, authenticated CRM timing, consent-mode impact, cache purging, synthetic monitoring, and Web Vitals segmentation by template and device. Search Console and crawl behavior cannot be validated responsibly on localhost.
