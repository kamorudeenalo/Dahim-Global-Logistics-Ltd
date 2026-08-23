# Migration Plan

## Goal

Move from the current WordPress/theme/plugin/dashboard architecture to the standalone Dahim platform without losing required business data or public content.

## Migration phases

### Phase A — Inventory

- WordPress pages
- Insights/posts
- Categories/tags
- SEO metadata
- Media
- Users/roles
- Shipments
- Inquiries
- Site settings
- Existing URLs

### Phase B — Data mapping

Create an explicit mapping from each WordPress data structure to the new PostgreSQL schema.

Example:

```text
WordPress Post
  -> Insight

WP post title
  -> insight.title

WP post_name
  -> insight.slug

WP post_content
  -> insight.content

Dahim SEO title metadata
  -> insight.seo_title

Dahim meta description metadata
  -> insight.meta_description
```

No data is migrated based on assumptions. Unknown or obsolete fields must be reviewed first.

### Phase C — New platform implementation

Build and test the new system independently of production WordPress.

### Phase D — Migration tooling

Create repeatable import/export scripts. Migration must be rerunnable and auditable.

### Phase E — Staging verification

Verify:

- URL coverage
- Content integrity
- Images
- SEO metadata
- Redirects
- Authentication
- Shipments
- Inquiries
- Permissions

### Phase F — Cutover

- Freeze content changes during final migration window.
- Export final WordPress data.
- Import into the new platform.
- Verify critical records.
- Switch production traffic.
- Monitor.

### Phase G — Decommission

WordPress is not removed immediately. Keep a verified backup and rollback copy until the new platform has passed the agreed stability period.

## Rollback principle

The migration must have a documented rollback path before production cutover.
