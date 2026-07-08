# Malachy Portfolio WordPress Theme

Cinematic portfolio theme for Malachy Egbuna — QA Engineer, SysAdmin & IT Support Specialist.

- GSAP animations powered by ScrollTrigger
- Light + Dark mode with system preference detection
- Repsonsive, accessible, no page builders, no ACF

---

## Quick Install (5 minutes)

### 1. Upload & Activate

**Method A — WP Admin (easiest)**
1. Go to **Appearance → Themes → Add New → Upload Theme**
2. Choose `malachy-portfolio.zip` and click **Install Now**
3. Click **Activate**

**Method B — FTP / cPanel**
1. Extract the zip locally
2. Upload the `malachy-portfolio/` folder to `/wp-content/themes/`
3. Go to **Appearance → Themes** and click **Activate** on Malachy Portfolio

---

### 2. Post-Installation Steps (required)

These steps MUST be done in order:

#### Step 1: Seed the CPT data

The theme stores projects, experience entries, and skills as Custom Post Types. You need to seed them.

**If you have WP-CLI access (recommended):**
```bash
wp malachy seed
```

**If you don't have WP-CLI:**
Go to **Settings → Malachy Portfolio** and click **"Seed Default Data"** button *(if this page option exists)*, or manually create them:

- **Projects:** Add New under "Projects" in the admin menu
- **Experience:** Add New under "Experience" in the admin menu
- **Skills:** Add New under "Skills" in the admin menu

#### Step 2: Set your front page

1. Go to **Settings → Reading**
2. Under **"Your homepage displays"**, select **"A static page"**
3. For **"Homepage"**, select **"Front Page"** from the dropdown
4. Click **Save Settings**

#### Step 3: Set permalinks

1. Go to **Settings → Permalinks**
2. Select **"Post name"**
3. Click **Save Changes**

(If you see a "404 Not Found" on any page after this, just visit Settings → Permalinks again and click Save — this flushes the rewrite rules.)

#### Step 4: Configure contact email

1. Go to **Settings → Malachy Portfolio**
2. Set the **"Contact Form Recipient Email"** to `malachy.egbuna@imadconsulting.co.uk`
3. Click **Save Changes**
4. Set the **"Portrait Image URL"** (if needed)

#### Step 5: Verify everything works

Visit your site and check:

| What to check | How |
|---------------|-----|
| **Hero section** | Portrait shows, text renders, CTA buttons link to #projects and #about |
| **About section** | Bio text + 3 stat cards visible |
| **Skills section** | 8 skill cards with icons |
| **Projects section** | 3 project cards with tech tags |
| **Experience section** | Timeline with 4 milestones |
| **Blog section** | Latest posts shown, "View all posts" link works |
| **Contact form** | Renders with Name, Email, Message fields |
| **Dark mode** | Click the sun/moon icon in the nav — theme toggles |
| **Mobile** | Resize browser — nav collapses to hamburger, all sections stack properly |
| **Animations** | Scroll down — sections fade/stagger in |

---

## Managing Content

All content is managed through WordPress Admin — no code changes needed.

### Projects

Go to **Projects → Add New**

| Field | What to enter |
|-------|---------------|
| Title | Project name (e.g. "Test Automation Framework") |
| Excerpt | Short description (shown on front page) |
| Tag/Category | E.g. "QA", "DevOps", "Web Dev" |
| Tech Stack | Comma-separated: "Playwright, TypeScript, Docker" |
| Project URL | https://example.com |
| GitHub URL | https://github.com/username/repo |
| Featured Image | Project screenshot or illustration |

**Order:** Use the "Order" attribute (0 = first, 1 = second, etc.)

### Experience

Go to **Experience → Add New**

| Field | What to enter |
|-------|---------------|
| Title | Job title (e.g. "Senior QA Engineer") |
| Excerpt | Description of responsibilities and achievements |
| Organization | Company name + location (e.g. "IMaD Consulting · London") |
| Year Range | E.g. "2024 — Present" or "Jan 2021 — Dec 2024" |

**Order:** Use the "Order" attribute (0 = first, 1 = second, etc.)

### Skills

Go to **Skills → Add New**

