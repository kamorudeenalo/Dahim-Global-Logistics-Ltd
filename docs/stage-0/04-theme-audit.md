# Stage 0 — WordPress Theme Audit

## Finding
The existing theme owns public presentation but also reaches into plugin/business data. It must not be ported as a WordPress theme. Its useful visual/content behavior should be extracted and rebuilt in the standalone public application.

## Current responsibilities identified
- Public templates and navigation
- Homepage/About/Services/Contact/Tracking
- Insights rendering and discovery
- Header/footer and responsive behavior
- Theme Customizer settings and fallback content
- SEO/meta/schema output
- Contact and tracking front-end behavior
- Client-side UI utilities such as navigation, FAQ, carousel, cookie notice and shipment modal

## Target decisions
- Visual design and approved content: KEEP/REFINE.
- PHP templates: REMOVE after migration.
- Theme Customizer: REPLACE with application settings/content models.
- Business logic inside templates: REMOVE.
- Plugin-function dependencies: REPLACE with domain services/API/data access.
- SEO output: REBUILD in the application SEO layer.
- Hardcoded business content: MIGRATE into authoritative data models where still required.
- Client-side interactions: REBUILD as reusable React components.

## Boundary
The new public presentation layer renders data; it does not own shipment, inquiry, authentication, SEO-analysis, or persistence business rules.
