# URL migration plan

The sample covers 20 services published across `.html`, practice-directory, and city-first legacy patterns. The migration objective is one relevant 301 per legacy URL, never a chain or broad homepage fallback.

## 1. Inventory evidence

Export indexed URLs and canonical targets from Google Search Console, the XML sitemap, crawl tools, CMS/database records, analytics landing pages, server logs, paid-campaign landing pages, backlinks, and any internal search index. Record status, canonical, title, organic sessions, conversions, links/referring domains, and proposed content owner.

## 2. Group duplicates and select the strongest destination

Cluster pages by legal service, intent, market, and overlapping content. For each group, choose the destination that best preserves intent and accumulated signals. A page with stronger links is not automatically the right target if its intent differs; relevance comes first, then signal consolidation.

## 3. Create one-to-one mappings

Write explicit source and final destination rows. Do not redirect every removed service to the homepage. Preserve query strings only when they remain useful and safe. Normalize slash/protocol policy before import.

Brand migrations may legitimately send the same path to a different host, for example `/service/` to `https://newbrand.example/service/`. The validator's comparison key therefore retains scheme, host, effective port, and normalized path. This prevents a cross-domain move from being misclassified as a loop while still detecting a genuine same-origin self-redirect. At runtime, WordPress allows only the already validated destination host for that single redirect request.

## 4. Update owned references

Change internal links, navigation, XML sitemaps, HTML canonicals, hreflang if applicable, paid campaigns, CRM templates, email links, and structured data to the final URLs. A redirect is not a substitute for updating an owned link.

## 5. Test in staging

Run:

```bash
wp liston-webops redirects validate redirects.csv
wp liston-webops redirects import redirects.csv --dry-run
wp liston-webops redirects export-nginx redirects.csv
wp liston-webops redirects export-apache redirects.csv
```

Then crawl every source and destination. Require exactly one 301, a final 200, the intended canonical, expected robots behavior, and no source in a sitemap. Test case, encoded paths, `.html`, slash variants, HTTP/HTTPS, subdirectory installs, pagination, and high-value query parameters.

## 6. Deploy permanent redirects

Deploy at the highest reliable layer—CDN/edge or web server for high-volume production—using the generated rules as reviewed input. The WordPress table is a demonstration and a practical lower-volume fallback. Back up configuration and database, prepare a rollback, deploy in a controlled window, and warm critical cache entries.

## 7. Submit and monitor

Submit final sitemaps in Search Console and inspect representative source/final URLs. Preserve the mappings for at least a year and usually longer when backlinks or recurring traffic remain.

## 8. Watch post-launch signals

Daily initially, then weekly: coverage, crawl stats, soft 404s, redirect errors, canonical selection, rankings, landing-page traffic, backlinks, paid links, conversions, CRM campaign attribution, and server 404/5xx logs. Compare by template and migration group, not only sitewide totals.

## Validator policy

Blocking errors: malformed/missing endpoints, duplicate sources, self/two-way/arbitrary cycles, chains, unsafe mass-home consolidation, final destination 4xx/5xx, or a destination that itself redirects.

Review warnings: shared destinations, scheme changes, `.html`/slash collisions, unreachable destination checks, or unsupported status normalization.
