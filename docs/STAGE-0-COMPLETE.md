# Stage 0 — Discovery, Audit & Architecture

## Status
DOCUMENTATION CONSOLIDATED — awaiting final approval to begin Stage 1.

## Why this stage exists
The previous Dahim solution evolved into a React dashboard coupled to WordPress, a large business-logic plugin, a public theme, WordPress REST/authentication, CPT/post-meta storage and several compatibility bridges. Stage 0 establishes a clean replacement architecture before further production implementation.

## Final decision
Build a standalone Dahim platform using Next.js/React/TypeScript, PostgreSQL and Prisma. WordPress will be used only as a migration source and temporary rollback/reference during cutover.

## Product scope
### Public website
Home, About, Services, public shipment tracking, Insights, Contact/Inquiries, Careers where retained, SEO and responsive public presentation.

### Dashboard
Overview, Shipments, Inquiries, Insights, Media, Services, Trade Lanes, Team, FAQs, Careers, Users/Roles, Settings and Activity Log.

### Platform services
Authentication, authorization/RBAC, SEO analysis/metadata, media storage, email, audit logging, validation, migrations, backups and deployment tooling.

## Key decisions
- PostgreSQL replaces WordPress CPT/post-meta as the authoritative data model.
- Application-owned secure HTTP-only sessions replace WordPress cookie/nonce authentication.
- Authorization is server-side and permission/department aware.
- Public shipment tracking has a deliberately restricted data contract.
- Operational records use history/events and should not rely on destructive deletion for normal business states.
- Insights remain simple and SEO-focused; no contributor/editorial-publishing complexity is required.
- SEO is a reusable application domain; the score shown in the dashboard and metadata rendered publicly share the same rules/data.
- Image alt text belongs to media/image metadata; no separate cover-alt field.
- Media storage, email and other providers are abstracted from business logic.
- Development flows through GitHub branches, commits, pull requests, CI, staging and production.
- Production is not a development environment.

## Migration strategy
Extract -> transform -> validate -> import -> reconcile -> cut over. Preserve valuable URLs/SEO, data and media while removing WordPress implementation details. Use dry runs, staging acceptance, final reconciliation and a rollback window.

## Stage 0 checklist
- [x] Foundation established
- [x] Public website scope/audit documented
- [x] Dashboard audited
- [x] WordPress plugin audited
- [x] WordPress theme audited
- [x] Target data model documented
- [x] API/integration architecture documented
- [x] UI/routes documented
- [x] Security requirements documented
- [x] Infrastructure/deployment direction documented
- [x] Migration plan documented
- [x] Final architecture documented
- [x] Documentation consolidated in GitHub
- [ ] Final Stage 0 approval

## Stage 1 entry criteria
Stage 1 starts after approval of this architecture. Stage 1 will create the standalone application foundation: repository/application structure, Next.js/TypeScript, PostgreSQL/Prisma, initial schema/migrations, authentication, RBAC, API conventions, public/dashboard shells, local development instructions and GitHub Actions validation.

## Change-control rule
If a major architectural assumption changes after approval, record the decision in GitHub rather than silently diverging from this baseline.
