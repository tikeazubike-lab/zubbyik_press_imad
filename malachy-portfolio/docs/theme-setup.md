# Theme Setup — Malachy Portfolio

How to install, configure, and customise the theme for a new WordPress site.

---

## Requirements

- **WordPress** 6.0+
- **PHP** 8.0+
- **MySQL** 8.0+ / MariaDB 10.4+
- **Browser:** Chrome 90+, Firefox 90+, Safari 15+, Edge 90+ (OKLCH color support)

---

## Installation

### Option A: Via WP Admin

1. Download the theme zip (`malachy-portfolio-v1.0.0.zip` from the `docs/` folder — or rebuild it with `bin/package.sh`).
2. Go to **Appearance → Themes → Add New → Upload Theme**.
3. Choose the `.zip` file and click **Install Now**.
4. Click **Activate**.

### Option B: Via cPanel / FTP

1. Extract the zip locally.
2. Upload the `malachy-portfolio/` folder to `wp-content/themes/`.
3. Go to **Appearance → Themes** and click **Activate**.

### Option C: Via WP-CLI (local dev)

```bash
# Already mounted at /var/www/html/wp-content/themes/malachy-portfolio/
# Just activate:
wp theme activate malachy-portfolio

# If running from theme root:
ln -s /path/to/malachy-portfolio /var/www/html/wp-content/themes/malachy-portfolio
wp theme activate malachy-portfolio
```

---

## First-Time Configuration

### 1. Set the Front Page

The theme uses `front-page.php` to render the single-page portfolio.

- Go to **Settings → Reading → Your homepage displays**.
- Ensure **Your latest posts** is selected (this triggers `front-page.php`).
- No static page selection is required.

### 2. Flush Permalinks

- Go to **Settings → Permalinks**.
- Select **Post name**.
- Click **Save Changes** (this flushes the rewrite rules for CPT archives and taxonomies).

### 3. Verify Menu

The primary navigation is hardcoded in `template-parts/navigation.php` — no menu assignment needed in Appearance → Menus.

To customise: edit the `$nav_links` array in `navigation.php` (lines 9–17).

### 4. Seed Demo Content (Optional)

The theme ships with a WP-CLI seeder that populates Projects, Skills, and Experience CPTs:

```bash
wp malachy seed
```

This creates:
- **3 projects:** Test Automation Framework, Infrastructure as Code, Custom WordPress Platform
- **4 experience entries:** Senior QA Engineer → QA Automation Engineer → Systems Administrator → IT Support Engineer
- **8 skills:** QA Automation, System Administration, Containerization, Scripting, Version Control, Linux Servers, WordPress, LLM & Agentic Coding

The seeder is idempotent — it skips posts whose titles already exist.

### 5. Update Theme Settings

Go to **Portfolio** in the WordPress admin sidebar to configure:

| Setting | Description | Default |
|---|---|---|
| Portrait Image URL | URL for the hero portrait image | `assets/images/malachy-portrait.webp` |
| Hero Subtitle | Eyebrow text above the hero heading | `Portfolio · 2026` |
| Resume/CV Download URL | Link for the "Download CV" CTA button | `#` |

### 6. Configure the Contact Form

The contact form delivers messages via `wp_mail()`.

- The recipient defaults to the WordPress admin email (`Settings → General → Administration Email Address`).
- To override the recipient, edit `inc/contact-handler.php` line 97:

```php
$to = get_option( 'admin_email', 'your@email.com' );
```

**Note:** Many shared hosts block `wp_mail()` or route it through an SMTP plugin. Install a mailer plugin (e.g., WP Mail SMTP, Post SMTP) if messages aren't arriving.

---

## Adding Content

### Custom Post Types

| CPT | WP Admin Menu | Fields to Fill |
|---|---|---|
| **Projects** | Projects → Add New | Title, Description (excerpt), Featured Image, Project Details meta box |
| **Experience** | Experience → Add New | Title, Description (excerpt), Experience Details meta box |
| **Skills** | Skills → Add New | Title, Description (excerpt), Skill Details meta box |
| **Testimonials** | Testimonials → Add New | Title, Content, Featured Image, Testimonial Details meta box |
| **Publications** | Publications → Add New | Title, Content, Featured Image |

### Meta Box Fields

| CPT | Meta Box | Fields |
|---|---|---|
| Project | Project Details | Tag/Label, Project URL, GitHub URL, Technology Stack (repeater) |
| Experience | Experience Details | Organization/Company, Date/Year Range |
| Skill | Skill Details | Icon (dropdown: test/server/code/docker/ai/wp/git/linux), Skill Level (1–5) |
| Testimonial | Testimonial Details | Role/Title, Organization |

### Ordering Content

All portfolio CPTs respect `menu_order` — use the **Order** field in the **Page Attributes** meta box to control display order (ascending).

---

## Customisation

### Colors

Edit CSS custom properties in `assets/css/main.css` (lines 17–69):

```css
:root {
  --brick: oklch(0.512 0.181 27.5);       /* Primary red */
  --ebony: oklch(0.451 0.028 138);         /* Green accent */
  --champagne: oklch(0.938 0.043 82);      /* Warm highlight */
}
```

Dark theme overrides are under `.dark { ... }` (lines 49–69).

### Sections Order

Edit `front-page.php` — reorder the `get_template_part()` calls.

### Adding a New Section

1. Create `template-parts/section-new.php`.
2. Add `get_template_part( 'template-parts/section-new' )` to `front-page.php`.
3. Add animation JS at `assets/js/animations/new.js`.
4. Enqueue in `functions.php` inside the `is_front_page()` block (around line 113).

---

## Troubleshooting

| Issue | Likely Cause | Fix |
|---|---|---|
| Animations not playing | GSAP files missing or mobile viewport | Check `functions.php` enqueue; GSAP is disabled on mobile (`wp_is_mobile()`) |
| GSAP/ScrollTrigger 404 | Wrong theme URI | Flush permalinks; confirm theme is activated |
| Portfolio sections show fallback data | CPTs not seeded | Run `wp malachy seed` or add content manually via WP Admin |
| Contact form not sending emails | `wp_mail()` blocked by host | Install an SMTP plugin (WP Mail SMTP, Post SMTP, etc.) |
| Mobile menu doesn't open | JS error from missing GSAP | Check browser console; navigation falls back to IntersectionObserver |
| White page (WSOD) | PHP 8.0+ required | Check server PHP version |
