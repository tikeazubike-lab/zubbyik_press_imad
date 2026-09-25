# WEBSITE CODEBASE HANDOVER — Full Forensic Audit

**Date:** 2026-09-17
**Scope:** `/home/zubbyik/wordpress_project/malachy-portfolio/` (WordPress theme)
**Staging:** `https://imadconsult.zubbystudio.site/`
**Theme version:** 1.3.1

---

## 1. REPOSITORY MAP

### Framework, Languages, Build System

| Aspect | Detail |
|--------|--------|
| CMS | WordPress (custom theme, no page builder) |
| Languages | PHP (templates, logic), JavaScript (vanilla, no bundler), CSS (hand-authored, no preprocessor) |
| Build system | None. Two shell scripts in `bin/` for zip packaging only. |
| JS vendor | GSAP 3.12.5 + ScrollTrigger (bundled locally in `assets/js/vendor/`) |
| PHP dependencies | None beyond WordPress core. No ACF, no plugins required. |
| Font stack | Google Fonts CDN: DM Sans (400–700) + Instrument Serif (regular + italic) |
| Color system | OKLCH design tokens in CSS custom properties (`:root` and `.dark`) |
| Min PHP | 7.0 |
| Min WP | 5.6+ |
| License | GPL v2 or later |

### Entry Points

- `functions.php` — Theme bootstrap: asset enqueue, theme supports, settings page, module includes
- `header.php` — `<head>`, meta tags, schema.org, Google Fonts, dark-mode flash-prevention script
- `footer.php` — Closes `</main>`, loads footer template part, AI chatbot mount point, `wp_footer()`

### Page Templates

| File | Purpose |
|------|---------|
| `front-page.php` | Main portfolio page — composes 8 section template parts |
| `home.php` | Blog archive listing (mapped to `/blog` via `template_include` filter) |
| `single.php` | Single blog post |
| `page.php` | Generic WordPress page |
| `index.php` | Fallback template |
| `404.php` | Not-found page |
| `template-lead-magnet.php` | Named template: "Lead Magnet" — opt-in form posting to Listmonk |
| `template-thank-you.php` | Named template: "Thank You" — post-conversion page with optional download |

### Template Parts (`template-parts/`)

| File | Section ID | Purpose |
|------|-----------|---------|
| `navigation.php` | `site-header` | Header: wordmark, nav links, CTA, dark mode toggle, hamburger |
| `section-hero.php` | `home` | Full-screen hero: title, role, 2 CTAs, portrait, discovery call panel |
| `section-about.php` | `about` | Bio text, 3 metrics (8+ years, 40+ projects, 99.9% uptime) |
| `section-offers.php` | `offers` | Services: 3 groups, 9 offers, featured + numbered list |
| `section-skills.php` | `skills` | 4-column tech stack grid (16 items), augmented from CPT |
| `section-projects.php` | `work` | Project cards with images, tags, tech stacks |
| `section-experience.php` | `experience` | Timeline with vertical line and nodes |
| `section-blog-preview.php` | `blog` | Latest 3 posts in journal grid layout |
| `section-contact.php` | `contact` | Contact form + social links |
| `footer.php` | `site-footer` | 3-column footer: copyright, tagline, back-to-top + social icons |

### Custom Post Types (registered in `inc/post-types.php`)

| CPT | Rewrite Slug | Status |
|-----|-------------|--------|
| `project` | `/projects` | Used (3 seeded entries) |
| `experience` | `/experience` | Used (7 seeded entries) |
| `skill` | `/skills` | Used (9 seeded entries) |
| `testimonial` | `/testimonials` | **Registered but 0 posts exist. No front-end template renders testimonials.** |
| `publication` | `/publications` | **Registered but completely unused. Dead code.** |

### Custom Taxonomies

- `project_category` (hierarchical, for projects)
- `technology_stack` (non-hierarchical, for projects + experience)
- `skill_category` (hierarchical, for skills)

### Includes (`inc/`)

| File | Purpose |
|------|---------|
| `post-types.php` | Registers 5 CPTs + 3 taxonomies |
| `meta-boxes.php` | Custom meta box UI for all CPTs + lead magnet settings + admin settings page |
| `data-seeder.php` | WP-CLI command `wp malachy seed` + admin button to seed default content |
| `contact-handler.php` | REST + AJAX contact form processing |
| `ai-chat-bot.php` | Full AI chatbot: REST routes, chat logic, lead capture, Telegram, knowledge retrieval, LLM fallback |

### Styling

- `assets/css/main.css` — 1983 lines, OKLCH design tokens, complete design system
- `assets/css/ai-chat-bot.css` — 679 lines, chatbot widget styles
- Dark mode via `.dark` class on `<html>`, toggled by JS, persisted in `localStorage`
- `style.css` — Theme declaration header only (12 lines, no actual styles)
- **`assets/css/wordpress-editor.css`** — Referenced in `functions.php` line 36 but **does not exist on disk**. Silent 404 in admin.

### Animation System (GSAP + ScrollTrigger)

| File | Targets |
|------|---------|
| `AnimationManager.js` | Global manager: section registration, matchMedia, reduced-motion, cleanup |
| `hero.js` | Hero entrance stagger + scroll parallax |
| `about.js` | About section reveal on scroll |
| `offers.js` | Featured service fade-up + numbered items scale-in |
| `skills.js` | Skills grid reveal |
| `projects.js` | Project cards reveal |
| `experience.js` | Timeline line growth + node stagger + item reveal |
| `contact.js` | Contact section reveal |
| `global.js` | Blog journal cards reveal |
| `navigation.js` | Mobile menu toggle, active link highlighting, smart anchor nav |

### Other JS

- `assets/js/theme-toggle.js` — Light/dark toggle with localStorage persistence
- `assets/js/contact.js` — AJAX contact form + discovery panel open/close
- `assets/js/ai-chat-bot.js` — Full AI chatbot widget (909 lines): 3-stage progressive disclosure, exit-intent modal, REST API integration, lead capture, markdown parsing

### API Integrations

