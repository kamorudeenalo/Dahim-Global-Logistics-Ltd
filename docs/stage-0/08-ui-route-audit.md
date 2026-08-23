# Stage 0 — UI & Route Audit

## Dashboard information architecture
- Overview
- Operations: Shipments, Inquiries
- Content: Insights, Media
- Company: Services, Trade Lanes, Team, FAQs, Careers
- System: Users & Roles, Settings, Activity Log

## Dashboard route direction
- /dashboard
- /dashboard/shipments[/new|/:id]
- /dashboard/inquiries[/:id]
- /dashboard/insights[/new|/:id]
- /dashboard/media
- /dashboard/services[/:id]
- /dashboard/team[/:id]
- /dashboard/faqs[/:id]
- /dashboard/trade-lanes[/:id]
- /dashboard/jobs[/:id]
- /dashboard/users[/:id]
- /dashboard/settings
- /dashboard/activity

## Public route direction
- /
- /about
- /services
- /services/:slug
- /track
- /insights
- /insights/:slug
- /contact
- /careers (if jobs retained)

## UX principles
- Business-domain language instead of WordPress terminology.
- Reusable tables, forms, status badges, dialogs, media picker, rich-text editor, timeline and SEO components.
- Responsive layouts designed intentionally for mobile rather than merely shrinking desktop tables.
- Insights publishing remains simple.
- SEO score and analysis are reusable application features.
- Activity Log tracks business-data changes; GitHub tracks software changes.

## Dashboard overview
The overview should surface useful operational metrics, shipment status distribution, inquiry workload, recent activity and other server-derived insights rather than calculating all business intelligence ad hoc in the browser.
