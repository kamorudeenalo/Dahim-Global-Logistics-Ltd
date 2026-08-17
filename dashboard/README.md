# Dahim Dashboard

A standalone, mobile-friendly, installable (PWA) dashboard for managing the
Dahim Global Logistics website — Shipments, Inquiries, Trade Lanes,
Departments, Jobs, and Insights posts — without ever touching wp-admin.

It's a static React app. It has no server of its own — everything it shows
and saves goes straight to your WordPress site's REST API. That means it
can be hosted on plain shared hosting (no Node.js required on the server).

## What's in this folder

- `dist/` — the **ready-to-deploy build**. This is what you upload to your host.
- `src/`, `index.html`, `package.json`, etc. — the source code, for making
  changes and rebuilding later.

---

## Part 1 — One-time WordPress setup

1. Install and activate **both** the Dahim theme (v5.0+) **and** the
   "Dahim Dashboard" plugin — the plugin is what actually provides
   Shipments, Inquiries, Departments, Jobs, the contact form, and the
   REST API this dashboard talks to. The theme is the public website's
   look; the plugin is the data and logic underneath, kept deliberately
   separate so it survives a future theme change instead of disappearing
   with one. **Both are required — the theme alone isn't enough.**
2. For each person who needs dashboard access: they just need a normal
   WordPress account — the same one they'd use to log into wp-admin.
   Nothing extra to generate.
3. Whoever needs to edit **Settings** (contact phone/email/address) in
   the dashboard needs an **Administrator** WordPress account — everyone
   else (Editor role or above) can manage Shipments, Inquiries, Jobs,
   Trade Lanes, Departments, and Posts.
4. New people can also request access themselves from the dashboard's
   own **Create an account** link — they land as a harmless, no-access
   account until an admin gives them a real role from wp-admin → Users
   (that role change *is* the approval — nothing else to click).

## Part 2 — Deploying to cPanel / shared hosting (Namecheap)

The dashboard now lives on the **same domain** as your WordPress site, in
a `/dashboard/` folder — no separate subdomain, no CORS setup, and no
"which URL do I put here" confusion, since it's always talking to the
same site it's hosted on.

1. In cPanel **File Manager**, go into your main site's web root
   (typically `public_html/`, or a subfolder if WordPress isn't at the
   domain root) and create a new folder named exactly **`dashboard`**.
2. **Upload the contents of `dist/`** (not the `dist` folder itself — its
   *contents*: `index.html`, `assets/`, `icons/`, `.htaccess`, etc.) into
   that `dashboard` folder. File Manager can't upload folders directly,
   so zip the contents on your computer first, upload that one zip file,
   then right-click it in File Manager and choose **Extract**.
   - Uploading `.htaccess` matters — it's what makes page refreshes and
     direct links (e.g. `yoursite.com/dashboard/shipments`) work
     correctly instead of 404ing. Enable "Show Hidden Files" in File
     Manager's settings if you don't see it after extracting.
3. Visit `https://yoursite.com/dashboard/` — you should see the sign-in screen.

That's it — no subdomain to create, no separate SSL certificate to set
up, since it rides on the main site's existing HTTPS.

## Part 3 — Signing in

- **Username**: your normal WordPress username
- **Password**: your normal WordPress password — the same one for wp-admin

There's no "Site URL" field to fill in — since the dashboard lives on the
same domain as the WordPress site, it always knows which site to talk to.
There's no separate password to generate either — this app now uses
WordPress's own standard login (the same mechanism wp-admin itself uses),
not a custom credential.

An earlier version of this dashboard used a hand-built token system
specifically to work around this host (LiteSpeed) stripping the
`Authorization` HTTP header, which otherwise breaks WordPress's normal
login flow. That fix worked, but caused a much bigger problem: since it
deliberately never set a real WordPress login cookie, no caching layer on
the host — including LiteSpeed Cache, which is active here — had any way
to recognize these requests as personalized, and could cache and replay
one person's authenticated response to someone else, or serve a stale
response from before a session even existed. That was the real cause
behind sessions randomly appearing to break after working fine initially.

Switching to WordPress's own cookie + nonce authentication (the same
thing wp-admin's own JavaScript uses) fixes this at the root: it's the
exact signal every caching plugin already knows to check for, and it
never touches the `Authorization` header at all, so the original
stripping issue doesn't apply either. Nothing is stored in this browser's
localStorage for authentication anymore — signing in sets a normal
WordPress session, exactly like visiting wp-admin would.

## Installing it like an app

Once deployed and opened once in a mobile browser (Chrome/Edge on
Android, Safari on iOS), the browser will offer an **"Add to Home
Screen" / "Install"** prompt. Installed, it opens full-screen with no
browser address bar — same as a native app, no app store involved.

---

## Making changes / rebuilding

Requires [Node.js](https://nodejs.org) installed on your own computer
(not the server).

```bash
npm install       # first time only
npm run dev       # local development server, live-reloads on save
npm run build     # produces a fresh dist/ folder to re-upload
```

After `npm run build`, re-upload the new contents of `dist/` to the
`dashboard` folder on your host, replacing what's there.

## What this dashboard does NOT do (by design)

- **Layout, colors, and page design** — that's the WordPress theme's job,
  changed by a developer, not from here.
- **Rich-text/WYSIWYG editing** for Insights posts and Job descriptions —
  the editor here is plain text. HTML formatting from wp-admin's editor
  is preserved when just *saving* other fields, but new content typed
  here is plain paragraphs. A rich editor can be added later if wanted.
- **Media/image uploads** — featured images for posts/jobs still need to
  be set from wp-admin for now.
- **Site-wide settings** like the homepage hero image, theme colors, or
  analytics ID — those stay in Appearance → Customize in wp-admin.


## v1.0.4 — Content & Shipment Creation
- Shipment creation now includes a WordPress post title.
- Insights post creation supports title, slug, publish date, content, excerpt, categories, tags, author, status, visibility/password, comments, pingbacks, featured image upload, and existing Media Library selection.
- Categories and tags can be created directly from the post form.
- Featured images are uploaded through the native WordPress Media REST API.
- The production `dist/` build includes a dependency-free creation enhancement layer so the new creation UI works immediately even when replacing an existing deployed build.
