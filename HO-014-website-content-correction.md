# HO-014 — Website Content Correction: Implementation Notes & Corrections

**Source:** `HO-013-website-content-correction.md`
**Purpose:** Correct architectural assumptions in HO-013 based on actual codebase inspection. Read HO-013 first for the full scope — this document overrides specific points where HO-013 conflicts with the real code.

---

## 0. Codebase Reference (read before starting)

| What | File |
|------|------|
| Homepage assembler | `front-page.php` — loads all sections via `get_template_part()` |
| Hero section | `template-parts/section-hero.php` |
| About section (contains stat bar) | `template-parts/section-about.php` |
| Skills section | `template-parts/section-skills.php` |
| Projects section | `template-parts/section-projects.php` |
| Experience section | `template-parts/section-experience.php` |
| Blog preview section | `template-parts/section-blog-preview.php` |
| Contact section | `template-parts/section-contact.php` |
| Navigation (hardcoded) | `template-parts/navigation.php` |
| Contact form handler | `inc/contact-handler.php` |
| Contact form JS | `assets/js/contact.js` |
| Chatbot (static Q&A) | `inc/ai-chat-bot.php` |
| Main CSS | `assets/css/main.css` |
| Hero animations | `assets/js/animations/hero.js` |
| About animations | `assets/js/animations/about.js` |
| Functions / asset loading | `functions.php` |

**Key architectural facts:**
- Theme is hand-coded, no page builders, no ACF, native meta boxes.
- Homepage sections are `get_template_part()` calls in `front-page.php`.
- Navigation is hardcoded in `template-parts/navigation.php`; the registered `primary` menu is unused.
- There are **no custom page templates** in the theme (no `Template Name:` headers anywhere).
- Stats bar is in About, not Hero.
- Contact form is POST-only via AJAX; no URL query-param handling exists.

---

## 1. Hero Rewrite — Corrected Stat Bar Location

HO-013 says *"Keep the existing stat bar (8+ years, 40+ projects, 99.9% uptime) below the fold as supporting credibility, not the headline"* — implying the stat bar is in the hero. **It is not.** The stat bar lives in `template-parts/section-about.php` lines 42-55, inside the About section.

**Action:** Rewrite the hero H1 and sub in `template-parts/section-hero.php` only. Leave the stat bar where it is in About — it already serves as supporting credibility below the fold.

**Changes in `template-parts/section-hero.php`:**
- **Lines 40-45:** Replace H1/sub content with:
  - H1: `"I fix Microsoft 365 email deliverability & domain security for small businesses."`
  - Sub: `"Former enterprise QA engineer & sysadmin turned IT/security consultant — the same rigor I used to bring to production systems, now applied to your inbox and infrastructure."`
- **Lines 46-54:** Update the 7 rotating quotes to match the new positioning (email deliverability, domain security, Microsoft 365, peace of mind for small businesses). Keep the same `.hero-quote` structure and `data-quote` attributes — `hero.js` depends on them.
- **Lines 55-64:** Update CTAs.
  - Replace "View My Projects" with "See My Services" linking to `#offers`.
  - Keep or rename "Download CV" as appropriate.
- **Lines 68-80:** Update the marquee badge strip. Replace QA/DevOps tech-stack keywords (Playwright, Docker, Linux, Git, etc.) with service-relevant keywords (Email Security, SPF, DKIM, DMARC, DNS, Microsoft 365, Google Workspace, Domain Security, WordPress, AI Chatbots).

---

## 2. New Offers Section — New Template Part

HO-013 is correct here. Create `template-parts/section-offers.php` and insert it in `front-page.php`.

**Placement:** Between About and Skills.

In `front-page.php`, change:
```php
get_template_part( 'template-parts/section-about' );
get_template_part( 'template-parts/section-skills' );
```
to:
```php
get_template_part( 'template-parts/section-about' );
get_template_part( 'template-parts/section-offers' );
get_template_part( 'template-parts/section-skills' );
```

Rationale: visitor learns who Malachy is (About), then sees what he offers (Offers), then sees technical depth (Skills).

**Offer data (exact titles and prices from HO-013):**

### Email Deliverability & Security
1. **Fix Business Emails Going to Spam** — from £15
2. **Audit and Report on Your Business Email Security and Deliverability** — from £15
3. **Lock Down Your Domain and DNS Against Spoofing, Hijacking and Email Fraud** — from £35
4. **Provide Ongoing Email & DNS Health Monitoring for Your Business** — from £20/month

