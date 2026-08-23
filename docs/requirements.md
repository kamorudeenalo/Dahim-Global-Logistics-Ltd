# Requirements

## Functional requirements

### Public website

- Home page
- About page
- Services pages
- Shipment tracking page
- Insights listing
- Individual Insight pages
- Contact/inquiry forms
- Responsive mobile experience
- Search-engine-friendly rendering

### Authentication

- Login
- Logout
- Password reset
- User sessions
- Role-based access control
- Secure password storage
- Session expiration and revocation

### Dashboard

- Overview/insights dashboard
- Navigation and centralized branding
- User/account controls
- Content management
- Media management
- Shipment management
- Inquiry management
- Settings
- Analytics

### Insights

Insights are an SEO/content feature, not a publishing-firm workflow.

Required fields should remain simple:

- Title
- Slug
- Excerpt
- Content
- Featured image
- Category
- Tags where required
- Status
- Publish date
- SEO title
- Meta description
- Focus keyword
- Canonical URL
- Social metadata where required

No contributor workflow unless a future requirement explicitly introduces it.

### SEO

The application must provide a native SEO system inspired by the usability of AIOSEO, without depending on AIOSEO or WordPress.

Required areas:

- SEO score
- Basic SEO checks
- SEO title analysis
- Meta description analysis
- Focus keyword analysis
- URL/slug analysis
- Content analysis
- Heading analysis
- Internal links
- External links
- Image alt-text checks
- Readability checks
- Search preview
- Social preview/metadata
- Canonical URL
- Structured data/schema

An empty new Insight must start at a neutral SEO score rather than receiving points for empty/default fields.

### Media

- Upload images
- Media library
- Featured images
- Inserted images
- Alt text per image
- Captions where required
- Image metadata

There will be no separate cover-alt field.

### Shipments

- Create shipment
- Edit shipment
- Track shipment
- Shipment status
- Origin/destination
- Customer information
- Delivery information
- Operational/private fields
- Privacy/access controls

### Inquiries

- Receive inquiries
- Assign/route inquiries
- Status management
- Department/team access controls
- Internal notes where required
- Response tracking

## Non-functional requirements

- Secure by default
- Responsive
- Accessible
- Fast public pages
- SEO-friendly
- Maintainable TypeScript codebase
- Documented architecture
- Automated build/test checks
- Database migrations tracked in Git
- Environment secrets never committed
- Production changes traceable to Git commits/releases
- Backups and rollback strategy

## Acceptance principle

A feature is not complete until its implementation, tests, documentation, and change history are updated.
