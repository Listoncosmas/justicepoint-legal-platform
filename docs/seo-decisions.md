# Technical SEO decisions

## Canonicals

WordPress permalink output is the default and follows the configured trailing-slash policy. Archive pagination canonicalizes to the exact numbered page. Search and 404 pages omit canonical output. Overrides are accepted only when `wp_http_validate_url` succeeds, the scheme is HTTP(S), the host equals `home_url`, and no fragment is present.

## Robots and filters

Production filter combinations with `city`, `state`, `practice_area`, or `page` parameters are `noindex,follow` unless deliberately promoted to a curated `service_area`. Localhost, loopback, `.test`, `.local`, or `blog_public=0` force `noindex,nofollow,noarchive` and an equivalent `X-Robots-Tag`.

This matters because a demonstration should not leak into an index even if a reverse proxy exposes it.

## Schema

The graph uses stable identifiers:

- homepage: `Organization`;
- office and service pages: `LegalService`, `PostalAddress`, `GeoCoordinates`;
- attorney: `Person` linked to the organization;
- navigable pages: `BreadcrumbList`;
- pages with visible published FAQs: `FAQPage`.

No review, aggregate rating, award, or legal outcome is invented. `priceRange` is a broad LocalBusiness compatibility field, not a fee promise.

## Sitemaps

WordPress core sitemaps remain the generator. FAQs and Elementor templates are excluded. A post flagged `_jp_exclude_sitemap` or resolving as a redirect source is removed from an entry. Redirect sources do not become posts and therefore do not appear by default.

## SEO-plugin compatibility

The core checks constants/classes for Yoast, Rank Math, AIOSEO, and The SEO Framework. When detected, it suppresses JusticePoint canonical, description/Open Graph, and JSON-LD output rather than trying to partially merge graphs. The plugin’s local/demo robots protection remains because it is an environment safety control.

Production integration would add an adapter per selected SEO plugin and automated HTML snapshots to confirm exactly one title, description, canonical, robots directive, Open Graph set, and JSON-LD graph.

## Content quality

`service_area` is intentionally indexable only when an editor publishes a curated record with unique local information. Duplicate practice/office pairs are prevented. Thin parameter combinations stay out of the index. Internal links connect practice, office, local service, attorney, and FAQ entities in both directions visible to people and crawlers.

