# Stage 0 — API & Integration Audit

## Core finding
The existing React dashboard is a WordPress REST client. The target platform replaces WordPress REST/CPT CRUD with an application-owned, versioned API and explicit domain services.

## Target API families
- /api/v1/auth/*
- /api/v1/shipments/*
- /api/v1/tracking/:trackingNumber
- /api/v1/inquiries/*
- /api/v1/insights/*
- /api/v1/media/*
- /api/v1/services/*
- /api/v1/team/*
- /api/v1/faqs/*
- /api/v1/trade-lanes/*
- /api/v1/jobs/*
- /api/v1/users/*
- /api/v1/settings/*

## Authentication
Replace wp_signon, WP cookies and REST nonces with application-owned secure HTTP-only sessions. Registration does not automatically grant privileged dashboard access.

## Authorization
RBAC and department/resource permissions are enforced server-side before domain operations.

## Integrations
- Media uses a storage abstraction.
- Email uses an internal mail service abstraction connected to a transactional provider.
- Analytics is configuration-driven.
- CORS is restrictive if cross-origin deployment is used.

## API conventions
Use explicit validation, stable error codes, pagination metadata for collections, and separate private/public representations. The frontend must not decide authorization or receive internal fields simply because they exist in the database.

## Removed dependencies
- /wp-json/wp/v2 as the application API
- WP REST nonces
- generic restBase CRUD
- WordPress authentication/session semantics
- CORS bridge created solely to make WP cookies work across the dashboard boundary
