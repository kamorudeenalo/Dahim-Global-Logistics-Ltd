# Current System Audit

## Purpose

Document what exists before replacing the WordPress-dependent architecture.

## Current architecture

```text
Public Website / WordPress Theme
            |
            v
      WordPress Core
            |
      +-----+------+
      |            |
      v            v
Dahim Plugin   WordPress DB
      |
      v
Dahim REST API
      |
      v
React/Vite Dashboard
```

The existing Git history confirms that the current implementation has evolved through WordPress REST authentication, dashboard routing, custom Insights, SEO metadata, and dashboard fixes. Examples include the dashboard WordPress API origin, cross-subdomain authentication, custom Insights publishing, SEO fields, and dashboard build configuration. See the commit history for these changes.

## Current major components

### WordPress theme

Responsible for public-site presentation and WordPress-side rendering.

### Dahim WordPress plugin

Responsible for custom post types, metadata, REST endpoints, SEO integration, authentication bridge work, and business-specific functionality.

### React dashboard

Responsible for the administrative interface, including authentication screens, navigation, Insights management, media handling, and other dashboard features.

### WordPress database

Acts as the persistence layer for WordPress content, users, custom post types, and metadata.

## Current pain points identified

1. Authentication depends on WordPress cookies, REST nonces, CORS, and plugin-created REST routes.
2. Dashboard and WordPress have overlapping responsibilities.
3. SEO logic is split between dashboard UI, plugin logic, and public rendering.
4. Insights publishing is more complex than the business requirement requires.
5. Theme and plugin must remain synchronized with dashboard assumptions.
6. Production behaviour can diverge from the React source because the dashboard is built and deployed separately.
7. Media, content, business data, and authentication are coupled to WordPress conventions.

## Target of the audit

Before migration, each existing feature will be mapped to one of:

- Keep as-is conceptually
- Rebuild in the standalone platform
- Simplify
- Replace
- Remove
- Migrate as data only

This mapping will be completed before Stage 1 implementation begins.
