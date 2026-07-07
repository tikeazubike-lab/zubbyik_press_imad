# Malachy Portfolio WordPress Theme

**Version:** 1.0.0  
**Author:** IMaD Consulting  
**Live URL:** https://imadconsult.zubbystudio.shop  
**Contact:** malachy.egbuna@imadconsulting.co.uk

Cinematic portfolio theme converted from React → WordPress. Full-screen hero, stacked sticky projects, animated skill cards, timeline, blog, and contact form.

---

## Quick Start

### Activation
```bash
# Already active at imadconsult.zubbystudio.shop
# For new sites: Appearance → Themes → Activate "Malachy Portfolio"
```

### First-time Setup
1. **Set front page** — already configured to show `front-page.php` (latest posts mode)
2. **Menu** — the primary menu is hardcoded in `template-parts/navigation.php` for the single-page portfolio. Customize via `Appearance → Menus` if needed.
3. **Contact Form** — works via REST API + admin-ajax fallback. Emails use `wp_mail()`. Set the recipient in `inc/contact-handler.php` line: `$to = get_option( 'admin_email' );`

---

## Custom Post Types

The theme registers 3 CPTs automatically on activation:

| CPT | Slug | Used In |
|---|---|---|
| **Projects** | `project` | Section-projects.php (stacked sticky cards) |
| **Skills** | `skill` | Section-skills.php (8 card grid) |
| **Experience** | `experience` | Section-experience.php (timeline) |

### Adding Content via WP Admin

**Skills:** Dashboard → Skills → Add New. Fields: Title, Description, Icon URL (meta box). Each skill becomes a card.

**Projects:** Dashboard → Projects → Add New. Fields: Title, Description, Image, Project URL, Technologies used. Featured image = project screenshot.

**Experience:** Dashboard → Experience → Add New. Fields: Title (job title), Description, Start/End dates, Company name. Ordered by date DESC.

### Meta Boxes

Each CPT has native meta boxes (no ACF required):
- **Skill:** Icon SVG/URL, Proficiency %
- **Project:** Project URL, GitHub URL, Tech stack (tags)
- **Experience:** Start date, End date, Company, Is current role

---

## Theme Structure

```
malachy-portfolio/
├── style.css              # Theme info (WP required)
├── functions.php           # Enqueues, theme supports, includes
├── header.php              # <head>, skip link, nav wrapper
├── footer.php              # Footer close + wp_footer()
├── front-page.php          # Single-page portfolio (7 sections)
├── index.php               # Fallback template
├── home.php                # Blog archive listing
├── single.php              # Single blog post
├── page.php                # Generic page
├── 404.php                 # 404 page
├── template-parts/
│   ├── navigation.php      # Sticky glassmorphism nav
│   ├── section-hero.php    # Full-screen hero w/ portrait
│   ├── section-about.php   # About + stats
│   ├── section-skills.php  # 8 skill cards
│   ├── section-projects.php# Stacked sticky project cards
│   ├── section-experience.php # Timeline
│   ├── section-blog-preview.php # Latest 3 posts
│   ├── section-contact.php # Form + social links
│   └── footer.php          # Site footer
├── inc/
│   ├── post-types.php      # CPT & taxonomy registration
│   ├── meta-boxes.php      # Native meta box UI
│   └── contact-handler.php # REST + admin-ajax handler
├── assets/
│   ├── css/main.css        # Single compiled stylesheet (1564 lines)
│   ├── js/
│   │   ├── vendor/         # GSAP 3.12.5, ScrollTrigger
│   │   ├── animations/     # Per-section animation modules
│   │   └── contact.js      # Form submission handler
│   ├── images/             # Portrait, project images, icons
│   └── fonts/              # (optional local fonts)
└── bin/package.sh          # Deployment packaging script
```

---

## Animation System

**GSAP 3.12.5 + ScrollTrigger** powers all animations:

| File | Section | Effect |
|---|---|---|
| `AnimationManager.js` | Orchestrator | Register/cleanup sections |
| `navigation.js` | Header | Sticky shrink on scroll, mobile menu |
| `hero.js` | Hero | Pinned scroll, portrait scale, marquee |
| `about.js` | About | Fade-up stagger, parallax |
| `skills.js` | Skills | Scroll reveal stagger |
| `projects.js` | Projects | Stacked sticky cards (desktop only) |
| `experience.js` | Experience | Growing timeline line |
| `contact.js` | Contact | Fade-up, floating orbs |
| `global.js` | All | Smooth anchors, theme toggle |

### Reduced Motion
The theme respects `prefers-reduced-motion`. Animations are disabled when the user's OS setting requests reduced motion.

---

## Design System

- **Fonts:** Fraunces (display) + Inter (body), loaded from Google Fonts
- **Color Scheme:** OKLCH-based CSS custom properties
- **Theme:** Dark mode default, toggle persists via localStorage
- **Grid:** Custom CSS (no Tailwind) — Container, flex, grid classes in main.css
- **Icons:** SVG inline / emoji fallback in skills section

---

## Deployment

### To Shared Hosting (cPanel)
1. Upload `malachy-portfolio-v1.0.0.zip` via **WP Admin → Appearance → Themes → Add New → Upload Theme**
2. Activate the theme
3. Set **Settings → Reading → Your homepage displays → Your latest posts** (for front-page.php)
4. Flush permalinks: **Settings → Permalinks → Post name → Save**

### WordPress Export
The theme uses standard WordPress structures. Use **Tools → Export** to migrate content (posts, pages) to the new host.

### From this Docker Stack
```bash
# Re-package the theme
bash /home/zubbyik/wordpress_project/malachy-portfolio/bin/package.sh
# Upload malachy-portfolio-v1.0.0.zip to shared hosting via cPanel
```

---

## Local Development

### Docker (current setup)
```bash
docker-compose up -d              # Start WordPress + MySQL
docker exec malachy-wp wp --allow-root shell   # WP-CLI
```

### File changes
The theme is mounted as a volume at:
```
./malachy-portfolio/ → /var/www/html/wp-content/themes/malachy-portfolio/
```
Changes to theme files are reflected immediately (no rebuild needed).

---

## Customization

### Colors
Edit `:root` CSS custom properties in `assets/css/main.css`:
```css
--color-background: oklch(0.141 0.004 285);
--color-foreground: oklch(0.985 0 0);
--color-accent:     oklch(0.585 0.233 277);
--color-muted:      oklch(0.7 0.01 285);
```

### Sections Order
Edit `front-page.php` — reorder the `get_template_part()` calls.

### Adding a Section
1. Create `template-parts/section-new.php`
2. Add `get_template_part( 'template-parts/section-new' );` to `front-page.php`
3. Add animation JS to `assets/js/animations/new.js`
4. Enqueue in `functions.php` under `is_front_page()` block

---

## Browser Support

- Chrome 90+
- Firefox 90+
- Safari 15+
- Edge 90+

The CSS uses OKLCH color space with fallbacks. Dark/light theme persists via `localStorage`.

---

## License

**GSAP:** Standard license (free for personal/portfolio sites)  
**Theme code:** Private — IMaD Consulting  
**Portrait image:** © Malachy Egbuta

---

## Changelog

### 1.0.0 — 2026-07-07
- Initial release
- React → WordPress theme conversion
- 7-section single-page portfolio
- GSAP 3.12.5 animations (9 modules)
- CPTs: Projects, Skills, Experience
- Native meta boxes (no ACF)
- Contact form (REST + admin-ajax)
- Dark/light theme toggle
- Accessibility: skip link, aria-labels, semantic HTML
- Shared hosting deployment package (2.1M zip)
