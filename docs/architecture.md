# Target Architecture

## Architecture decision

The new Dahim platform will be a standalone application. WordPress will not be a runtime dependency of the target system.

## Proposed architecture

```text
                    DAHIM PLATFORM

       +-------------------------------+
       |       Next.js Application     |
       |                               |
       |  Public Website               |
       |  Dashboard                    |
       |  API / Server Actions        |
       +---------------+---------------+
                       |
              Application Services
                       |
       +---------------+---------------+
       |                               |
       v                               v
 Authentication                   Domain Services
       |                               |
       +---------------+---------------+
                       |
                       v
                  PostgreSQL
                       |
             +---------+---------+
             |                   |
             v                   v
        Media Storage       Backups
```

## Recommended stack

- Next.js
- React
- TypeScript
- PostgreSQL
- Prisma ORM
- Secure HTTP-only application sessions
- Object/file storage abstraction for media
- GitHub for source control, issues, pull requests and releases
- GitHub Actions for automated validation/builds

The exact versions will be selected during Stage 1 based on current stable releases and hosting constraints.

## Application boundaries

### Public application

Renders the public Dahim website and SEO metadata directly from the application data layer.

### Dashboard

Uses the same application backend and authentication system. It does not depend on a WordPress REST API.

### Domain layer

Contains business logic for:

- Users and permissions
- Insights
- SEO
- Media
- Shipments
- Inquiries
- Analytics
- Settings

### Database

PostgreSQL will be the authoritative persistence layer.

### Media

Media is accessed through an application storage abstraction so the physical storage provider can change without rewriting the application.

## Security principles

- Passwords are hashed using a modern password-hashing algorithm.
- Authentication uses secure, HTTP-only cookies/sessions.
- Authorization is enforced server-side.
- Sensitive shipment/inquiry fields are protected by role/permission checks.
- Secrets are stored in environment variables or a secrets manager.
- API inputs are validated server-side.
- CSRF/session protections are designed into the authentication architecture.

## SEO principle

SEO metadata and SEO analysis belong to the application domain, not to the presentation layer alone. The public website and dashboard consume the same SEO data and analysis rules.

## Deployment principle

Development -> Git commit -> CI checks -> staging -> acceptance -> production.

Production must never be the primary development environment.