### Microsoft 365, Google Workspace & Migrations
5. **Set Up Microsoft 365 Business Email With Your Custom Domain** — from £30
6. **Migrate Business Email to Microsoft 365, Google Workspace** — from £50
7. **Migrate Your Website and Business Email to a New Host** — from £50

### AI & WordPress Modernization
8. **Assess Your WordPress Site for AI Chatbot Integration** — from £30
9. **Migrate and Rebuild a WordPress Site Onto a Modern Stack for AI Chatbot Integration** — from £220

**Each card requires two CTAs:**
- **"Get started"** — only for tripwire-tier offers (prices £15–£30): Fix Spam, Audit, M365 Setup, WP Assessment.
  - Links to: `/#contact?service=<offer-slug>`
  - Slug examples: `fix-spam`, `email-audit`, `m365-setup`, `wp-chatbot-assessment`
- **"Learn more"** — for every offer.
  - Links to a dedicated WordPress Page. Create placeholder pages with `page.php` template (no custom template needed for these), matching existing page style.
  - Page slug examples: `/offers/fix-business-emails-going-to-spam/`

**Structure and styling:**
- Use `.container-x` wrapper, matching other sections.
- Use existing heading styles (`.section-title`, `.section-subtitle` or equivalent from `main.css`).
- Group offers under 3 topic headers (`<h3>`) matching the category names above.
- Card layout: CSS Grid, 3 columns on desktop, 1 column on mobile. Follow the card pattern from Skills section (`section-skills.php` + `main.css` lines 768-900+).
- Add offer section styles to `assets/css/main.css`. Use existing design tokens (OKLCH colors, Poppins/Roboto, spacing scale).

**Animation:**
- Create `assets/js/animations/offers.js` with GSAP ScrollTrigger stagger fade-up for cards.
- Register and enqueue in `functions.php` following the existing pattern: front-page only, desktop only, in-footer, deferred.

---

## 3. Nav Update — Hardcoded Array, Not WordPress Menu

HO-013 says *"Add 'Offers' (or similar) to primary nav."* The `primary` menu is registered in `functions.php` line 34 but **never used**. Navigation is hardcoded in `template-parts/navigation.php` lines 9-17 as a PHP array.

**Action:** Edit `$nav_links` in `template-parts/navigation.php`. Insert an Offers entry after Home:

```php
$nav_links = array(
  array( 'href' => '/',            'label' => 'Home',       'section' => 'home' ),
  array( 'href' => '/#offers',     'label' => 'Offers',     'section' => 'offers' ),
  array( 'href' => '/#about',      'label' => 'About',      'section' => 'about' ),
  array( 'href' => '/#skills',     'label' => 'Skills',     'section' => 'skills' ),
  array( 'href' => '/#projects',   'label' => 'Projects',   'section' => 'projects' ),
  array( 'href' => '/#experience', 'label' => 'Experience', 'section' => 'experience' ),
  array( 'href' => '/blog',        'label' => 'Blog',       'route' => true ),
  array( 'href' => '/#contact',    'label' => 'Contact',    'section' => 'contact' ),
);
```

This automatically renders in both desktop and mobile nav, and `navigation.js` handles active-state highlighting + smooth scrolling.

---

## 4. Contact Form — Add `?service=` Query Param Support

HO-013 is correct, but the current contact form has **no URL query-param handling**. It is a pure POST-based AJAX form.

**Changes needed:**

### a) `template-parts/section-contact.php`
- Add a visible `<p class="contact-service-context">` to display which service the enquiry is about.
- Add a hidden `<input type="hidden" name="malachy_service" id="malachy_service">`.
- Keep existing fields: name, email, message, honeypot, nonce.

### b) `assets/js/contact.js`
- On DOMContentLoaded, parse `window.location.search` for `?service=<slug>`.
- Map the slug to a human-readable offer title (use a small lookup object inline in the JS).
- Populate `#malachy_service` with the slug.
- Display the offer title in `.contact-service-context`.
- If `service` is present, automatically smooth-scroll to `#contact`.

### c) `inc/contact-handler.php`
- Add `malachy_service` to sanitization (line 84-86 area).
- Add `malachy_service` to validation if needed.
- Include the service name in the email body sent to admin (around line 114).

### d) Offer cards in `section-offers.php`
- Ensure "Get started" CTA URLs are: `/#contact?service=<offer-slug>`.

---

## 5. Lead Magnet Landing Page Template

