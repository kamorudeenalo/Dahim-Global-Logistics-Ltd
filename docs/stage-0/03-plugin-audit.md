# Stage 0.3 — Current WordPress Plugin Audit

**Status:** Audited
**Source:** `wordpress-plugin/` on `stage-0/foundation`
**Purpose:** Document the current plugin's responsibilities before extracting the business domain into the standalone Dahim platform.

## 1. Plugin composition

The current repository contains three PHP plugin components:

- `dahim-dashboard.php` — large monolithic plugin containing CPTs, admin UI, business logic, REST API, authentication, email, import/export, and access controls.
- `dahim-dashboard-cross-origin.php` — CORS/auth bridge for a separate dashboard origin.
- `dahim-insights-seo.php` — separate SEO metadata registration for WordPress posts.

The main plugin file is versioned as **1.0.7 in the audited repository source**. This must be reconciled with later deployed/plugin-package versions before migration; repository source and production version history should not be treated as identical without verification.

## 2. Core responsibilities currently in the plugin

The plugin currently acts as:

1. WordPress content model provider.
2. Operational logistics data store through CPTs and post meta.
3. Dashboard backend/API.
4. Authentication layer for the React dashboard.
5. Contact/inquiry processing system.
6. Shipment tracking and shipment notification engine.
7. Department routing system.
8. Job management system.
9. Admin workflow enhancement layer.
10. CSV import/export utility.
11. Email templating/notification system.
12. Insights SEO metadata provider.
13. Some data migration/seeding logic.

This confirms that the plugin is effectively functioning as a business application backend inside WordPress.

## 3. Custom content/domain entities

The plugin registers these content types:

| Current entity | WordPress implementation | Standalone target |
|---|---|---|
| Services | `service` CPT | REBUILD as content/config entity if still required |
| Team Members | `team_member` CPT | REBUILD as people/content entity if required |
| FAQs | `faq` CPT | REBUILD as simple content entity |
| Trade Lanes | `trade_lane` CPT | REBUILD as native data entity if operationally required |
| Shipments | `shipment` CPT | REBUILD as core logistics entity |
| Departments | `department` CPT | REBUILD as core organizational entity |
| Jobs | `job` CPT | REVIEW, then REBUILD/SIMPLIFY if still required |
| Inquiries | `inquiry` CPT | REBUILD as core CRM/workflow entity |
| Insights | WordPress `post` | REBUILD as native Insight entity |

The plugin explicitly exposes these entities through the WordPress REST API. The standalone system must not reproduce them as generic posts with metadata.

## 4. Shipment domain

Shipment records currently contain data including:

- Tracking number
- Shipment owner name/email/phone
- Consignee name/phone
- Origin
- Destination
- Current location
- Package description
- Weight
- Pieces
- Dimensions
- Declared value
- Service type
- Carrier
- Date booked
- Estimated delivery
- Special instructions
- Current stage

Current stages are:

1. Booked
2. Cleared at Port / Picked Up
3. In Transit
4. Out for Delivery
5. Delivered

Tracking numbers are generated in the plugin using a random non-sequential `DGL-XXXX-XXXX` format.

### Standalone decision

**REBUILD.**

The new model should use real relational/domain fields, not WordPress post meta. Shipment status should be an explicit enum/state model, with optional status history for auditability. Public tracking responses must expose only deliberately approved customer-facing fields.

## 5. Shipment notifications

The plugin currently sends:

- Shipment-created customer email.
- Shipment-created internal/operations email.
- Shipment status-change customer email.
- Shipment cancellation email.

The notification system uses WordPress `wp_mail()` and branded HTML templates.

### Standalone decision

**REBUILD.**

Keep the business events and notification intent. Replace `wp_mail()`/WordPress hooks with an application mail service and explicit domain events.

## 6. Inquiry domain

Inquiries currently capture:

- Name
- Company
- Phone
- Email
- Service
- Role
- CV/portfolio link
- Message
- Department
- Status
- Submission token

The plugin supports statuses including New, Contacted, In Progress, Closed, and Abandoned.

It also supports department-scoped visibility and direct-edit protection in wp-admin.

### Standalone decision

**REBUILD.**

Inquiry privacy and department restrictions should be enforced at the API/database authorization layer, not through WordPress admin query filters.

## 7. Inquiry workflow

Current capabilities include:

