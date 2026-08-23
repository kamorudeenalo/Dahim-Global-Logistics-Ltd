# Stage 0.2 — Current Dashboard Audit

**Status:** Audited  
**Source:** `dashboard/` on `stage-0/foundation`  
**Purpose:** Record the current dashboard before rebuilding it as part of the standalone Dahim platform.

## 1. Current dashboard architecture

The current dashboard is a React single-page application using React Router. The route tree currently provides authentication screens plus authenticated operational pages. The application wraps the router with `AuthProvider` and `ToastProvider`, then protects authenticated routes with `RequireAuth`. `RedirectIfAuthed` prevents authenticated users from opening login/register/recovery screens. See `dashboard/src/App.jsx`.

Current authenticated areas:

- Overview
- Inquiries
- Shipments
- Trade Lanes
- Departments
- Jobs
- Posts / Insights
- Settings

Current authentication screens:

- Login
- Register
- Forgot password
- Reset password

The current application is therefore already more than a simple content dashboard: it is an operational back office for inquiries, logistics, content and configuration.

## 2. Current authentication model

`AuthContext.jsx` maintains the current user and a startup `checking` state. It restores the session on application load, exposes login/logout actions, and listens for a global unauthorized event to clear the user.

The current implementation depends on the WordPress-backed API and WordPress session semantics. This is a migration dependency, not a target architecture requirement.

### Target decision

**REBUILD** authentication as native application authentication in the standalone platform.

The new system should use secure server-managed sessions/HTTP-only cookies and application roles/permissions. It must not depend on WordPress cookies, REST nonces, or a WordPress plugin.

## 3. Overview/dashboard insights

The current Overview page loads inquiries and shipments and derives operational statistics in the client.

### Inquiry metrics currently shown

- Total inquiries
- New inquiries
- Contacted inquiries
- Closed / converted inquiries
- Recent inquiries

### Shipment metrics currently shown

- Total shipments
- Booked
- Cleared / picked up
- In transit
- Out for delivery
- Delivered

### Current limitation

The overview currently requests up to 100 inquiries and 100 shipments and calculates counts in the browser. This is acceptable for the current WordPress implementation but should not be the target design.

### Target decision

**REBUILD + IMPROVE**.

The standalone backend should provide aggregated dashboard metrics from the database. The dashboard should not download large collections just to count records.

## 4. Inquiries

The dashboard contains an Inquiries area and a detail route (`/inquiries/:id`). It is part of the operational workflow and should remain a first-class feature.

The current implementation is WordPress/CPT/meta dependent and includes department/status concepts.

### Target model

An inquiry should become a native application entity with fields for:

- Customer/contact information
- Inquiry type/service
- Department
- Status
- Assignment
- Message/details
- Internal notes
- Created/updated timestamps
- Response history where required

### Target decision

**REBUILD**.

Department access and inquiry privacy must be enforced server-side, not only in the UI.

## 5. Shipments

The dashboard contains a dedicated Shipments area. The current overview derives shipment state from internal metadata/stages.

Current operational stages observed in the dashboard:

1. Booked
2. Cleared / picked up
3. In transit
4. Out for delivery
5. Delivered

### Target model

Shipments should become a native domain model with explicit status values and a proper status/history structure where required. Customer-facing tracking should use a safe public tracking identifier and should never expose private operational/customer fields.

### Target decision

**REBUILD**.

Do not reproduce WordPress post metadata as the new database design.

## 6. Trade lanes

The current dashboard has a Trade Lanes page.

### Target decision

**REBUILD** as a native configuration/data entity if the business still uses trade lanes operationally.

## 7. Departments

The current dashboard has a Departments page. Departments are also relevant to inquiry routing and permissions.

### Target decision

**REBUILD** as a native entity tied to users/roles and inquiry assignment rules.

## 8. Jobs

The current dashboard has a Jobs page. This indicates a recruitment/job-management capability exists in the current platform.

### Target decision

**REVIEW → REBUILD OR SIMPLIFY**.

Before Stage 1 implementation, confirm whether Jobs is a live business requirement, a legacy feature, or a feature to retain in a simplified form.

## 9. Insights / content management

The current Posts page is the largest dashboard module. It includes a dedicated Insight editor and management workflow.

Observed capabilities include:

- List Insights
- Search/filter
- Status tabs
- Create Insight
- Edit Insight
- Draft
- Publish
- Schedule
- Trash/delete workflows
- Categories
- Tags
- Author selection
- Slug generation/manual slug editing
- Excerpt
- Content editor
- Featured image upload
- SEO title
- Meta description
- Focus keyword
- Public Insight preview/page detection
- Site timezone handling

The editor uses a custom rich-text editor component.

### Business requirement

Dahim is not a publishing house. Insights exist primarily as a company content/SEO channel.

Therefore the standalone implementation should deliberately be simpler than WordPress/Gutenberg/AIOSEO.

### Target decision

**REBUILD + SIMPLIFY**.