HO-013 is correct. The theme has **zero** custom page templates, so this will be the first.

**Create:** `template-lead-magnet.php` in the theme root (same level as `page.php`).

```php
<?php
/**
 * Template Name: Lead Magnet
 */
```

**Requirements:**
- Single column, low-friction layout.
- Minimal/no navigation distractions. Use a simplified or conditional header.
- Headline, 3-bullet value prop, opt-in form.
- Native HTML/JS form — no plugin.

**Form endpoint:**
- **Phase 1 (current):** POST to `https://mail.imadconsulting.co.uk/api/public/subscription`
- Define this as a named constant, e.g.:
  ```php
  define( 'MALACHY_LEAD_ENDPOINT', 'https://mail.imadconsulting.co.uk/api/public/subscription' ); // Phase 1 → Phase 2 swap point: replace with n8n webhook URL
  ```
- Method: POST
- Content-Type: `application/x-www-form-urlencoded`
- Fields: `email` (required), `name` (optional), `l` (list UUID — per-page configurable constant)

**Per-page list UUID:**
Use a custom field/meta box, or define a constant per page in the template by reading post meta. Simpler approach: add a meta box in `inc/meta-boxes.php` for `lead_magnet_list_uuid` and read it in the template.

**Honeypot:**
- Hidden field. If filled, silently return fake success (same pattern as contact form).

**Error handling:**
- Inline message on failure — must not silently fail.
- On success: redirect to a thank-you page with a direct download link.

**Thank-you page:**
- Create a generic WordPress Page for thank-you, or pass download URL as a query param.

**"Get started" CTA:**
- Surface a tripwire CTA immediately below the opt-in form.
- Link to `/#contact?service=<related-offer-slug>`.

---

## 6. Blog Cleanup

HO-013 is correct. No code changes required — this is a WordPress admin task.

- Create a new category called **"Notes"** (or similar).
- Recategorize the two Upwork-diary posts under "Notes".
- The blog loop in `home.php` and `section-blog-preview.php` currently queries all posts without category exclusion. If you want to exclude "Notes" from the primary feed, update both `WP_Query` calls to add a `category__not_in` parameter.
- Alternatively, simply leave them categorized as "Notes" and let them appear — the primary objective is to separate them visually/topically.

---

## 7. Chatbot Update (Not in HO-013 But Required)

The chatbot static Q&A in `inc/ai-chat-bot.php` (lines 784-837) conflicts with the new positioning and offers.

**Action:**
- Update the "what do you offer" response (lines 784-787) to reflect the new offer categories and titles from HO-013.
- Update individual offer regex matches (email deliverability, migration, M365 setup, domain security, email audit, WordPress) to align with the new offer descriptions and prices.
- Remove or rephrase references to "QA consulting" and "QA automation" to match the new email/security positioning.
- Ensure the chatbot no longer describes the site as a QA/DevOps portfolio.

---

## 8. CSS & Animation Guidelines

- Use existing design tokens from `assets/css/main.css` lines 17-69.
- OKLCH color format throughout.
- Font families: Poppins (display), Roboto (body).
- Use `.container-x` for section wrappers.
- GSAP animations: desktop only, front-page only, register in `functions.php` following the existing pattern.
- Respect `prefers-reduced-motion` — all animations must have fallbacks (see `main.css` lines 1680-1728).

---

## 9. Implementation Order

1. Hero rewrite (`template-parts/section-hero.php`) — text only.
2. Offers section (`template-parts/section-offers.php` + CSS + JS + `front-page.php` insertion).
3. Nav update (`template-parts/navigation.php` — one line addition).
4. Contact form query param support (`section-contact.php`, `contact.js`, `contact-handler.php`).
5. Lead magnet template (`template-lead-magnet.php` + meta box + thank-you page).
6. Blog cleanup (admin task).
7. Chatbot static answer updates (`inc/ai-chat-bot.php`).

---

## 10. Open Questions / Decisions Needed

- **Hero stat bar:** Keep stats in About, or move them into Hero?
- **Skills section:** Keep it as-is, replace it with Offers, or move it after Offers?
- **Projects section:** Current projects are QA/DevOps focused (Test Automation Framework, Infrastructure as Code, Custom WordPress Platform). Should these be updated/replaced with email/security projects, or left as historical portfolio items?
- **Experience section:** Current experience entries are QA/DevOps roles. Should these be rewritten to emphasize email/security/sysadmin consulting?