- Website form submission.
- Honeypot and timing spam checks.
- Duplicate-submission protection using a submission token.
- Saving the inquiry to the database.
- Department routing.
- Internal notification email.
- Customer confirmation email.
- Status changes.
- Bulk status actions.
- Search across inquiry metadata.
- Department filtering.
- CSV export/import.
- Department-specific access restrictions.

### Standalone decision

**REBUILD + IMPROVE.**

Preserve the workflow, but model it as a proper inquiry/CRM entity with assignment, audit history, and server-side authorization.

## 8. Departments

Departments currently act as both:

- Public contact-routing configuration.
- Inquiry routing/security boundary.
- Email notification configuration.
- Customer confirmation-message configuration.

The plugin seeds default departments and stores department configuration in post meta.

### Standalone decision

**REBUILD.**

A Department should become a real database entity. Notification configuration and routing rules should be first-class fields/configuration rather than post meta.

## 9. Trade lanes

Trade lanes contain:

- Origin
- Destination
- Mode
- Transit time
- Ordering

### Standalone decision

**REBUILD / REVIEW.**

Keep if trade lanes remain a meaningful operational/public-site feature. Use a native relational model.

## 10. Jobs

Jobs currently contain:

- Role/title
- Description/content
- Location
- Type
- Deadline
- Open/closed status

The plugin can expose public job pages and includes admin management.

### Standalone decision

**REVIEW.**

Before implementation, confirm whether this is a current business requirement or legacy functionality. If retained, implement as a lightweight recruitment/content entity.

## 11. Services, team members and FAQs

The plugin uses CPTs and admin meta boxes for these public-facing content entities. It also contains one-time seed/migration routines that copy fallback theme content and images into WordPress content/media.

### Standalone decision

**REBUILD selectively.**

These should become simple content/configuration entities only where the public website actually needs them. Do not reproduce WordPress CPT infrastructure merely because it exists today.

## 12. Admin functionality

The plugin adds substantial WordPress admin enhancements, including:

- Custom meta boxes.
- Custom admin columns.
- Inquiry status dropdowns.
- Inquiry menu badge.
- Inquiry bulk actions.
- Inquiry department filtering.
- Department-scoped user access.
- Dashboard "At a Glance" widget.
- CSV import/export.
- One-click duplication.
- Feature toggles.
- Contact settings in the Customizer.
- User dashboard-access status.

### Standalone decision

**REBUILD selectively.**

These features should become normal dashboard application screens/workflows rather than WordPress admin customizations.

## 13. Authentication

The plugin implements dashboard authentication endpoints under `dahim/v1/auth/*`, including login, logout, session restoration, registration, forgot-password, and reset-password.

The current design uses WordPress cookies and REST nonces. The main plugin source explicitly describes the React dashboard as dependent on WordPress cookie/nonce authentication.

The repository also contains a separate cross-origin bridge that changes the trusted browser-origin handling and emits credentialed CORS headers for dashboard subdomains.

### Standalone decision

**REMOVE FROM TARGET ARCHITECTURE.**

Authentication should be implemented natively with secure application sessions, HTTP-only cookies, password hashing, role/permission checks, session revocation, and appropriate CSRF protection.

This is one of the clearest reasons to remove WordPress from the architecture: the current system requires several layers of WordPress authentication/CORS/session coordination for a custom React application.

## 14. REST API

The plugin exposes dashboard resources through WordPress REST routes and WordPress's CPT REST support. It also registers protected post meta for shipments, inquiries, trade lanes, departments, jobs, and team members.

### Standalone decision

**REPLACE.**

Define an application API around business domains instead of WordPress resource conventions.

Proposed domains:

- `/api/auth`
- `/api/dashboard`
- `/api/insights`
- `/api/media`
- `/api/shipments`
- `/api/inquiries`
- `/api/departments`
- `/api/trade-lanes`
- `/api/jobs` if retained
- `/api/site-content`
- `/api/settings`

## 15. REST metadata design

The current plugin registers many protected underscore-prefixed WordPress meta fields with `show_in_rest` and a generic `current_user_can('edit_posts')` authorization callback.

This is functional inside WordPress but creates several problems for the standalone system:

- Business fields are flattened into key/value metadata.
- Authorization is tied to WordPress capabilities.
- Data validation is spread across meta callbacks and post handlers.
- Relational concepts such as departments and assignments are represented as strings.
- Sensitive shipment/customer fields live beside public tracking fields.

### Standalone decision

**REPLACE WITH RELATIONAL SCHEMA.**

Sensitive/private fields should be separated logically and authorization should be evaluated by domain rules.

## 16. SEO component

