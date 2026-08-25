# Three-minute walkthrough outline

## 0:00–0:25 — The business problem

Open the homepage. Explain that a multi-location firm needs consistent practice content, genuinely local office guidance, and fast intake without rebuilding Elementor layouts. Point out the fictional-demo disclosure and search-index protection.

## 0:25–0:55 — Architecture

Show the repository tree and architecture diagram. Emphasize that The7/Elementor are retained for editorial continuity, while portable business logic lives in the PSR-4 plugin and presentation stays in the child theme.

## 0:55–1:25 — Scalable publishing

Open one Practice Area, one Office, and a Service Area in WP Admin. Create or edit a service record by selecting its practice and office and entering only local fields. Preview the complete page. Mention duplicate-pair prevention, ACF Local JSON, and the native fallback used because licensed ACF Pro was not installed locally.

## 1:25–1:50 — Custom Elementor and directory

Open Elementor’s Legal WebOps category and name the four widgets. On the Office Directory, change city/practice filters, show the URL updating, and point out that the list works without JavaScript while MapLibre loads only here.

## 1:50–2:15 — SEO and migration

View source for one service page: one canonical, meta/OG, robots, BreadcrumbList, LegalService/PostalAddress, and FAQPage only for visible FAQs. Run redirect validation, show the invalid loop/chain fixture failing, then curl one legacy path and show exactly one 301 to a 200.

## 2:15–2:40 — Intake, privacy, and analytics

Submit invalid data to show accessible errors, then a valid fictional request. Explain nonce, sanitization, honeypot, rate limit, UTM/click-ID capture, environment webhook, retries, PII-safe logs, and a `dataLayer` success event only after confirmed delivery.

## 2:40–3:00 — Quality and operations

Show CI and the final verification record: PHPUnit/WPCS/ESLint/Stylelint/build results, desktop/mobile screenshots, no browser/PHP errors, noindex headers, and the honest list of items requiring production hosting, CRM, analytics, and Search Console access.

