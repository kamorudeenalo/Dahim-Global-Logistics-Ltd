# Stage 0 — Discovery, Audit & Architecture

## Status

Stage 0 establishes the target direction for the standalone Dahim Global Logistics platform before production implementation begins.

**Status: READY FOR FINAL REVIEW / APPROVAL**

## Core decision

Dahim will become a standalone application. WordPress, the WordPress theme, WordPress plugin, WordPress REST API, WordPress authentication and WordPress database structure will not be runtime dependencies of the target platform.

The target architecture is a Next.js/React/TypeScript application with PostgreSQL as the authoritative persistence layer, Prisma as the ORM, secure HTTP-only application sessions, storage abstraction for media, and GitHub/GitHub Actions for source control and CI/CD.

## Stage 0 decisions

| Area | Decision |
|---|---|
| Public website | Rebuild as standalone application |
| Dashboard | Rebuild as standalone application |
| WordPress | Temporary migration source only |
| Plugin | Business logic extracted and rebuilt as application domains |
| Theme | Visual/content requirements extracted and rebuilt |
| Database | PostgreSQL |
| ORM | Prisma |
| Authentication | Application-owned secure sessions |
| Authorization | Server-side RBAC/permissions |
| API | Versioned application API |
| SEO | Shared application SEO engine |
| Insights | Simple SEO-focused content system |
| Shipments | Proper relational domain + event history |
| Tracking | Separate restricted public representation |
| Inquiries | CRM-style workflow + event history |
| Media | Storage abstraction + media metadata |
| Source control | GitHub |
| CI/CD | GitHub Actions |
| Migration | Extract → Transform → Validate → Import → Reconcile → Cutover |

## Target application domains

- Authentication and users
- Roles and permissions
- Insights
- SEO
- Media
- Shipments
- Shipment tracking
- Inquiries
- Departments
- Services
- Team
- FAQs
- Trade lanes
- Careers/jobs
- Settings
- Analytics
- Audit logs

## Security baseline

- Passwords use modern password hashing.
- Authentication uses secure HTTP-only cookies/sessions.
- Authorization is enforced server-side.
- Public tracking never exposes private shipment/customer fields.
- Inquiry visibility is enforced by permissions and department access.
- File uploads are validated server-side.
- Rich text is sanitized.
- Authentication and public endpoints are rate-limited.
- Secrets are kept outside source control.
- Production data is backed up and restoration is tested.
- Application changes and business-data changes are separately auditable.

## SEO baseline

SEO metadata and analysis are application-domain concerns shared by the dashboard and public website. The system will support SEO title, meta description, focus keyword, canonical URL, Open Graph data, social image, robots directives and an AIOSEO-style analysis/score. Image alt text belongs to image/media metadata; there is no separate cover-alt concept.

## Migration baseline

WordPress data will not be copied as a WordPress schema. Business entities will be extracted, normalized, deduplicated, validated and imported into the new relational model. Existing URLs will be mapped to new URLs with deliberate redirects where required. Media will be migrated into the new storage system. WordPress users will be reviewed and recreated in the application rather than transferring WordPress sessions.

## GitHub workflow

Every stage and meaningful implementation change must be represented by documentation, an issue/branch where appropriate, a clear commit, and a pull request for review before merge. Stage completion is not considered real until the corresponding GitHub documentation and commit are verifiable.

## Stage 1 entry criteria

Stage 1 may begin only after:

- Stage 0 documents are consolidated and verified.
- The target architecture is accepted.
- Migration strategy is accepted.
- Security baseline is accepted.
- The repository structure for the new application is agreed.
- The Stage 0 completion checkpoint is approved.

## Stage 1 starting point

Stage 1 will establish the actual standalone application foundation: repository/application structure, local development environment, PostgreSQL, Prisma, migrations, authentication foundation, RBAC foundation, API conventions, CI validation and the initial public/dashboard application shells.

See `docs/architecture.md` for the existing target architecture reference.