`dahim-insights-seo.php` currently registers three WordPress post meta fields:

- Focus keyword
- SEO title
- Meta description

They are exposed through REST and require `edit_posts` capability.

This is only a metadata layer; the agreed richer SEO analysis is primarily implemented in the dashboard/editor logic.

### Standalone decision

**REBUILD.**

SEO should become a first-class application service/model attached to Insights. The editor should calculate the score from its actual state, and public rendering should consume the same source of truth.

## 17. Email system

The plugin contains a substantial custom email system for:

- Inquiries.
- Shipment creation.
- Shipment status updates.
- Shipment cancellation.
- Dashboard account requests.
- Account approval.
- Password reset.
- Password-change notifications.

### Standalone decision

**REBUILD as notification service.**

The reusable concepts are templates, events, recipients, and delivery status. The new system should allow provider configuration without WordPress dependency.

## 18. CSV import/export

The plugin supports CSV export/import for:

- Inquiries
- Shipments
- Trade lanes

Imports create new records and exports provide operational fields.

### Standalone decision

**KEEP CONCEPT, REBUILD IMPLEMENTATION.**

This is useful for migration and operational backup. The new application should support controlled import/export with validation, duplicate handling, authorization, and audit logging.

## 19. Data seeding/migration currently inside plugin

The plugin includes one-time routines that seed/migrate:

- FAQs
- Services
- Departments
- Team members and their images

This demonstrates that some public content previously existed as theme fallback data rather than authoritative database records.

### Migration implication

During Stage 0.6, we must identify which current public content exists in:

- WordPress posts/pages
- WordPress post meta
- Theme hardcoded fallbacks
- Media library
- Plugin defaults

Nothing should be migrated blindly from only the database.

## 20. Security observations

Positive controls already present include:

- WordPress nonces for admin forms.
- Capability checks.
- Input sanitization.
- Explicit origin checking for authentication POSTs.
- Strict CORS allow-list in the separate bridge.
- Department access restrictions.
- Public/private shipment separation intent.
- No direct file access guard.
- CSV import/export nonce checks.

However, the architecture still relies heavily on WordPress's security model and REST behavior. The standalone platform will need its own explicit security model, including:

- Session security.
- CSRF strategy.
- Rate limiting.
- Login throttling.
- Role/permission matrix.
- Audit logs.
- Object-level authorization.
- File-upload validation.
- API request validation.
- Sensitive-field separation.

## 21. Major architectural finding

The plugin is not merely a WordPress enhancement plugin anymore.

It is effectively a **business application backend implemented inside WordPress**.

That explains the growing complexity around:

`React → REST → WordPress auth → cookies → nonce → CORS → CPT → post meta → theme/public rendering`.

The standalone platform should invert this relationship:

`Business domain → database → backend/API → authorization → React UI/public website`.

## 22. Stage 0 migration classification

| Plugin responsibility | Decision |
|---|---|
| Shipment data model | REBUILD |
| Shipment tracking | REBUILD |
| Shipment notifications | REBUILD |
| Inquiry management | REBUILD |
| Department routing | REBUILD |
| Department permissions | REBUILD |
| Trade lanes | REBUILD / REVIEW |
| Jobs | REVIEW |
| Services | REBUILD SELECTIVELY |
| Team | REBUILD SELECTIVELY |
| FAQs | REBUILD SELECTIVELY |
| REST API | REPLACE |
| WordPress authentication | REMOVE |
| CORS bridge | REMOVE |
| WordPress SEO metadata | REPLACE |
| SEO analysis | REBUILD |
| CSV import/export | KEEP CONCEPT / REBUILD |
| Admin widgets | REBUILD AS DASHBOARD UI |
| WordPress Customizer settings | REBUILD AS APPLICATION SETTINGS |
| WordPress hooks/events | REPLACE WITH APPLICATION EVENTS |
| WordPress post meta | REPLACE WITH RELATIONAL DATA |

## 23. Stage 0 acceptance status

- [x] Plugin component inventory documented
- [x] CPT/domain inventory documented
- [x] Shipment model documented
- [x] Inquiry model/workflow documented
- [x] Department model documented
- [x] Authentication dependency documented
- [x] REST dependency documented
- [x] SEO component documented
- [x] Email/notification system documented
- [x] CSV migration tools documented
- [x] Security observations documented
- [x] Migration classification assigned
- [ ] Theme audit
- [ ] Full database/data map
- [ ] Public website audit verification
- [ ] API contract
- [ ] Final standalone architecture approval
