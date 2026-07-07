# Malachy Portfolio — Installation Guide

**Version:** 1.1.0  
**Theme:** Malachy Portfolio  
**Domain:** https://imadconsult.zubbystudio.shop  
**Contact:** malachy.egbuna@imadconsulting.co.uk

> A cinematic, single-page portfolio WordPress theme with GSAP animations, custom post types, and a contact form. No page builders, no ACF — pure native WordPress.

---

## Table of Contents

1. [Requirements](#requirements)
2. [Quick Install (WP Admin)](#quick-install-wp-admin)
3. [Docker Stack Install](#docker-stack-install)
4. [Post-Installation Steps](#post-installation-steps)
5. [Verification Checklist](#verification-checklist)
6. [First-Time Content Setup](#first-time-content-setup)
7. [Troubleshooting](#troubleshooting)

---

## Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| WordPress | 6.0 | 6.7+ |
| PHP | 8.0 | 8.2 |
| MySQL | 5.7 | 8.0 |
| Web Server | Apache / Nginx | Apache with mod_rewrite |
| HTTPS | Required | Let's Encrypt / Cloudflare |

### Browser Support

- Chrome 90+
- Firefox 90+
- Safari 15+
- Edge 90+

---

## Quick Install (WP Admin)

This is the fastest way to install on shared hosting or any existing WordPress site.

```bash
# Step 1: Get the package
# If you have the source repo:
bash bin/package.sh
# → produces malachy-portfolio-v1.1.0.zip
```

### Step-by-Step

1. **Log into WP Admin** at `https://yoursite.com/wp-admin`
2. Go to **Appearance → Themes → Add New → Upload Theme**
3. Select `malachy-portfolio-v1.1.0.zip`
4. Click **Install Now**
5. Click **Activate**

**Total time: ~30 seconds.**

---

## Docker Stack Install

For development, staging, or production on a VPS with Docker.

### Prerequisites

- Docker Engine 24+ and Docker Compose v2
- Traefik reverse proxy (recommended for HTTPS)
- DNS record pointing your domain to the server IP

### Quick Start

```bash
# 1. Clone / copy the project
cd /home/zubbyik/wordpress_project

# 2. Start the stack
docker compose up -d

# 3. Initialize WordPress (first run only)
#    WordPress auto-configures from environment variables.
#    Wait ~30 seconds for MySQL to initialize.

# 4. Activate the theme
docker exec malachy-wp wp --allow-root theme activate malachy-portfolio

# 5. Seed demo content (optional)
docker exec malachy-wp wp --allow-root malachy seed

# 6. Flush permalinks
docker exec malachy-wp wp --allow-root rewrite flush
```

### Docker Compose Layout

```
docker-compose.yml        ← WordPress + MySQL + Traefik labels
malachy-portfolio/        ← Theme source (bind-mounted live)
wp-data/                  ← WordPress core + uploads (persistent)
db-data/                  ← MySQL data (persistent)
```

### Accessing the Site

- **Front:** https://imadconsult.zubbystudio.shop
- **Admin:** https://imadconsult.zubbystudio.shop/wp-admin
- **Default login:** `admin` / `admin123` (⚠️ Change immediately)

> See [staging-development.md](staging-development.md) for the full Docker workflow.

---

## Post-Installation Steps

These steps are required after activation on any new site.

### 1. Set the Front Page

```
Settings → Reading → Your homepage displays
→ Select "Your latest posts"
→ Save
```

This ensures `front-page.php` renders the portfolio layout.

### 2. Configure Permalinks

```
Settings → Permalinks → Common Settings
→ Select "Post name"
→ Save
```

This flushes rewrite rules so CPT archives (projects, skills) work correctly.

### 3. Configure Contact Form Email

```
Settings → General → Administration Email Address
→ Set the address where form submissions should arrive
→ Save
```

For production, install an SMTP plugin (e.g., WP Mail SMTP, Post SMTP) to ensure reliable email delivery. The default `wp_mail()` may not work on all hosts.

### 4. Upload Portrait Image

1. Go to **Portfolio** in the admin sidebar
2. Set **Portrait Image URL** to the full URL of the hero portrait
3. Optionally update **Hero Subtitle** and **Resume URL**
4. Click **Save Changes**

---

## Verification Checklist

Check these items after installation to confirm everything works:

| # | Check | How |
|---|-------|-----|
| ✅ | Theme is active | `Appearance → Themes` shows "Malachy Portfolio" as active |
| ✅ | Front page renders portfolio | Visit the site — 7 sections: Hero, About, Skills, Projects, Experience, Blog, Contact |
| ✅ | Skills section shows cards | At least one skill published (or seeded) |
| ✅ | Projects section shows cards | At least one project published |
| ✅ | Experience timeline renders | At least one experience entry published |
| ✅ | Contact form works | Fill out form and submit — should show success toast |
| ✅ | Navigation links scroll smoothly | Click nav links — smooth scroll to sections |
| ✅ | Dark/light theme toggle works | Toggle switch in nav — theme persists on reload |
| ✅ | Mobile layout is usable | Check DevTools mobile emulation (animations disabled) |
| ✅ | No JS console errors | Open DevTools Console — should be clean |
| ✅ | Permalinks work | Visit `/projects/` — should return project archive |
| ✅ | SSL/HTTPS loads properly | No mixed-content warnings |

---

## First-Time Content Setup

### Option A: Seed with Demo Content (Docker only)

```bash
docker exec malachy-wp wp --allow-root malachy seed
```

This creates:
- **3 projects** (Test Automation Framework, Infrastructure as Code, Custom WordPress Platform)
- **4 experience entries** (Senior QA Engineer → IT Support Engineer)
- **8 skills** (QA Automation through LLM & Agentic Coding)
- Each entry is fully populated with meta fields and ordered correctly

### Option B: Add Content via WP Admin

Add entries through the admin dashboard:

| Content | Menu | Fields |
|---------|------|--------|
| Skills | **Skills → Add New** | Title, Description (excerpt), Icon, Level, Order |
| Projects | **Projects → Add New** | Title, Description (excerpt), Project URL, GitHub URL, Tech Stack, Featured Image, Order |
| Experience | **Experience → Add New** | Title, Description (excerpt), Company, Date Range, Order |
| Blog Posts | **Posts → Add New** | Standard post fields, Featured Image |

See [maintenance.md](maintenance.md) for detailed per-CPT instructions.

---

## Troubleshooting

| Problem | Likely Cause | Fix |
|---------|--------------|-----|
| "Broken theme" error on activation | Missing PHP extensions | Enable `mysqli`, `intl`, `mbstring` in PHP config |
| White page after activation | PHP error | Enable `WP_DEBUG` in `wp-config.php` to see the error |
| Front page is blank | Front page not set to "latest posts" | Settings → Reading → Your latest posts → Save |
| 404s on theme pages | Permalinks not flushed | Settings → Permalinks → Post name → Save |
| Contact form not sending | No MTA configured | Install an SMTP plugin |
| Images not loading | Wrong file paths | Update image URLs in theme settings or use absolute URLs |
| Animations not working | Mobile device or reduced-motion preference | Expected behavior — animations are desktop-only |
| CSS broken after update | Cache | Hard refresh (Ctrl+F5) or clear browser cache |
| "Sorry, you are not allowed to access this page" | WP Admin role issue | User needs at least `edit_themes` capability (Administrator) |

---

## Updating the Theme

### Via WP Admin (from ZIP)

1. **Download updated ZIP** — `malachy-portfolio-v{newversion}.zip`
2. **Backup your current theme** (FTP copy or **Appearance → Theme File Editor → Copy**)
3. Go to **Appearance → Themes**
4. Deactivate and delete the old version
5. **Add New → Upload Theme** → upload new ZIP → Activate

> **⚠️ Warning:** Deleting the old theme removes all theme files. Content (CPTs, posts, settings stored as options) is preserved in the database and will reappear when the new version is activated.

### Via WP-CLI

```bash
wp theme install malachy-portfolio-v1.1.0.zip --activate --force
```

### Via Docker (bind mount)

```bash
cd /home/zubbyik/wordpress_project
git pull origin main           # Or update files however you manage them
docker compose restart wp      # Only needed if wp-config or functions.php changed
```

---

## Related Documentation

| Document | Description |
|----------|-------------|
| [README.md](README.md) | Theme overview, structure, animation system, design system |
| [maintenance.md](maintenance.md) | WP Admin guide — managing CPTs, settings, taxonomies |
| [staging-development.md](staging-development.md) | Docker Compose development workflow |
| [packaging-guide.md](packaging-guide.md) | Build and package steps for deployment |
| [HO-001.md](HO-001.md) | Engineering handoff document (architecture decisions) |
