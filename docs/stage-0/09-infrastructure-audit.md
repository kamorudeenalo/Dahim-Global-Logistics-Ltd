# Stage 0 — Infrastructure & Deployment Audit

## Target infrastructure
The standalone platform runs independently of WordPress hosting. Public website and dashboard share an application backend backed by PostgreSQL, media storage, transactional email and backups.

## Environments
- Local development
- Staging
- Production

Each environment has separate database, secrets, storage/email configuration and analytics configuration. Production is never used as the primary development environment.

## Deployment workflow
Issue -> branch -> implementation -> commit -> pull request -> CI -> staging -> acceptance -> production.

## CI/CD
GitHub Actions should validate TypeScript, linting, tests, build and later migration/deployment checks.

## Database
PostgreSQL is private to the application/network and managed through versioned Prisma migrations. It is not publicly exposed.

## Media
Uploaded files use a storage abstraction; production should use durable object/file storage rather than treating the application server filesystem as permanent media storage.

## Backups and recovery
Automated database and media backups must be stored off-server/provider where practical. Restoration procedures must be documented and periodically tested.

## Migration deployment
WordPress remains production during development. The new platform is validated in staging, receives migrated data, undergoes acceptance testing, then replaces WordPress at cutover. WordPress is retained temporarily as rollback/reference before retirement.

## Operations
Production requires HTTPS, environment/secrets management, application/error logging, monitoring, dependency/security updates and a documented rollback process.