The new Insight entity should focus on:

- Title
- Slug
- Excerpt
- Content
- Featured image
- Category (only if needed)
- Tags (only if useful)
- Publication status/date
- SEO metadata
- Social metadata
- Author attribution only if the business requires it

Contributor/editorial complexity should not be recreated unless explicitly required.

## 10. SEO

The current dashboard has SEO fields integrated into the Insight editor. The current implementation is WordPress meta driven.

Required target SEO capabilities previously agreed for Dahim include:

- SEO score
- Focus keyword
- SEO title
- Meta description
- URL/slug analysis
- Content analysis
- Heading structure
- Internal links
- External links
- Image alt text
- Readability analysis
- Search preview
- Social metadata
- Canonical URL
- Appropriate structured data/schema

The target UX should be inspired by the clarity of AIOSEO without reproducing WordPress/AIOSEO architecture.

### Target decision

**REBUILD** as a native SEO analysis engine and metadata layer.

The score must be calculated from the actual current editor state and should not start with arbitrary points on an empty Insight.

## 11. Media

The current Insight editor supports featured image upload and the broader dashboard has media-related functionality through the API layer.

### Target decision

**REBUILD** a native media library.

Required capabilities:

- Upload
- Select existing media
- Featured image
- Image dimensions/type
- Alt text
- Caption where needed
- File URL/storage reference
- Ownership/audit information

There should be no separate "cover alt" concept. Featured and inserted images should use normal image metadata.

## 12. Settings

The current dashboard contains a Settings page. It also consumes site settings such as timezone and public posts-page information.

### Target decision

**REBUILD + SIMPLIFY**.

The standalone Settings area should contain only application/business settings that are actually required.

## 13. UI infrastructure

Current shared UI/application infrastructure includes:

- Layout
- Logo component
- Toast notifications
- Error boundary
- Modal
- Password field
- Rich text editor
- Utility functions
- API client
- CSS/design system

### Target decision

**REUSE CONCEPTS, REBUILD IMPLEMENTATION**.

The existing visual language and useful UX patterns can inform the new application, but components should be rebuilt against the standalone data/API architecture rather than preserving WordPress assumptions.

## 14. Current API dependency

The dashboard currently depends heavily on a WordPress API client (`dashboard/src/api.js`). The application expects WordPress-style resource endpoints and authentication behavior.

This is the primary architectural dependency to remove.

### Target API architecture

The new application should expose domain-specific APIs such as:

- `/api/auth/*`
- `/api/dashboard/*`
- `/api/insights/*`
- `/api/seo/*` where appropriate
- `/api/media/*`
- `/api/shipments/*`
- `/api/inquiries/*`
- `/api/departments/*`
- `/api/trade-lanes/*`
- `/api/jobs/*` if retained
- `/api/settings/*`

The exact API contract will be defined during architecture/database design.

## 15. Current dashboard route inventory

| Current route | Feature | Target decision |
|---|---|---|
| `/login` | Authentication | REBUILD |
| `/register` | Authentication | REVIEW / REBUILD |
| `/forgot-password` | Password recovery | REBUILD |
| `/reset-password` | Password reset | REBUILD |
| `/` | Overview | REBUILD + IMPROVE |
| `/inquiries` | Inquiry management | REBUILD |
| `/inquiries/:id` | Inquiry detail | REBUILD |
| `/shipments` | Shipment management | REBUILD |
| `/trade-lanes` | Trade lanes | REBUILD / REVIEW |
| `/departments` | Departments | REBUILD |
| `/jobs` | Jobs/recruitment | REVIEW |
| `/posts` | Insights | REBUILD + SIMPLIFY |
| `/settings` | Settings | REBUILD + SIMPLIFY |

## 16. Key problems to eliminate in the standalone rebuild

1. WordPress REST API dependency.
2. WordPress cookie/nonce authentication.
3. Client-side aggregation of operational metrics.
4. WordPress post/meta data model used as business objects.
5. Tight coupling between content management and WordPress publishing features.
6. SEO implementation coupled to WordPress post metadata.
7. Potentially excessive editorial features for a logistics company.
8. UI logic having to compensate for WordPress API behavior.

## 17. Standalone target principle

The new dashboard should be a true application, not a WordPress administration replacement.

The target hierarchy is:

**Business domain → database → backend/API → authorization → dashboard UI**

rather than:

**WordPress post/CPT → WordPress meta → REST API → React workaround**.

## 18. Stage 0 acceptance items generated from this audit

- [x] Dashboard route inventory documented
- [x] Authentication dependency identified
- [x] Operational modules identified
- [x] Insights workflow documented
- [x] SEO requirements captured
- [x] Shipment workflow identified
- [x] Inquiry workflow identified
- [x] Media requirements identified
- [x] WordPress coupling identified
- [ ] Plugin data model audit
- [ ] Theme audit
- [ ] Existing database/data migration map
- [ ] Final standalone API contract
- [ ] Final architecture approval
