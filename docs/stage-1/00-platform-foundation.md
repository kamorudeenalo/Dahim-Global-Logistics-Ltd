# Stage 1 — Platform Foundation

## Status

Started after Stage 0 approval.

## Objective

Create the technical foundation for the standalone Dahim Global Logistics platform without making WordPress a runtime dependency.

## Stage 1 scope

1. Establish the application repository structure.
2. Establish the local development environment.
3. Establish the Next.js/React/TypeScript application foundation.
4. Establish PostgreSQL and Prisma.
5. Establish the first database migration.
6. Establish authentication and session foundations.
7. Establish RBAC/permission foundations.
8. Establish API conventions.
9. Establish public website and dashboard shells.
10. Establish GitHub Actions validation.
11. Establish environment-variable conventions.
12. Establish documentation and change-tracking conventions.

## Non-goals

Stage 1 does not yet migrate production data or replace the existing live WordPress site. It creates the foundation on which later stages will build the standalone platform.

## Completion criteria

Stage 1 is complete when the project can be installed locally, the application can start, PostgreSQL can be connected through Prisma, the initial migration runs successfully, authentication foundations exist, the public and dashboard shells render, CI validates the project, and all of these changes are documented in GitHub.

## Branch

`stage-1/platform-foundation`

## Rule

Every meaningful implementation step must be committed to GitHub. The conversation is not the source of truth; the repository is.