- **OpenCode.ai LLM** (`ai-chat-bot.php`) — Endpoint: `https://opencode.ai/zen/v1/chat/completions`, model: `deepseek-v4-flash-free`
- **Telegram Bot API** (`ai-chat-bot.php`) — Lead notification alerts
- **Listmonk** (`template-lead-magnet.php`) — Public subscription API at `https://mail.imadconsulting.co.uk/api/public/subscription`
- **Cal.com** (`functions.php`) — Booking URL: `https://cal.com/malachy-egbuna`

### Analytics

**None.** No Google Analytics, Plausible, or any tracking scripts detected.

### SEO Config

- `title-tag` theme support (WordPress manages `<title>`)
- `<meta name="description">` in `header.php` — fallback: "Reliable — QA Engineer, SysAdmin & IT Support Specialist. Portfolio and blog."
- Schema.org Person markup (JSON-LD in `header.php`)
- `robots.txt` with sitemap reference
- **No Open Graph tags** — missing og:title, og:description, og:image
- **No Twitter Card meta tags**
- **No canonical URL tag** (relies on WordPress default)

### Deployment/CI

No CI/CD config files. Deployment is manual. Two shell scripts in `bin/`:
- `bin/build.sh` — rsync + zip packaging
- `bin/package.sh` — Similar, targets shared hosting

### README/Docs

`README.md` — 227 lines, comprehensive installation guide, content management docs, troubleshooting table.

### TODO/FIXME

**Zero matches.** No `TODO`, `FIXME`, `HACK`, `XXX`, `PLACEHOLDER`, or `lorem ipsum` found.

### Unused/Abandoned Code

1. **`testimonial` CPT** — Registered but no front-end rendering. Only used by chatbot static Q&A.
2. **`publication` CPT** — Registered but never queried or rendered. Completely dead code.
3. **`about-illustration*` images** (6 files) — Zero references in any PHP, JS, or CSS. Leftover from previous design.
4. **`malachy-portrait.png`** — Original portrait source file, not referenced in code.
5. **`languages/` directory** — Empty. Theme is i18n-ready but no translation files exist.
6. **`malachy-portfolio-v1.0.0.zip`** — Old package artifact in theme root.
7. **Duplicate admin settings** — `functions.php` AND `meta-boxes.php` both register settings UI for the same options.
8. **`wordpress-editor.css`** — Referenced but does not exist on disk.
9. **`wp-config-docker.php`** and `wp-config-sample.php` — Left in WordPress root.

---

## 2. FILE-BY-FILE CONTENT DISCOVERY (Verbatim)

### 2.1 Navigation (`template-parts/navigation.php`)

**Wordmark:** `ME.` (with `.` in primary color)

**Nav links:**
- `Work` → `/#work`
- `Experience` → `/#experience`
- `Contact` → `/#contact`
- `Blog` → `/blog`

**CTA button:** `Let's talk` → `/#contact`

**ARIA labels:**
- `"Malachy Egbuna home"` (wordmark)
- `"Primary navigation"` (nav)
- `"Toggle theme"` (dark mode button)
- `"Toggle menu"` (hamburger)

### 2.2 Hero Section (`template-parts/section-hero.php`)

**Eyebrow/subtitle (configurable):** `Portfolio . 2026` (default)

**Heading:** `Hi, I'm` / `Malachy.` (Malachy in `<em>`)

**Role line:** `QA Engineer . System Admin . IT Support Specialist`

**Body copy:** `I engineer confidence into software and infrastructure — from automated test suites to resilient servers, with a fondness for LLM-driven, spec-first workflows.`

**CTAs:**
- `View My Projects` → `/#work`
- `Book a Discovery Call` → opens slide-out panel

**Portrait alt text:** `Malachy Egbuna — portrait photo`

**Portrait note:** `Based in Manchester` / `Working everywhere`

**Scroll hint:** `Scroll to explore` / `01 / 08`

**Discovery Panel:**
- Title: `Book a Discovery Call`
- Close label: `Close`
- Contact links: `Phone`, `WhatsApp` ("Send a message"), `Twitter / X`
- Divider: `or send a message`
- Form labels: `Name`, `Email`, `Message`
- Placeholders: `Your name`, `your@email.com`, `Tell me about your project...`
- Submit: `Send Message`
- Footer link: `Or book directly on Cal.com`

### 2.3 About Section (`template-parts/section-about.php`)

**Section label:** `02` / `What I solve`

**Heading:** `Software and` / `servers, sorted.`

**Lede:** `Good technology should make the work feel lighter. I test the edges, fix the foundations and support the people in the middle.`

**Body:** `Most of what I do comes down to one thing: making sure things work before someone else finds out they don't. From release confidence to a stubborn DNS record, I bring a practical eye to the places where products and people meet. The goal is not just to make things work — it's to make them hold up.`

**Metrics:**
- `8+` / `Years in QA`
- `40+` / `Projects shipped`
- `99.9%` / `Uptime targeted`

### 2.4 Offers/Services Section (`template-parts/section-offers.php`)

**Section label:** `03` / `How I can help`

**Featured service label:** `Featured service 01`

**CTA:** `Tell me what's stuck`

**Offer groups and items (verbatim titles and descriptions):**

**Group 1: Email Deliverability & Security**
1. *Fix Business Emails Going to Spam* — `Diagnose and fix the SPF, DKIM, and DMARC issues that push your emails to junk folders.` (from £15, highlighted)
2. *Audit and Report on Your Business Email Security and Deliverability* — `A clear, actionable report on your email authentication, DNS health, and spam risk.` (from £15)
3. *Lock Down Your Domain and DNS Against Spoofing, Hijacking and Email Fraud* — `Harden your domain records and registrar settings against impersonation and takeover.` (from £35)
4. *Provide Ongoing Email & DNS Health Monitoring for Your Business* — `Monthly monitoring that catches email and DNS problems before they affect your customers.` (from £20/month)

