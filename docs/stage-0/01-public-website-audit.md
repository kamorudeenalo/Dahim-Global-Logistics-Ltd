# Stage 0 — Public Website Audit

## Scope
The public Dahim website is being preserved functionally while its WordPress runtime dependency is removed.

## Public routes/features to preserve or rebuild
- Home
- About
- Services and service details
- Shipment tracking
- Insights listing and article details
- Contact/inquiry flow
- Careers/jobs where retained
- Search/category discovery where useful
- Header/navigation/footer
- 404/error handling
- Responsive behavior

## SEO requirements
Preserve valuable public URLs wherever practical. Where routes change, maintain an explicit redirect map. Preserve or migrate SEO titles, meta descriptions, canonical URLs, Open Graph metadata, slugs, structured data requirements, sitemap behavior, robots/indexability rules, and image alt metadata.

## Target decisions
- Public presentation: REBUILD in Next.js.
- WordPress templates: REMOVE after migration.
- WordPress runtime: REMOVE.
- Public content/data: MIGRATE.
- Public shipment tracking: REBUILD with a restricted public response model.
- Contact forms: REBUILD against the standalone inquiry API.
- Insights: MIGRATE and SIMPLIFY.
- Existing brand/content value: KEEP where approved.

## Acceptance principle
The standalone public site must retain business functionality and SEO continuity without requiring WordPress, its theme, plugin, REST API, or admin runtime.
