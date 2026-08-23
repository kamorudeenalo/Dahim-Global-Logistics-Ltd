# Stage 0 — Data Model Audit

## Core finding
The current system uses WordPress posts/CPTs/post-meta as a generic persistence layer. The standalone platform will replace that with explicit relational domain models in PostgreSQL.

## Target entities
- users, roles, permissions, user_roles, role_permissions
- departments and user_department_access
- shipments and shipment_events
- inquiries and inquiry_events
- insights and insight_seo
- categories and tags
- services
- team_members
- faqs
- trade_lanes
- jobs (optional module)
- media
- site_settings
- audit_logs

## Shipment principles
Shipments receive explicit columns for tracking, customer/consignee, route, cargo, service, status and dates. Status history is stored as shipment events. Cancellation is a state/action, not deletion. Public tracking uses a restricted DTO and never exposes private operational/customer fields by default.

## Inquiry principles
Inquiries retain contact, department, service, message, status and assignment data. Changes that matter operationally are recorded as events/audit records. Department visibility is enforced server-side.

## Content principles
Insights remain lightweight and SEO-focused. SEO metadata is modeled explicitly. Media owns alt text and other image metadata; there is no separate cover-alt concept.

## Migration principle
Do not reproduce wp_posts/wp_postmeta. Extract, normalize, deduplicate, validate and map source data into the new relational schema. Preserve source IDs only in migration mapping/audit records, not as the new application's primary identity strategy.
