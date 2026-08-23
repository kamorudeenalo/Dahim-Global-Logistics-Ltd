# Stage 0 — Final Architecture

## Decision
Dahim will become a standalone application. WordPress is not a runtime dependency of the target platform.

## Architecture

```text
Public Website ---------+
                        |
Dashboard --------------+--> Application/API --> Domain Services --> PostgreSQL
                                  |                    |
                                  |                    +--> Media Storage
                                  |                    +--> Email
                                  |                    +--> Audit/Events
                                  |
                                  +--> Authentication / Authorization
```

## Stack direction
- Next.js + React + TypeScript
- PostgreSQL
- Prisma
- Secure HTTP-only sessions
- Server-side RBAC/department authorization
- Object/file storage abstraction
- Transactional email abstraction
- GitHub + GitHub Actions

## Domain boundaries
- auth/users/roles/permissions
- insights/SEO/media
- shipments/tracking/events
- inquiries/departments/events
- services/team/faqs/trade-lanes/jobs
- settings/analytics/audit

## Public/private boundary
Public routes expose only deliberately public data. Dashboard routes require authenticated sessions and server-side permission checks. Public tracking uses a restricted response model separate from the internal shipment entity.

## SEO
SEO metadata and analysis are first-class application concerns. Dashboard analysis and public HTML metadata use the same underlying SEO rules/data. Insights remain a simplified SEO/content feature rather than a publishing-company workflow.

## Source of truth
- PostgreSQL: authoritative business/content data
- Object/file storage: media bytes
- Application/domain services: business rules
- GitHub: software and architecture history
- WordPress: temporary migration source/rollback reference only

## Stage 1 boundary
Stage 1 may begin only after Stage 0 documentation is consolidated and approved. Stage 1 establishes the application shell, local development, database/Prisma, authentication/RBAC, CI and first end-to-end foundation features.