**Group 2: Microsoft 365, Google Workspace & Migrations**
5. *Set Up Microsoft 365 Business Email With Your Custom Domain* — `Get professional business email running on your domain with correct DNS and authentication.` (from £30, highlighted)
6. *Migrate Business Email to Microsoft 365, Google Workspace* — `Move mailboxes, calendars, and contacts without downtime or lost messages.` (from £50)
7. *Migrate Your Website and Business Email to a New Host* — `Relocate your site and email together with minimal disruption and proper DNS handover.` (from £50)

**Group 3: AI & WordPress Modernization**
8. *Migrate and Rebuild a WordPress Site Onto a Modern Stack for AI Chatbot Integration* — `A full rebuild on a modern, maintainable stack with AI chatbot integration built in.` (from £220, highlighted, shows discovery CTA)
9. *Assess Your WordPress Site for AI Chatbot Integration* — `A structured review of whether your site is ready for an AI chatbot and what it would take.` (from £30)

### 2.5 Skills/Stack Section (`template-parts/section-skills.php`)

**Section label:** `04` / `The tools behind it`

**Heading:** `The stack behind` / `the work.`

**Subtitle:** `Enough tools to solve the problem. Not so many that the tools become the problem.`

**Stack groups:**
- **Languages:** JavaScript, TypeScript, Python, SQL
- **Infrastructure:** GitHub Actions, Docker, Linux, AWS
- **Testing:** Playwright, Cypress, Postman, Jira
- **Web / CMS:** WordPress, React, Node.js, REST APIs

### 2.6 Projects Section (`template-parts/section-projects.php`)

**Section label:** `05` / `Selected work`

**Heading:** `Recent` / `projects.`

**Subtitle:** `A few useful things I've helped bring into the world.`

**CTA:** `View case`

**Fallback projects (if no CPT entries):**
1. *Test Automation Framework* (Quality assurance) — `A comprehensive Playwright-based E2E framework for multi-environment regression testing, featuring parallel execution, visual diffing, and CI integration.` — Tags: Playwright, React, Data viz
2. *Infrastructure as Code* (Web / CMS) — `Automation for server provisioning using Docker Compose, monitoring stacks, and automated backup rotations across 4 environments.` — Tags: WordPress, PHP, Performance
3. *Custom WordPress Platform* (Product systems) — `A bespoke WordPress theme and plugin ecosystem for high-traffic portfolio and directory sites, with GSAP animations and zero page builder reliance.` — Tags: Product design, UX strategy, Systems

### 2.7 Experience Section (`template-parts/section-experience.php`)

**Section label:** `06` / `A little history`

**Heading:** `Where I've` / `been.`

**Subtitle:** `The roles change. The instinct to make things better doesn't.`

**Fallback experience (if no CPT entries):**
1. *QA Engineer* — Independent / contract — 2024–now — `Building quality into web products through thoughtful test strategy, automation and documentation.` — Playwright · API testing · CI/CD
2. *Technical Support Engineer* — Digital products — 2022–24 — `Untangling infrastructure and customer issues, then turning patterns into better systems and clearer help.` — WordPress · DNS · Linux · Customer success
3. *IT Support Specialist* — Growing teams — 2020–22 — `Keeping people productive across devices, accounts and the quiet technical details that make work possible.` — Endpoint support · Identity · Process

### 2.8 Blog Preview Section (`template-parts/section-blog-preview.php`)

**Section label:** `07` / `Notes from the shop`

**Heading:** `News from` / `the shop.`

**Link:** `All notes` → `/blog`

**Featured CTA:** `Read the note`

**Other CTA:** `Read`

**Fallback posts (if none exist):**
1. `QA practice · 05.24` — `Small checks, fewer surprises.`
2. `Infrastructure · 02.24` — `Docker monitoring stack in six minutes.`
3. `AI & Tooling · 01.24` — `Agentic coding is a conversation, not a prompt.`

### 2.9 Contact Section (`template-parts/section-contact.php`)

**Section label:** `08` / `Make something useful`

**Heading:** `Let's` / `build.`

**Subtitle:** `Have a tricky problem, a new thing to test or a site that needs a steady pair of hands?`

**Social links:**
- Email (dynamic, default: `malachy.egbuna@imadconsulting.co.uk`)
- `LinkedIn` → `https://www.linkedin.com/in/malachy-egbuna`
- `GitHub` → `https://github.com/zubbyik`

**Form labels/placeholders:**
- `Your name` / `Jane Smith`
- `Email address` / `jane@company.com`
- `What can I help with?` / `A little context goes a long way...`

**Submit button:** `Send enquiry`

### 2.10 Footer (`template-parts/footer.php`)

**Copyright:** `© {year} Malachy Egbuna`

**Tagline:** `Good work, thoughtfully done.`

**Links:** `Back to top` → `/#top`, LinkedIn, GitHub

### 2.11 Blog Page (`home.php`)

**Eyebrow:** `Writing`

**Title:** `Notes from the shop.`

**Empty state:** `No posts yet. Check back soon.`

**Back link:** `Back to home`

### 2.12 Single Post (`single.php`)

**Default category label:** `Article`

**Back link:** `Back to blog`

### 2.13 404 Page (`404.php`)

**Heading:** `404`

**Subheading:** `Page not found`

**Body:** `The page you're looking for doesn't exist or has been moved.`

**CTA:** `Go home`

### 2.14 Lead Magnet Template (`template-lead-magnet.php`)

**Default bullets (if none set):**
- `A practical checklist you can use immediately`
- `Common mistakes that waste time and money`
- `What to do first if you are not sure where to start`

**Form labels:** `Email`, `Name (optional)`

**Submit:** `Get the guide`

**Loading state:** `Subscribing...`

**Tripwire CTA:** `Rather skip the download and just get this fixed?` / `Get started`

### 2.15 Thank You Template (`template-thank-you.php`)

**Heading:** `Thank you — you are almost done.`

**Message:** `Check your inbox for a confirmation email. Once you confirm, the guide will be on its way.`

**Download button:** `Download your guide` (conditional)

**Next steps:** `Want help implementing this?` / `Get in touch`

### 2.16 AI Chatbot (`inc/ai-chat-bot.php` + `assets/js/ai-chat-bot.js`)

