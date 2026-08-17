# Dahim Global Logistics — WordPress Theme

A custom theme built entirely with core WordPress APIs. No page builder, no third-party plugins required.

## For the site owner — what you can manage yourself

Once this is installed, day-to-day management needs **no developer and no code**:
- **Colors** — Appearance → Customize → Brand Colors. Change 4 color pickers, the whole site updates.
- **Logo & Favicon** — Appearance → Customize → Site Identity (header logo) and Footer Logo section, plus Settings → General → Site Icon (favicon).
- **Text content on every page** — edit the actual Page in wp-admin (Pages → About Dahim, etc.) or the relevant custom post type (Services, Team, FAQs, Trade Lanes) the same way you'd edit any WordPress post.
- **New pages** — Pages → Add New. Works immediately with the site's full styling, even without picking a special template.
- **Blog posts** — Posts → Add New, exactly like any WordPress blog.
- **Navigation menu** — Appearance → Menus, drag and drop.

**Honest limitation:** this is a hand-coded theme, not a drag-and-drop page builder — so *where things sit on the page* (e.g. moving the FAQ section above the services grid) still means editing a template file, which is a developer task. If full drag-and-drop layout control ever becomes a priority, the usual path is adding a page-builder plugin (e.g. the free version of Elementor) on top of this theme later — that's a bigger decision worth making deliberately rather than something baked in by default, so it isn't included here.

## Fixing the sticky header

The header used CSS `position: sticky`, which is fragile in real-world WordPress hosting: it silently breaks (showing exactly the "half stuck" clipping you recorded) if *any* ancestor element gets `overflow` or a `transform` set on it — something caching/performance plugins add routinely, often without the site owner ever seeing it happen. This theme now uses a small dependency-free JavaScript toggle to `position: fixed` instead (`assets/js/sticky-header.js`), which isn't susceptible to that failure mode, and also correctly steps below the WordPress admin toolbar when you're logged in.

## 1. Install

1. Zip the `dahim` folder (this folder), or upload it as-is via FTP to `wp-content/themes/dahim`.
2. In wp-admin: **Appearance → Themes → Add New → Upload Theme** (if using the zip), then **Activate**.

## 2. Create your pages

Go to **Pages → Add New** and create these five pages. For each, set the **Page Attributes → Template** dropdown (right sidebar) to match:

| Page title       | Slug       | Template            |
|-------------------|------------|----------------------|
| Home              | `/`        | *(leave as Default — front-page.php is used automatically once set as homepage)* |
| About Dahim       | `about`    | About Dahim          |
| Our Services      | `services` | Our Services          |
| Contact Us        | `contact`  | Contact Us            |
| Track a Shipment  | `track`    | Track a Shipment      |

Then go to **Settings → Reading** and set **Homepage displays → A static page**, with **Homepage** = your Home page.

## 3. Set up the menu

**Appearance → Menus** → create a menu, add the 5 pages above, assign it to **Primary Menu**. The "Our Services" dropdown (What We Do / Industries We Service) is built automatically by the theme when a page with slug `services` is in the menu — no extra setup needed there, though you can also add custom sub-links manually if preferred.

## 4. Add your content

The theme ships with sensible fallback content pulled from the current site, so nothing breaks if these are empty — but for full control, use:

- **Services** (left admin menu) — the 6 "What We Do" cards. Add a featured image, title, and description (post content) for each. Order them with the **Order** field under Page Attributes.
- **Trade Lanes** — the "Key routes" rows. Fill in Origin, Destination, Mode, and Transit Time in the box under the editor.
- **FAQs** — homepage FAQ accordion. Title = question, content = answer.
- **Our Team** — team member cards on the About page. Add a photo, name (title), role (side box), and short bio (content).
- **Shipments** — powers the real Track a Shipment lookup. Add a shipment with its Tracking Number and current Stage (1–4); a client entering that number on `/track/` will see live status.

## 5. Site-wide settings — Appearance → Customize

Everything here updates with a live preview before you even click Publish:

