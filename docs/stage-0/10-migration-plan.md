# Stage 0 — Migration Plan

## Principle
Migrate business data and SEO value, not WordPress implementation details.

## Pipeline
Preparation -> Extraction -> Transformation -> Validation -> Import -> Verification -> Cutover.

## Data to migrate
- Insights, categories, tags and SEO metadata
- Media and image metadata
- Shipments and available status/history information
- Inquiries
- Departments
- Trade lanes
- Services
- Team members
- FAQs
- Jobs if retained
- Relevant site/business settings

## Do not migrate as architecture
- WordPress sessions/cookies/nonces
- CPT definitions
- wp_posts/wp_postmeta structure
- plugin/theme internals
- WordPress admin UI
- legacy dashboard REST response contracts
- CORS/authentication bridges

## Mapping
New records receive application-native IDs. A migration map records source entity/type and source ID against the new ID for traceability.

## SEO and URLs
Preserve slugs/URLs where practical. Build an explicit old-path -> new-path redirect map. Preserve SEO title, meta description, canonical and social metadata where valid. Recalculate SEO analysis scores in the new engine rather than migrating stale scores.

## Validation
Every migration run produces counts, failures, warnings and reconciliation results. No record should disappear silently. Relationships and representative records are verified, not only aggregate counts.

## Cutover
1. Dry-run migration.
2. Staging migration and manual acceptance.
3. Resolve migration warnings/errors.
4. Define a short content/data freeze.
5. Final extraction/import.
6. Reconcile counts and critical records.
7. Switch routing/DNS as applicable.
8. Monitor.
9. Roll back to WordPress if acceptance criteria fail.
10. Retire WordPress only after a stability period.
