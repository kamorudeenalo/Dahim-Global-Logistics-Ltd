# Stage 0.8 — Security Audit

## Purpose

Identify security requirements and risks that must be addressed before the standalone Dahim platform is implemented.

## Security boundaries

The new system must separate:

- public website data;
- authenticated dashboard data;
- administrator-only configuration;
- department-restricted inquiries;
- operational shipment data; and
- internal audit/activity data.

The frontend must never be treated as an authorization boundary. Every protected operation must be authorized server-side.

## Authentication

### Current risk

The existing dashboard depends on WordPress authentication cookies and REST nonces. This creates cross-origin/session complexity and couples authentication to WordPress.

### Target

Use application-owned authentication with secure HTTP-only session cookies. Passwords must be stored only as strong password hashes. Sessions must be revocable and expire according to an explicit policy.

Required controls:

- secure password hashing;
- rate limiting on login;
- account lockout/throttling after repeated failures;
- secure password reset tokens;
- single-use, expiring reset tokens;
- session revocation;
- logout invalidation;
- optional MFA-ready architecture;
- no passwords or session secrets in frontend storage;
- no authentication tokens in URLs.

## Authorization / RBAC

Implement explicit roles and permissions rather than WordPress capabilities.

Every protected API route must enforce authorization server-side.

Examples:

- Administrator: full administration;
- Operations: shipment access according to assigned permissions;
- Content Manager: Insights/content access;
- Department users: inquiry access limited to permitted departments.

Object-level checks are required for inquiries and other department-scoped resources.

## Public shipment tracking

### Current risk

The existing tracking implementation can expose customer and shipment-private fields through tracking responses.

### Target

Public tracking must return a deliberately defined public DTO containing only necessary tracking information:

- tracking number;
- status;
- origin;
- destination;
- current public location;
- estimated delivery;
- last update/time;
- public tracking events if approved.

Never expose by default:

- customer email;
- customer phone;
- declared value;
- private instructions;
- internal notes;
- private consignee information;
- internal operational metadata.

Tracking numbers must be sufficiently unpredictable. Add rate limiting and abuse protection to the public tracking endpoint.

## Inquiries

Inquiry records can contain personal information and must be protected as sensitive business data.

Controls:

- server-side department authorization;
- least-privilege access;
- audit trail for assignment/status changes;
- rate limiting on public submission;
- spam/bot protection;
- input validation and output encoding;
- safe attachment handling if attachments are supported;
- no unrestricted public inquiry lookup.

## File uploads / media

All uploads must be validated server-side.

Required controls:

- MIME/type validation;
- extension validation;
- file-size limits;
- image dimension limits where appropriate;
- generated storage keys rather than trusting filenames;
- no executable file types;
- private/public storage distinction;
- safe image processing;
- authorization for media management;
- alt text stored as content metadata, not inferred from filename.

## SEO/content security

Rich text content must be sanitized server-side before storage/rendering. Never assume editor-generated HTML is safe.

SEO fields must have explicit length and format validation.

Canonical URLs, Open Graph fields and schema data must be escaped/validated before rendering.

## CSRF / request security

The standalone application should use secure same-site session cookies and CSRF protection for state-changing requests where required by the deployment architecture.

Avoid unnecessary cross-origin requests. If an API subdomain is used, configure CORS using an explicit allowlist and never wildcard authenticated origins.

## API security

All APIs must have:

- request validation;
- authentication where required;
- authorization where required;
- consistent error responses;
- rate limiting for public/authentication endpoints;
- pagination limits;
- maximum request body sizes;
- safe query handling through the ORM/database layer;
- no stack traces or secrets in production responses.

Never expose internal database errors directly to clients.

## Secrets and configuration

Secrets must not be committed to GitHub.

Use environment variables/secrets for:

- database credentials;
- session secrets;
- email provider credentials;
- storage credentials;
- analytics/admin secrets;
- deployment credentials.

`.env.example` may document variable names but must contain no real secrets.

## Security headers

Production should use appropriate headers including, as applicable:

- Content-Security-Policy;
- Strict-Transport-Security;
- X-Content-Type-Options;
- Referrer-Policy;
- Permissions-Policy;
- frame protections through CSP/frame-ancestors or equivalent.

## Audit logging

Application audit logs must record security-relevant and business-critical actions, including:

- login/logout/security events;
- password reset events;
- user/role changes;
- shipment creation/update/status changes;
- inquiry assignment/status changes;
- Insight publication/deletion;
- settings changes.

Audit logs must be append-oriented and protected from ordinary users.

## Backups and recovery

Production requires:

- automated database backups;
- retention policy;
- off-server backup storage;
- tested restoration procedure;
- media backup strategy;
- documented recovery objectives.

A backup that has never been restored successfully is not considered a verified backup.

## Dependency and supply-chain security

Use lockfiles and review dependency changes. Automated dependency/security checks should run through GitHub Actions before production releases.

## Deployment security

Production must use HTTPS. Administrative interfaces should not be publicly exposed without authentication. Database services must not be directly exposed to the public internet.

Deployment credentials must be stored as GitHub/environment secrets rather than committed files.

## Security classification decisions

| Area | Decision |
|---|---|
| WordPress auth | REMOVE |
| WP nonce | REMOVE |
| WordPress cookies | REMOVE |
| Application sessions | REBUILD |
| RBAC | REBUILD |
| Department authorization | REBUILD |
| Public tracking DTO | REBUILD |
| Shipment private data | RESTRICT |
| Inquiry data | RESTRICT |
| Media uploads | REBUILD |
| Audit logging | REBUILD |
| Rate limiting | ADD |
| Security headers | ADD |
| Backup/recovery | ADD |

## Security acceptance criteria for Stage 1

Before production, the standalone platform must demonstrate:

1. Unauthenticated users cannot access dashboard APIs.
2. Authenticated users cannot access resources outside their permissions.
3. Department restrictions are enforced server-side.
4. Public tracking exposes only approved public fields.
5. Login and password reset are rate-limited and securely implemented.
6. Uploaded files are validated and safely stored.
7. Rich text is sanitized.
8. Secrets are absent from the repository.
9. Security-relevant actions are auditable.
10. Database and media backups can be restored successfully.
