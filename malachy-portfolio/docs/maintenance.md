# Malachy Portfolio — WP Admin Maintenance Guide

**Version:** 1.1.0  
**Theme:** Malachy Portfolio  
**Domain:** https://imadconsult.zubbystudio.shop  
**WP Admin:** https://imadconsult.zubbystudio.shop/wp-admin

> All content management happens through the WordPress admin dashboard. No page builders, no ACF — just native WordPress.

---

## Table of Contents

1. [Dashboard Overview](#dashboard-overview)
2. [Managing Projects](#managing-projects)
3. [Managing Skills](#managing-skills)
4. [Managing Experience](#managing-experience)
5. [Managing Testimonials](#managing-testimonials)
6. [Managing Publications](#managing-publications)
7. [Theme Settings Page](#theme-settings-page)
8. [Taxonomies](#taxonomies)
9. [Blog Posts](#blog-posts)
10. [Menus](#menus)
11. [Contact Form](#contact-form)
12. [Permalink Settings](#permalink-settings)
13. [Content Migration & Backup](#content-migration--backup)

---

## Dashboard Overview

After logging into `/wp-admin`, the admin sidebar includes:

| Menu Item | Description |
|-----------|-------------|
| **Portfolio** | Theme-wide settings (portrait, subtitle, resume URL) |
| **Projects** | CPT — portfolio project entries |
| **Skills** | CPT — skill cards (8 on front page) |
| **Experience** | CPT — timeline entries |
| **Testimonials** | CPT — client/colleague testimonials |
| **Publications** | CPT — articles or publications |
| **Project Categories** | Hierarchical taxonomy for projects |
| **Technology Stack** | Tag-like taxonomy for tools/tech |
| **Skill Categories** | Hierarchical taxonomy for skills |
| **Posts** | Standard WordPress blog posts |
| **Appearance** | Theme management, menus, customizer |

---

## Managing Projects

Projects appear as stacked sticky cards in the portfolio's **Projects** section.

### Adding a Project

1. Go to **Projects → Add New**
2. Enter a **Title** (e.g., "Test Automation Framework")
3. Write an **Excerpt** (shown on the project card)
4. Fill in the **Project Details** meta box:

   | Field | Description |
   |-------|-------------|
   | **Tag / Category Label** | Short badge text, e.g. "QA" or "Web Dev" |
   | **Project URL** | Live URL for the project |
   | **GitHub URL** | Source code repository link |
   | **Technology Stack** | Add rows for each tech/tool used |

5. Set a **Featured Image** — this serves as the project screenshot/thumbnail
6. Use **Order** (Page Attributes → Order) to control the sequence:

   | Order | Behavior |
   |-------|----------|
   | 1 | Appears first (top card) |
   | 2 | Second card |
   | 3 | Third card |

7. **Publish**

### Editing or Removing Projects

- **Edit:** Projects → hover project → Edit
- **Delete:** Projects → hover project → Trash
- **Reorder:** Set different Order values, then visit the front page to confirm

### Project Categories

Assign projects to categories via **Project Categories** in the sidebar. This is a hierarchical taxonomy (like post categories).

---

## Managing Skills

Skills render as an 8-card grid in the **Skills** section.

### Adding a Skill

1. Go to **Skills → Add New**
2. Enter a **Title** (e.g., "QA Automation")
3. Write an **Excerpt** (shown as the skill description — e.g., "Playwright, Cypress, Pytest")
4. Fill in the **Skill Details** meta box:

   | Field | Description |
   |-------|-------------|
   | **Icon** | Dropdown — choose from: `Test`, `Server`, `Code`, `Docker`, `AI`, `WP`, `Git`, `Linux` |
   | **Skill Level (1-5)** | Numeric proficiency rating |

5. Use **Order** (Page Attributes) to control card position (1 = top-left, 8 = bottom-right)
6. **Publish**

> The front page renders up to 8 skills ordered by `menu_order`. Extra skills beyond 8 are not shown but can be used elsewhere.

### Skill Categories

Assign skills to groups via **Skill Categories** (hierarchical). Use for filtering or grouping if you extend the theme.

---

## Managing Experience

Experience entries build the **timeline** section.

### Adding Experience

1. Go to **Experience → Add New**
2. **Title** = job title (e.g., "Senior QA Engineer & Systems Consultant")
3. **Excerpt** = role description (shown on timeline)
4. Fill in the **Experience Details** meta box:

   | Field | Description |
   |-------|-------------|
   | **Organization / Company** | Employer name and location |
   | **Date / Year Range** | Date string — displayed verbatim |

5. Use **Order** to control timeline position (1 = most recent, top)
6. **Publish**

### Best Practices for Dates

- Use consistent formatting: `YYYY — YYYY` or `YYYY — Present`
- The date field is a plain text string — no date picker
- Example values: `2024 — Present`, `2021 — 2024`, `2017 — 2019`

---

## Managing Testimonials

Testimonials are registered but not rendered on the front page by default. They're available for future expansion.

1. Go to **Testimonials → Add New**
2. **Title** = person's name
3. **Editor** = testimonial text
4. **Testimonial Details** meta box:

   | Field | Description |
   |-------|-------------|
   | **Role / Title** | e.g., "CTO" |
   | **Organization** | e.g., "Acme Corp" |

5. **Featured Image** = person's photo (optional)

---

## Managing Publications

Publications are registered but not shown on the front page by default.

1. Go to **Publications → Add New**
2. Standard post fields (title, editor, excerpt, featured image)
3. Supports custom fields for extensibility

---

## Theme Settings Page

The **Portfolio** menu item in the admin sidebar opens the global settings page.

| Setting | Description |
|---------|-------------|
| **Portrait Image URL** | Full URL to the hero portrait image |
| **Hero Subtitle** | Text shown below "Hi, I'm Malachy" |
| **Resume/CV Download URL** | Link for the resume download button |

To update:

1. Go to **Portfolio** in the admin sidebar
2. Edit the desired fields
3. Click **Save Changes**

> These settings are stored as WordPress options (not post meta). They persist across theme updates.

---

## Taxonomies

### Project Categories (hierarchical)

**Dashboard → Project Categories**

Use to group projects. Similar to WordPress categories. Applies to the `project` CPT.

### Technology Stack (tags)

**Dashboard → Technology Stack**

Non-hierarchical tags for tools and technologies. Applies to both `project` and `experience` CPTs. Type to add new tags when editing a project — they auto-complete.

### Skill Categories (hierarchical)

**Dashboard → Skill Categories**

Group skills into categories. Available for extending the theme (e.g., grouping by "Frontend", "Backend", "DevOps").

---

## Blog Posts

The blog preview section on the front page shows the **3 most recent published posts**.

### Adding a Post

1. Go to **Posts → Add New**
2. Standard WordPress editor (classic editor — Gutenberg is disabled)
3. Add categories and tags as needed
4. Set a featured image for the blog card
5. **Publish**

### Blog Archive

The full blog listing is at `/blog` (uses `home.php` template). Posts appear in reverse chronological order.

---

## Menus

The primary navigation is **hardcoded** in `template-parts/navigation.php` for the single-page portfolio layout. It anchors to section IDs (`#hero`, `#about`, `#skills`, etc.).

If you need to customize nav links:

1. Go to **Appearance → Menus**
2. Create or edit a menu assigned to the **Primary Menu** location
3. The theme will use the custom menu if one is assigned, otherwise the hardcoded fallback applies

---

## Contact Form

The contact form at the bottom of the page works via:

- **REST API** (primary) — POST to `/wp-json/malachy/v1/contact`
- **admin-ajax** fallback — POST to `/wp-admin/admin-ajax.php`

### Changing the Email Recipient

By default, form submissions go to the WordPress admin email address. To change:

1. Go to **Settings → General**
2. Update **Administration Email Address**
3. Click **Save Changes**

Or override programmatically in `inc/contact-handler.php`:
```php
$to = get_option( 'admin_email' );  // Change this to a specific address
```

### Form Features

- Honeypot field (anti-spam, invisible to users)
- Nonce verification
- Rate limiting (configurable)
- Visual success/error feedback via toast message

---

## Permalink Settings

The theme uses `/%postname%/` permalink structure for clean URLs.

To verify or restore:

1. Go to **Settings → Permalinks**
2. Select **Post name**
3. Click **Save Changes** (this flushes the rewrite rules)

This is required after initial theme activation and recommended after adding new CPTs or taxonomies.

---

## Content Migration & Backup

### Exporting Content

Use **Tools → Export** to download an XML file containing all posts, CPTs, and meta data.

### Importing Content

On a new site:

1. Install the **WordPress Importer** plugin (Tools → Import)
2. Upload the export XML
3. Assign posts to existing users

### Database Backup (Docker environment)

```bash
# Backup
docker exec malachy-db mysqldump -u wordpress -pwordpress_pass malachy_portfolio > backup.sql

# Restore
cat backup.sql | docker exec -i malachy-db mysql -u wordpress -pwordpress_pass malachy_portfolio
```

---

## Troubleshooting

| Issue | Likely Cause | Fix |
|-------|--------------|-----|
| Skills not showing | No skills published or wrong menu_order | Add skills via Skills → Add New, set Order 1-8 |
| Contact form not emailing | No MTA in Docker | Works on real hosting with sendmail/postfix |
| 404 on project/skill pages | Permalinks not flushed | Settings → Permalinks → Save |
| Animations not working | Mobile device or `prefers-reduced-motion` | Check browser DevTools console for errors |
| Image not updating | Browser cache | Hard refresh (Ctrl+F5) or clear cache |