- **Brand Colors** — four color pickers (Navy, Gold, WhatsApp Green, Background). Change any of them and the *entire site* repaints — buttons, headers, footer, accents — because every color in the CSS is built from these four values. No code, no per-page editing.
- **Site Identity** (a built-in WordPress section) — upload the **header logo** here by clicking "Select logo". Also where the **Site Title/Tagline** live (used for browser tab text and SEO, even though they're not visually shown on the page).
- **Footer Logo** — a separate upload, since the footer background is dark navy and needs a light-colored version of the logo.
- **Contact Info** — phone numbers, WhatsApp number, emails, address (used in the header bar, footer, and Contact page).
- **Homepage Stats** — the four stat numbers/labels.
- **Homepage Hero** — eyebrow text, headline, paragraph, hero image.
- **About Page Content** — mission, vision, company overview, founder bio paragraph.

**Favicon:** go to **Settings → General → Site Icon** (a separate, standard WordPress screen) and upload a square image — WordPress handles all the browser-tab and mobile-icon sizes automatically.

## 6. Contact form email delivery

The contact form on `/contact/` submits through WordPress's built-in `wp_mail()` — no plugin required. **Note:** many hosts don't reliably deliver mail via PHP's default `mail()` function, which `wp_mail()` uses out of the box. If quote request emails aren't arriving, ask your host to enable SMTP delivery at the server level (most managed WP hosts, including hosting typical for `.com.ng` domains, offer this) rather than adding an SMTP plugin.

## 7. Images

All images live in `assets/images/` inside the theme. To replace any of them (e.g. once you have real photography for the Import & Export Logistics or Oil & Gas service cards), either:
- Swap the file in `assets/images/` keeping the same filename, or
- Upload a new image to the Media Library and set it as the featured image on the relevant Service post (this overrides the fallback automatically).

## 8. Blog / Insights

The theme includes a full blog, built on WordPress's native Posts (not a custom post type), so writers can use the normal **Posts → Add New** screen with categories, tags, and featured images.

**Setup (one-time):**
1. Go to **Pages → Add New**, title it "Insights", and **Publish** it with no content (it's just a placeholder — the theme's `home.php` template renders the actual listing automatically).
2. Go to **Settings → Reading** and set **Posts page** to that "Insights" page.
3. Go to **Appearance → Menus** and add "Insights" to your Primary Menu.

**Writing a post:**
- **Posts → Add New**. Set a **Category** (used as the filter pills on the listing page and the label on related-post cards), add a **Featured Image** (used on cards and the article hero banner), and write in the normal block editor.
- Reading time is calculated automatically from word count — no field to fill in.
- The 2-column "Discover More Insights" block on each article pulls related posts from the same category automatically.

**Templates involved:** `home.php` (listing + category filter pills), `category.php` (filtered by category), `single.php` (individual article), `template-parts/insights-grid.php` (shared card grid, used by both listing templates).

## 9. Adding a page builder (Elementor, free)

This is the one thing that genuinely needs a few clicks inside wp-admin — no file can do this part for you, since installing a plugin happens on the live site itself.

**Install it (2 minutes, no technical knowledge needed):**
1. In wp-admin, go to **Plugins → Add New**
2. Search "**Elementor**" (by Elementor.com — it'll be the top result, several million active installs)
3. Click **Install Now**, then **Activate**
4. That's it — Elementor is free with no account or payment required for the core page builder

**How it works with this theme:**
- **New pages** — Pages → Add New → you'll see a blue **"Edit with Elementor"** button. Click it and you get the full drag-and-drop visual editor.
- For a new Elementor page, set **Page Attributes → Template** to **"Full Width (Blank)"** (added by this theme) so Elementor's own layout controls aren't fighting the site's default content width. Elementor also auto-adds its own "Elementor Canvas" (no header/footer at all) and "Elementor Full Width" templates to that same dropdown — Full Width (Blank) is the right choice for a page that should still show this site's header, footer, and WhatsApp button.
- **Existing pages** (Home, About Dahim, Our Services, Contact Us, Track a Shipment) were built as hand-coded templates, not from post content — so Elementor won't be able to visually edit those specific five pages as-is. This is intentional: they're wired into the Customizer, custom post types, and the contact/tracking form logic, and converting them to Elementor would mean rebuilding all of that inside the builder instead. If you eventually want one of those five redesigned in Elementor, the clean approach is to duplicate it as a new page, build the new version there, and swap it in — rather than converting the original in place.
- **Everything else** (Services, Team, FAQs, Trade Lanes, Blog posts) still works exactly as before — Elementor doesn't change how those are managed.

## File map

```
dahim/
├── style.css              → theme header + full design system CSS
├── functions.php          → CPTs, meta boxes, Customizer, contact form handler, tracking lookup, blog helpers
├── header.php / footer.php
├── front-page.php         → Home
├── page-about.php         → About Dahim (Template: About Dahim)
├── page-services.php      → Our Services (Template: Our Services)
├── page-contact.php       → Contact Us (Template: Contact Us)
├── page-track.php         → Track a Shipment (Template: Track a Shipment)
├── page-blank.php         → Full Width (Blank) — for Elementor-built pages
├── home.php               → Insights blog listing (auto-used as the WP "Posts page")
├── category.php           → Insights filtered by category
├── single.php              → Individual Insights article
├── single-service.php     → individual service detail view
├── page.php / index.php   → fallback templates (required by WordPress)
├── template-parts/
│   └── insights-grid.php  → shared card-grid loop for home.php + category.php
├── assets/
│   ├── images/            → logo, favicon, and photography
│   └── js/
│       ├── sticky-header.js       → robust fixed-header behavior
│       └── customizer-preview.js  → live color preview in Appearance → Customize
```

## 10. If you update the theme and the site doesn't seem to change

As of v1.1, `style.css` and every JS file are enqueued with a cache-busting version number generated automatically from each file's last-modified time (`filemtime()`), so this specific problem should no longer happen going forward — every future edit automatically forces browsers to fetch the fresh file.

If you're still seeing stale styling after an update:
1. **Fully replace the theme folder** — don't upload new files on top of old ones. Delete `wp-content/themes/dahim` entirely on the server first, then upload the fresh zip.
2. **Clear any caching plugin or host-level cache** (LiteSpeed Cache, WP Rocket, SiteGround/Namecheap/etc. hosting cache) — extremely common on shared hosting, and the single most frequent cause of "I updated it but nothing changed."
3. **Hard-refresh your browser** (Ctrl+Shift+R / Cmd+Shift+R) to rule out local browser cache.


## Team Members

The About page reads the Team Members custom post type exclusively. There is no hard-coded team fallback in the theme. Manage names, roles, biographies, ordering, and featured images from **WP Admin → Our Team**.
