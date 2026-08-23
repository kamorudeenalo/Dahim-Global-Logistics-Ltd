# Architecture Decision Records

## ADR-001 — Standalone application instead of WordPress

**Status:** Proposed for Stage 0 approval

### Context

The current system uses WordPress as the website renderer, database-backed CMS, authentication layer, REST API, media manager, and extension platform while a separate React dashboard implements custom application behaviour.

This has produced coupling between the dashboard, plugin, theme, WordPress authentication, REST nonces, CORS, metadata, and deployment paths.

### Decision

Rebuild Dahim as a standalone application with its own application backend, database, authentication, media abstraction, SEO engine, public website, and dashboard.

### Consequences

Positive:

- One application source of truth
- One authentication model
- No WordPress plugin/theme dependency
- Native SEO and Insights model
- Easier testing and deployment
- Clearer domain boundaries

Negative:

- Requires a migration project
- Requires a new database
- Requires rebuilding public pages and business features
- Requires migration/redirect planning

### Alternatives considered

1. Continue extending WordPress.
2. Keep WordPress as backend and React as frontend.
3. Use WordPress headless.
4. Build a standalone platform.

The standalone platform is preferred because it removes the largest sources of coupling while matching the actual business requirements.

## ADR-002 — One application for public site and dashboard

**Status:** Proposed

The public website and dashboard should share the same application/backend and domain model unless a future scale requirement justifies separation.

## ADR-003 — PostgreSQL as authoritative database

**Status:** Proposed

Use PostgreSQL for structured application data, with migrations tracked in Git.

## ADR-004 — Native SEO engine

**Status:** Proposed

SEO analysis and metadata are first-class application features. They should not depend on WordPress or a third-party WordPress SEO plugin.