| Field | What to enter |
|-------|---------------|
| Title | Skill name (e.g. "QA Automation") |
| Excerpt | Brief description + techs (e.g. "Playwright, Cypress, Pytest") |
| Icon | Choose from: test, server, docker, code, git, terminal, wordpress, brain |

**Order:** Use the "Order" attribute (0 = first, 1 = second, etc.)

### Blog Posts

Standard WordPress posts. They appear on the front page Blog section (latest 3) and on the full Blog page.

---

## Theme Customization

### Logo

**Appearance → Customize → Site Identity → Logo**
Upload your logo (recommended: max 180px height, PNG or SVG)

### Hero Section

- **Subtitle:** Settings → Malachy Portfolio → "Hero Subtitle"
- **Portrait:** Settings → Malachy Portfolio → "Portrait Image URL"
- **CV Download:** Settings → Malachy Portfolio → "CV File"

### Navigation Links

**Appearance → Menus** — the primary menu is displayed in the header. Links should use `data-section-link` attribute for smooth-scroll behavior (the theme handles this automatically for internal anchors).

---

## Updating the Theme

1. Download the latest `malachy-portfolio.zip`
2. Go to **Appearance → Themes**
3. Deactivate the current Malachy Portfolio theme (activate a different theme temporarily)
4. Delete the old Malachy Portfolio theme
5. Upload and activate the new zip
6. Re-run `wp malachy seed` if new CPT entries are needed

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| **"This theme does not work with your version of PHP"** | The theme now requires PHP 7.0 minimum. If you're still seeing this, you may have an old zip. Re-download from `build/malachy-portfolio.zip`. |
| **404 on all pages except home** | Go to Settings → Permalinks → click "Save Changes" (no need to change anything) |
| **Contact form not sending** | Check Settings → Malachy Portfolio → Contact Email. Also check your hosting's PHP mail configuration. Some hosts require SMTP plugin. |
| **Portrait not showing** | Go to Settings → Malachy Portfolio → set the Portrait Image URL, or upload via Media Library and paste the URL |
| **GSAP animations not firing** | The theme uses GSAP v3.12.5 bundled locally. Check browser console for errors. Ensure you're on a desktop/tablet view (animations are disabled on mobile via matchMedia). |
| **Blank page (white screen of death)** | Enable WP_DEBUG in `wp-config.php` to see the PHP error. Most common cause: PHP version below 7.0. |
| **Dark mode not persisting** | Clear browser cache. The theme uses localStorage — if storage is blocked by browser settings, it won't persist. |

---

## Filesystem Structure

```
malachy-portfolio/
├── style.css              Theme identifier + header
├── functions.php          Asset enqueues, theme supports
├── header.php             <head>, navigation, dark mode script
├── footer.php             Footer, wp_footer()
├── front-page.php         Main portfolio page (7 sections)
├── home.php               Blog listing page
├── single.php             Single blog post
├── 404.php                Not-found page
├── inc/
│   ├── post-types.php     CPT + taxonomy registrations
│   ├── meta-boxes.php     Native meta boxes for all CPTs
│   ├── data-seeder.php    WP-CLI seed command
│   └── contact-handler.php AJAX contact form handler
├── template-parts/
│   ├── navigation.php     Glassmorphism header nav
│   ├── section-hero.php   Full-screen hero with portrait
│   ├── section-about.php  Bio + stats
│   ├── section-skills.php Skill cards grid
│   ├── section-projects.php Stacked sticky project cards
│   ├── section-experience.php Timeline
│   ├── section-blog-preview.php Latest 3 posts
│   ├── section-contact.php Form + social links
│   └── footer.php         Copyright + tagline
├── assets/
│   ├── css/main.css       1700-line design system (OKLCH)
│   ├── js/vendor/         GSAP 3.12.5 + ScrollTrigger
│   ├── js/animations/     7 animation modules
│   └── images/            WebP portrait + illustration
├── bin/build.sh           Packaging script
└── docs/                  Documentation
```

---

## Technical Requirements

- **WordPress:** 5.6+
- **PHP:** 7.0+
- **Memory:** 128MB recommended
- **Browser:** Modern (Chrome, Firefox, Safari, Edge)
- **No plugins required** — pure native WordPress