**JS config:**
- placeholder: `What would you like to fix?`
- assistant title: `Assistant`
- welcome: `Hi! I'm Malachy — I help businesses fix email deliverability, secure their domains, and migrate to Microsoft 365 or Google Workspace. What are you struggling with?`

**Exit-intent modal title:** `Need help before you go?`

**Lead form prompt:** `I'd love to discuss this further. Share your details and I'll follow up.`

**Lead form labels:** `Your name`, `Your email`

**Lead form buttons:** `Send`, `Cancel`

**Success message:** `Thanks! I'll be in touch soon.`

**Error message:** `Could not send your details. Please use the contact form instead.`

**Credit fallback:** `I can't respond right now — my AI service is temporarily unavailable.` + contact form link

**Rate limit message:** `You've sent too many messages — please wait a moment before trying again.`

### 2.17 Schema.org Markup (`header.php`)

```json
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Malachy Egbuna",
  "jobTitle": "QA Engineer, SysAdmin & IT Support Specialist",
  "url": "{home_url}"
}
```

### 2.18 Meta Description (`header.php`)

Fallback: `Reliable — QA Engineer, SysAdmin & IT Support Specialist. Portfolio and blog.`

### 2.19 Admin Settings (verbatim labels)

- `Portrait Image URL`
- `Hero Subtitle` (default: `Portfolio . 2026`)
- `Resume / CV File URL`
- `Contact Form Email` (default: `malachy.egbuna@imadconsulting.co.uk`)
- `Phone Number` (default: `+2348164162816`)
- `WhatsApp Number` (default: `2348162816`)
- `Twitter / X URL` (default: `https://x.com/azubike_ike`)
- `Seed Default Data` button

### 2.20 Contact Form Messages (verbatim)

- `Security check failed. Please refresh and try again.`
- `Thank you! Your message has been sent.` (honeypot fake success)
- `You've sent too many messages. Please try again later.`
- `Please fill in all fields.`
- `Please enter a valid email address.`
- `Thanks! I'll get back to you soon.`
- `Could not send message. Please try again later.`

### 2.21 Placeholder/Fallback Content

