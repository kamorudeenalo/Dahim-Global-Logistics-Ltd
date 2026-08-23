# Stage 0 — Foundation

## Purpose
Stage 0 defines the baseline, target direction, and governance rules for replacing the current WordPress-dependent Dahim system with a standalone application.

## Core decision
The target Dahim platform will not depend on WordPress at runtime. WordPress remains a migration source and temporary rollback reference until cutover is complete.

## Target stack
- Next.js, React, TypeScript
- PostgreSQL
- Prisma ORM
- Secure HTTP-only application sessions
- Object/file storage abstraction
- Transactional email abstraction
- GitHub for source control and documentation
- GitHub Actions for CI/CD validation

## Stage 0 principles
1. Audit before rebuilding.
2. Preserve business data and SEO value, not WordPress implementation details.
3. Separate public and private data contracts.
4. Enforce authorization server-side.
5. Keep Insights publishing intentionally simple.
6. Treat SEO as an application domain shared by dashboard and public website.
7. Track architecture, implementation, and migration decisions in GitHub.
8. Develop locally/staging first; production is never the development environment.

## Stage 0 outputs
The Stage 0 documentation set covers the current dashboard/plugin/theme, target data model, API/integrations, UI/routes, security, infrastructure, migration, and final architecture.