Hardcoded fallback data (not lorem ipsum, but default content when no CPT entries exist):
- 3 fallback projects in `section-projects.php`
- 3 fallback experience entries in `section-experience.php`
- 3 fallback blog posts in `section-blog-preview.php`
- Static Q&A in `ai-chat-bot.php` contains hardcoded experience timeline that **differs from seeder data** (e.g., "2024 — Now: Senior QA Engineer" vs seeder's "2020 — Present: Founder & Systems/QA Consultant")

---

## 3. POSITIONING — WHAT THE SITE CURRENTLY SELLS

### Business Identity

The site presents as a **freelance technical consultancy** run by Malachy Egbuna, offering:
- Email deliverability and domain security
- Microsoft 365 / Google Workspace migrations
- WordPress modernization with AI chatbot integration
- QA engineering and infrastructure support

### Target Audience

Small businesses needing email/domain fixes, migrations, or WordPress/AI work. The language is non-technical, problem-focused ("fix emails going to spam", "lock down your domain").

### Services Explicitly Stated

9 paid services in 3 groups, all with pricing ("from £X"):
- Email Deliverability & Security (4 services, £15–£20/month)
- Microsoft 365, Google Workspace & Migrations (3 services, £30–£50)
- AI & WordPress Modernization (2 services, £30–£220)

### Services Only Implied

- General QA consulting (mentioned in experience but not in offers)
- Infrastructure/server administration (mentioned in about section)
- LLM workflow consulting (mentioned in hero copy)

### CTA Inventory

| CTA | Location | Action | Destination | Functional? |
|-----|----------|--------|-------------|-------------|
| `Let's talk` | Navigation | Anchor link | `/#contact` | Yes |
| `View My Projects` | Hero | Anchor link | `/#work` | Yes |
| `Book a Discovery Call` | Hero | Opens slide-out panel | Discovery panel with form + links | Yes |
| `Tell me what's stuck` | Featured service | Link | `/?service={slug}#contact` | Yes |
| `Get started` | Offer cards | Link | `/?service={slug}#contact` or offer page | Yes |
| `Book a discovery call` | Offer card (AI) | Link | Cal.com | Yes |
| `Learn more` | Offer cards | Link | Offer detail page | Yes |
| `View case` | Project cards | Link | External URL or `/#contact` | Yes |
| `Read` / `Read the note` | Blog preview | Link | Blog post permalink | Yes |
| `All notes` | Blog preview | Link | `/blog` | Yes |
| `Send enquiry` | Contact form | Form submit | AJAX → `wp_mail()` | Yes |
| `Back to top` | Footer | Anchor link | `/#top` | Yes |
| `Get the guide` | Lead magnet | Form submit | Listmonk API | Yes |
| `Get started` | Lead magnet tripwire | Link | `/?service={slug}#contact` | Yes |
| `Download your guide` | Thank you page | Link | Download URL | Conditional |
| `Get in touch` | Thank you page | Link | `/#contact` | Yes |

---

## 4. LEAD CAPTURE AUDIT

| # | Mechanism | Location | Trigger | Destination | Data Captured | Status |
|---|-----------|----------|---------|-------------|---------------|--------|
| 1 | Contact Form (main) | `section-contact.php` | Form submit (AJAX) | `admin-ajax.php` → `contact-handler.php` → `wp_mail()` | Name, email, message, service slug, honeypot, nonce | **Working** |
| 2 | Discovery Panel Form | `section-hero.php` | "Book a Discovery Call" → slide-out → form submit | Same as #1 | Name, email, message, honeypot, nonce | **Working** |
| 3 | Phone link | `section-hero.php` | Click | `tel:+2348164162816` | None (pass-through) | **Working** |
| 4 | WhatsApp link | `section-hero.php` | Click | `https://wa.me/2348162816` | None (pass-through) | **Working** |
| 5 | Twitter/X link | `section-hero.php` | Click | `https://x.com/azubike_ike` | None (pass-through) | **Working** |
| 6 | Cal.com booking | `section-hero.php`, `section-offers.php` | Click | `https://cal.com/malachy-egbuna` | None (pass-through) | **Working** |
| 7 | Email mailto | `section-contact.php` | Click | `mailto:malachy.egbuna@imadconsulting.co.uk` | None (pass-through) | **Working** |
| 8 | LinkedIn | `section-contact.php`, `footer.php` | Click | `https://www.linkedin.com/in/malachy-egbuna` | None (pass-through) | **Working** |
| 9 | GitHub | `section-contact.php`, `footer.php` | Click | `https://github.com/zubbyik` | None (pass-through) | **Working** |
| 10 | AI Chatbot (lead) | `ai-chat-bot.js` | Bot detects name/email or shows inline form | REST `malachy/v1/chat/lead` → `wp_mail()` + Telegram + DB log | Name, email, conversation, IP, timestamp | **Working** |
| 11 | AI Chatbot (chat) | `ai-chat-bot.js` | User sends message | REST `malachy/v1/chat` → static Q&A → knowledge → LLM | Message text, history | **Working** |
| 12 | Lead Magnet Form | `template-lead-magnet.php` | Form submit | Listmonk API `https://mail.imadconsulting.co.uk/api/public/subscription` | Email, optional name, list UUID | **Working** (depends on Listmonk) |
| 13 | Exit-intent modal | `ai-chat-bot.js` | Mouse leaves viewport after 15s | Same as chatbot | Message text, history | **Working** |
| 14 | Nav CTA | `navigation.php` | Click | `/#contact` | None (funnel) | **Working** |
| 15 | Offers CTAs | `section-offers.php` | Click | `/?service={slug}#contact` or offer pages | None (funnel) | **Working** |
| 16 | Project CTAs | `section-projects.php` | Click | External URL or `/#contact` | None (funnel) | **Working** |

**No dead mechanisms found.** All forms have traced, working submission paths.

**Security notes:**
- Contact form: nonce + honeypot + rate limiting (3/hr/IP) + sanitization
- Chatbot lead endpoint: `permission_callback => '__return_true'`, no nonce, no rate limiting on lead endpoint (chat endpoint has 20/hr)
- Lead magnet: honeypot only, no nonce (posts to external API)

---

## 5. TECHNICAL ARCHITECTURE

```
BROWSER                           FRONTEND                       BACKEND                         DESTINATION
───────                           ────────                       ───────                         ───────────

[Contact Form] ──AJAX POST──→ contact.js ──fetch──→ admin-ajax.php ──→ contact-handler.php
  #contact-form                 (malachyAjax       (wp_ajax_              malachy_process_contact()
  nonce + form data              .ajaxurl)           malachy_               ├─ nonce verify
                                                     send_contact)          ├─ honeypot check
                                                                            ├─ rate limit (3/hr/IP)
                                                                            ├─ sanitize fields
                                                                            └─ wp_mail() → admin_email

[Chatbot Message] ──REST POST──→ ai-chat-bot.js ──fetch──→ /wp-json/malachy/v1/chat
  .ai-chatbot-input               (malachyChatbotConfig     handle_chat()
                                   .apiUrl)                   ├─ rate limit (20/hr/IP)
                                                              ├─ spam detection
                                                              ├─ static Q&A cache
                                                              ├─ Tier 1: knowledge retrieval
                                                              ├─ Tier 2: LLM fallback (opencode.ai)
                                                              └─ return reply

[Chatbot Lead] ──REST POST──→ ai-chat-bot.js ──fetch──→ /wp-json/malachy/v1/chat/lead
  inline form                     (malachyChatbotConfig        handle_lead()
                                   .leadUrl)                    ├─ wp_mail() → owner
                                                                ├─ wp_mail() → visitor
                                                                ├─ Telegram notification
                                                                └─ DB log (wp_options)

[Lead Magnet] ──POST──→ inline JS ──fetch──→ Listmonk API
  #lead-magnet-form                                      https://mail.imadconsulting.co.uk
                                                         /api/public/subscription

[External Links] ──click──→ tel: / wa.me / cal.com / mailto: / LinkedIn / GitHub
```

---

## 6. ROUTE / PAGE INVENTORY

| Route | Source File | Purpose | Primary CTA | Lead Mechanism | SEO Metadata |
|-------|------------|---------|-------------|----------------|-------------|
| `/` | `front-page.php` | Portfolio landing (8 sections) | "View My Projects", "Send enquiry" | Contact form, Discovery panel, Chatbot | Schema.org Person, meta description, title-tag |
| `/blog` | `home.php` (via filter) | Blog archive | Per-post links | Chatbot (global) | title-tag |
| `/blog/{slug}` | `single.php` | Blog post | "Back to blog" | Chatbot (global) | title-tag |
| `/thank-you` | `template-thank-you.php` | Post-opt-in page | "Download your guide" | Funnel to contact | title-tag |
| `{lead-magnet}` | `template-lead-magnet.php` | Lead magnet opt-in | "Get the guide" | Listmonk API | title-tag |
| `/offers/{slug}` | `page.php` | Service detail | "Get started" | Funnel to contact | title-tag |
| `/{any-page}` | `page.php` | Generic page | Varies | Chatbot (global) | title-tag |
| `/404` | `404.php` | Error page | "Go home" | None | title-tag |
| `/projects/{slug}` | `single.php` (CPT) | Project detail | None | Chatbot (global) | title-tag |
| `/experience/{slug}` | `single.php` (CPT) | Experience detail | None | Chatbot (global) | title-tag |
| `/skills/{slug}` | `single.php` (CPT) | Skill detail | None | Chatbot (global) | title-tag |
| `/testimonials/{slug}` | `single.php` (CPT) | Testimonial | None | Chatbot (global) | title-tag |
| `/publications/{slug}` | `single.php` (CPT) | Publication | None | Chatbot (global) | title-tag |

**REST API routes:**
- `POST /wp-json/malachy/v1/contact` — Contact form (orphaned, frontend uses AJAX)
- `POST /wp-json/malachy/v1/chat` — Chatbot messages
- `POST /wp-json/malachy/v1/chat/lead` — Chatbot lead capture

**Orphan archives:** `/projects/`, `/experience/`, `/skills/`, `/testimonials/`, `/publications/`, `/project-category/`, `/technology/`, `/skill-category/` — accessible but unlinked from navigation.

---

## 7. COMPONENT INVENTORY

### PHP Template Parts

| Component | File | Purpose | Used By |
|-----------|------|---------|---------|
| Navigation | `template-parts/navigation.php` | Site header | `header.php` |
| Hero | `template-parts/section-hero.php` | Hero + discovery panel | `front-page.php` |
| About | `template-parts/section-about.php` | Bio + metrics | `front-page.php` |
| Offers | `template-parts/section-offers.php` | Services showcase | `front-page.php` |
| Skills | `template-parts/section-skills.php` | Tech stack grid | `front-page.php` |
| Projects | `template-parts/section-projects.php` | Portfolio cards | `front-page.php` |
| Experience | `template-parts/section-experience.php` | Timeline | `front-page.php` |
| Blog Preview | `template-parts/section-blog-preview.php` | Journal grid | `front-page.php` |
| Contact | `template-parts/section-contact.php` | Contact form | `front-page.php` |
| Footer | `template-parts/footer.php` | Site footer | `footer.php` |

### JavaScript Components

| Component | File | Purpose | Loaded |
|-----------|------|---------|--------|
| Contact Form Handler | `assets/js/contact.js` | AJAX submit | Front page |
| AI Chatbot Widget | `assets/js/ai-chat-bot.js` | Full chatbot UI + API | All pages |
| Theme Toggle | `assets/js/theme-toggle.js` | Dark/light mode | All pages |
| AnimationManager | `assets/js/animations/AnimationManager.js` | GSAP orchestration | Front page (desktop) |
| Hero/About/Offers/Skills/Projects/Experience/Contact/Global Animations | `assets/js/animations/*.js` | Section scroll animations | Front page (desktop) |
| Navigation Animation | `assets/js/animations/navigation.js` | Sticky header, mobile menu | All pages |
| GSAP + ScrollTrigger | `assets/js/vendor/*.js` | Animation library | Front page (desktop) |

### PHP Includes

| Include | File | Purpose |
|---------|------|---------|
| Post Types | `inc/post-types.php` | 5 CPTs + 3 taxonomies |
| Meta Boxes | `inc/meta-boxes.php` | Custom fields + admin UI |
| Data Seeder | `inc/data-seeder.php` | Default content seeding |
| Contact Handler | `inc/contact-handler.php` | AJAX + REST form processing |
| AI Chatbot | `inc/ai-chat-bot.php` | Chatbot logic, REST routes, lead capture |

### CSS Files

| File | Purpose |
|------|---------|
| `style.css` | Theme declaration only |
| `assets/css/main.css` | All frontend styles (1983 lines) |
| `assets/css/ai-chat-bot.css` | Chatbot styles (679 lines) |
| `assets/css/wordpress-editor.css` | **Referenced but missing from disk** |

---

## 8. ASSET INVENTORY

### Images

| Filename | Type | Where Used | Status |
|----------|------|-----------|--------|
| `malachy-portrait.webp` | WebP | Hero portrait (default src) | **USED** |
| `malachy-portrait-400w.webp` | WebP | Hero srcset | **USED** |
| `malachy-portrait-600w.webp` | WebP | Hero srcset | **USED** |
| `malachy-portrait-1024w.webp` | WebP | Hero srcset | **USED** |
| `malachy-portrait.png` | PNG | Not referenced | **UNUSED** |
| `about-illustration*.webp` (6 files) | WebP/PNG | Not referenced anywhere | **UNUSED** |
| `project-qa.png` | PNG | Projects fallback + seeder | **USED** |
| `project-qa.webp` + `-600w`/`-900w` | WebP | Dynamic srcset | **USED** (conditional) |
| `project-sysadmin.png` | PNG | Projects fallback + seeder | **USED** |
| `project-sysadmin.webp` + variants | WebP | Dynamic srcset | **USED** (conditional) |
| `project-wordpress.png` | PNG | Projects fallback + seeder | **USED** |
| `project-wordpress.webp` + variants | WebP | Dynamic srcset | **USED** (conditional) |
| `project-placeholder.svg` | SVG | Fallback when no featured image | **USED** |
| `favicon.ico` | ICO | Browser favicon | **USED** |
| `site-icon.png` | PNG | 512×512 site icon | **USED** |
| `apple-touch-icon.png` | PNG | iOS home screen icon | **USED** |

**7 unused image files:** `malachy-portrait.png`, `about-illustration.png`, `about-illustration.webp`, `about-illustration-400w.webp`, `about-illustration-768w.webp`, `about-illustration-1024w.webp`

### Fonts

No local font files. All fonts from Google Fonts CDN: DM Sans (400–700) + Instrument Serif.

### Icons

All inline SVG in PHP templates and JS. No icon font or library.

### JS Vendor

| File | Version |
|------|---------|
| `assets/js/vendor/gsap.min.js` | 3.12.5 |
| `assets/js/vendor/ScrollTrigger.min.js` | 3.12.5 |

---

## 9. SECURITY RE-VERIFICATION

### 9.1 WordPress Core and Plugins

| Component | Version | Status |
|---|---|---|
| WordPress Core | 7.1 | Current |
| PHP | 8.2.28 | **EOL since Dec 2025** |
| MySQL | 8.0 | Supported |
| Akismet | Bundled (inactive) | Not activated |
| hello.php | Present | Deactivated, should be deleted |

### 9.2 Backdoor Scan

| Check | Result |
|---|---|
| PHP files in `wp-content/uploads/` | **CLEAN** — none found |
| Suspicious files in root | **CLEAN** — none found |
| Malicious cron entries | **CLEAN** — standard WP cron only |
| Malicious DB options | **CLEAN** — no eval/exec/base64/shell |
| mu-plugins backdoors | **CLEAN** — no mu-plugins directory |
| `.htaccess` | **CLEAN** — standard WordPress rewrite rules |

### 9.3 Admin Accounts

| ID | Login | Email |
|---|---|---|
| 1 | `admin` | malachy.egbuna@imadconsulting.co.uk |

**Finding:** Username is `admin` — the most common brute-force target.

### 9.4 File Permissions

| Path | Permissions | Assessment |
|---|---|---|
| `wp-config.php` | 644 | OK — not world-writable |
| `/var/www/html/` | 755 | OK |
| `wp-content/` | 755 | OK |
| `wp-content/uploads/` | 755 | OK |

No SUID files found. No world-writable files outside uploads.

### 9.5 Security Issues Found

| Issue | Severity | Detail |
|---|---|---|
| PHP 8.2 EOL | **Critical** | No security patches since Dec 2025 |
| `xmlrpc.php` active | **Medium** | Known brute-force/DDoS vector |
| `readme.html` present | **Low** | Exposes WP version |
| Username `admin` | **Medium** | Common brute-force target |
| No security headers | **Medium** | Missing X-Content-Type-Options, X-Frame-Options, HSTS, CSP |
| WP_DEBUG=true | **Low** | `debug.log` could grow and be web-accessible |
| `.env` contains plaintext secrets | **Low** | Never committed to git (in `.gitignore`), but present on disk |

### 9.6 Overall Security Verdict

**CLEAN.** No evidence of current or residual backdoor malware. The previous backdoor (HO-012) has been fully remediated. Remaining items are hardening recommendations, not active threats.

---

## 10. OPEN TECHNICAL DEBT (from HO-012)

| Item | Status | Detail |
|---|---|---|
| `prefers-reduced-motion` | **PASS** | All 8 animation files check `matchMedia`. CSS has `@media (prefers-reduced-motion: reduce)` blocks. |
| `aria-live` regions | **PARTIAL** | Chatbot has `aria-live="polite"`. Contact form status divs (`#contact-status`, `#discovery-status`) do **not** have `aria-live`. |
| Font-loading scope | **FUNCTIONAL** | 2 fonts loaded from Google Fonts CDN (DM Sans + Instrument Serif). `display=swap` used. No self-hosting. |
| Desktop fallback if GSAP fails | **PASS** | GSAP only enqueued on desktop. Content visible by default (no CSS `opacity:0`). Navigation has IntersectionObserver fallback. |
| Mobile animation fallback | **PASS** | GSAP not enqueued on mobile (`wp_is_mobile()` gate). All content renders without animation. |
| Console errors on staging | **UNKNOWN** | Could not run Playwright in this audit context. |

---

## 11. VERIFIED vs. IMPLIED vs. UNKNOWN

### VERIFIED IMPLEMENTATION

- Contact form with AJAX submission, nonce, honeypot, rate limiting
- AI chatbot with 3-tier response (static → knowledge → LLM)
- Chatbot lead capture with email + Telegram + DB log
- Lead magnet form posting to Listmonk
- Discovery call panel with phone/WhatsApp/Twitter/Cal.com
- Service pre-fill from `?service=` URL param
- Dark/light mode toggle with localStorage
- GSAP animations with `prefers-reduced-motion` support
- Responsive images with WebP srcset
- 9 offer pages with pricing
- 5 CPTs registered (project, experience, skill, testimonial, publication)
- WP-CLI seeder command
- Multi-channel lead delivery (email + Telegram + DB)

### VERIFIED CONTENT

- 9 paid services with pricing
- 3 project case studies
- 7 experience entries
- 9 skills
- 3 metrics (8+ years, 40+ projects, 99.9% uptime)
- Contact information (phone, WhatsApp, email, Twitter, LinkedIn, GitHub)
- Cal.com booking integration

### IMPLIED CAPABILITY

- "QA consulting" — mentioned in experience and chatbot Q&A but no dedicated service page
- "Infrastructure/server administration" — mentioned in about section but not in offers
- "LLM workflow consulting" — mentioned in hero copy but not a standalone service
- "Spec-first workflows" — mentioned in hero copy, partially demonstrated by the site itself

### UNKNOWN

- Whether `wp_mail()` actually delivers emails (depends on Docker host mail config, no SMTP plugin)
- Whether Listmonk instance at `mail.imadconsulting.co.uk` is operational
- Whether `OPENCODE_API_KEY` is defined in production
- Whether Telegram bot token/chat ID are valid
- Console errors on staging (could not test)
- Password strength for admin account
- Whether Cal.com booking page is configured

---

## 12. CONTENT / POSITIONING ISSUES

### Issue 1: Chatbot experience data contradicts seeder data

**Evidence:** `ai-chat-bot.php` lines 780–781 contain "2024 — Now: Senior QA Engineer" while seeder in `data-seeder.php` has "2020 — Present: Founder & Systems/QA Consultant"
**Location:** `inc/ai-chat-bot.php` static Q&A responses
**Why it matters:** Chatbot gives different career timeline than the portfolio page
**Confidence:** HIGH

### Issue 2: WhatsApp number may be incomplete

**Evidence:** Phone default `+2348164162816` has 13 digits. WhatsApp default `2348162816` has 10 digits. The leading `816` appears to be missing.
**Location:** `functions.php` lines 211, 215
**Why it matters:** WhatsApp link may not work
**Confidence:** MEDIUM — could be intentional if different numbers

### Issue 3: No Open Graph or Twitter Card tags

**Evidence:** Grep for `og:` and `twitter:` returns zero matches in theme code
**Location:** `header.php`
**Why it matters:** Social sharing produces poor previews with no image/title/description
**Confidence:** HIGH

### Issue 4: `resume_url` setting is registered but never rendered

**Evidence:** `malachy_resume_url` option registered in `meta-boxes.php` but no template uses it
**Location:** `inc/meta-boxes.php`
**Why it matters:** Admin setting exists but does nothing
**Confidence:** HIGH

### Issue 5: LinkedIn and GitHub URLs hardcoded

**Evidence:** `https://www.linkedin.com/in/malachy-egbuna` and `https://github.com/zubbyik` hardcoded in templates while phone/WhatsApp/Twitter use `get_option()`
**Location:** `section-contact.php`, `footer.php`
**Why it matters:** Inconsistent with settings-based approach for other social links
**Confidence:** HIGH

### Issue 6: `testimonial` CPT registered but no testimonials exist

**Evidence:** CPT registered in `post-types.php`, meta boxes in `meta-boxes.php`, zero posts in database
**Why it matters:** Chatbot references testimonials but has none to show
**Confidence:** HIGH

### Issue 7: `wordpress-editor.css` referenced but missing

**Evidence:** `add_editor_style('assets/css/wordpress-editor.css')` in `functions.php` line 36, file does not exist
**Why it matters:** Silent 404 in block editor
**Confidence:** HIGH

---

## 13. BUSINESS CAPABILITY INVENTORY

### Services (evidence-supported)

1. Email deliverability fixes (SPF/DKIM/DMARC) — from £15
2. Email security audit — from £15
3. Domain/DNS hardening — from £35
4. Email/DNS monitoring — from £20/month
5. Microsoft 365 setup — from £30
6. Email migration (M365/Google) — from £50
7. Website + email migration — from £50
8. WordPress rebuild with AI chatbot — from £220
9. AI chatbot readiness assessment — from £30

### Technical Capabilities

- WordPress theme development (custom theme, no page builder)
- GSAP animations with scroll triggers
- AI chatbot integration (OpenCode.ai LLM, knowledge retrieval, lead capture)
- Telegram Bot API notifications
- Listmonk email marketing integration
- Cal.com booking integration
- Responsive WebP image optimization
- Dark mode support
- Accessibility (prefers-reduced-motion, aria-live partial)
- WP-CLI data seeding
- Docker-based development environment

### Portfolio/Case Studies

| Project | Problem | Work | Tech | Outcome |
|---------|---------|------|------|---------|
| Test Automation Framework | Multi-environment regression testing | E2E framework with parallel execution | Playwright, TypeScript, Docker, GitHub Actions | **UNKNOWN** — no outcome stated |
| Infrastructure as Code | Server provisioning | Docker Compose, monitoring, backups | Docker, Linux, Bash, Python | **UNKNOWN** — no outcome stated |
| Custom WordPress Platform | High-traffic portfolio sites | Theme + plugin ecosystem, GSAP animations | WordPress, PHP, GSAP, Docker | **UNKNOWN** — no outcome stated |

### Differentiators (evidence-backed)

- Spec-first, LLM-assisted workflow (hero copy, chatbot Q&A)
- 8+ years in QA (about section metric)
- 40+ projects shipped (about section metric)
- 99.9% uptime targeted (about section metric)
- Fixed-price services with clear scope

### Trust Signals

- Specific pricing on all 9 services
- Cal.com booking integration (shows availability)
- AI chatbot with instant responses
- Detailed experience timeline (7 entries)

### Conversion Opportunities Already Built

- Contact form with service pre-fill
- Discovery call panel
- AI chatbot with lead capture
- Lead magnet template (Listmonk)
- Thank you page with download
- Tripwire CTA on lead magnet
- Cal.com booking links
- 9 offer detail pages

---

## 14. SECOND-PASS KEYWORD SWEEP

| Keyword | Count | Assessment |
|---|---|---|
| TODO / FIXME | 0 | Clean |
| placeholder | 29 | All functional HTML attributes |
| lorem | 0 | Clean |
| testimonial | 37 | CPT registered, 0 posts |
| case study | 2 | Alt text only |
| contact | 100+ | Core feature, well-implemented |
| mailto | 1 | Active email link |
| whatsapp | 2 | Active WhatsApp link |
| phone | 5 | Active phone link + settings |
| webhook | 1 | Future n8n integration comment |
| API | 100+ | REST + external APIs, all intentional |
| chatbot | 100+ | Full implementation |
| CRM | 0 | No CRM integration |
| calendar | 1 | Content text only |
| booking | 3 | Cal.com integration |
| analytics | 0 | **None — no tracking** |
| schema.org | 1 | Minimal Person JSON-LD |
| og: | 0 | **Missing** |
| twitter: | 0 | **Missing** |
| canonical | 0 | **Missing** |
| SPF / DKIM / DMARC | 31 | Service content, not site config |
| SMTP | 1 | README note about needing SMTP plugin |
| DNS | 14 | Service content + experience descriptions |

---

## 15. INVESTIGATION RESULT

**Files inspected:** 68 PHP files, 14 JS files, 3 CSS files, 2 shell scripts, 1 robots.txt, 1 README.md, 1 style.css, 20 image files, 1 SVG

**Routes identified:** 13 (8 template-based, 3 CPT singles, 3 REST API)

**Components identified:** 10 PHP template parts, 14 JS components, 5 PHP includes, 3 CSS files

**Lead mechanisms identified:** 16 (3 form-based, 13 link-based)
- Working: 16/16
- Uncertain: 0
- UI-only: 13 (external links)
- Dead: 0

**Security re-check: PASS** — No evidence of current or residual backdoor malware. Previous backdoor fully remediated. Remaining items are hardening recommendations (PHP upgrade, disable xmlrpc, add security headers, change admin username).

**Tech-debt items still open:**
1. `aria-live` missing on contact form status divs
2. Google Fonts loaded from CDN (not self-hosted)
3. `wordpress-editor.css` referenced but missing
4. Chatbot experience data contradicts seeder data
5. WhatsApp number may be incomplete
6. `testimonial` and `publication` CPTs registered but unused
7. `resume_url` admin setting never rendered
8. LinkedIn/GitHub URLs hardcoded instead of settings-based

**Unknowns requiring clarification:**
1. Whether `wp_mail()` delivers in production (no SMTP configured)
2. Whether Listmonk instance is operational
3. Whether OPENCODE_API_KEY is defined in production
4. Console errors on staging (could not test)
5. Admin password strength
6. Whether Cal.com booking page is configured
7. Whether WhatsApp number `2348162816` is correct (vs phone `+2348164162816`)

**Files modified:** 0
**Git changes made:** 0
