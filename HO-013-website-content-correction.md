You are working in the WordPress theme codebase for imadconsulting.co.uk (custom theme, hand-coded — no page builders, no ACF, native template files + GSAP animations). This is a content/structure correction, not a redesign: preserve the existing visual system (typography, animation patterns, layout rhythm) and extend it for new sections rather than introducing a different styling approach.

Before making changes: inspect the actual theme file structure and template hierarchy — don't assume file names or paths.

Current state (verified by prior audit):
- Homepage hero reads "Hi, I'm Malachy QA Engineer, System Admin & IT Support" — stale positioning, no QA work done in ~9 years
- Site currently reads as a QA/DevOps portfolio (Projects section: Test Automation Framework, Infrastructure as Code, Custom WordPress Platform)
- Two blog posts exist ("When a Senior Developer Joins Upwork...", "Diary of an Upwork Newbie...") — AI-generated placeholder content, not the intended content strategy
- No email capture mechanism exists anywhere on the site — only a contact form

Implement the following:

1. HERO REWRITE
   H1: "I fix Microsoft 365 email deliverability & domain security for small businesses."
   Sub: "Former enterprise QA engineer & sysadmin turned IT/security consultant — the same rigor I used to bring to production systems, now applied to your inbox and infrastructure."
   Keep the existing stat bar (8+ years, 40+ projects, 99.9% uptime) below the fold as supporting credibility, not the headline.

2. NEW OFFERS SECTION
   Add a homepage section (between Hero and About, or replacing part of the current Skills section — use judgment based on existing layout) presenting all 9 offers individually, organized under 3 topic headers for browsability. Each offer card shows: title, one-line description derived from its buyer intent, and starting price. Use these exact titles and prices:

   **Email Deliverability & Security**
   - Fix Business Emails Going to Spam — from £15
   - Audit and Report on Your Business Email Security and Deliverability — from £15
   - Lock Down Your Domain and DNS Against Spoofing, Hijacking and Email Fraud — from £35
   - Provide Ongoing Email & DNS Health Monitoring for Your Business — from £20/month

   **Microsoft 365, Google Workspace & Migrations**
   - Set Up Microsoft 365 Business Email With Your Custom Domain — from £30
   - Migrate Business Email to Microsoft 365, Google Workspace — from £50
   - Migrate Your Website and Business Email to a New Host — from £50

   **AI & WordPress Modernization**
   - Assess Your WordPress Site for AI Chatbot Integration — from £30
   - Migrate and Rebuild a WordPress Site Onto a Modern Stack for AI Chatbot Integration — from £220

   Each card gets TWO CTAs, not one:
   - "Get started" — for tripwire-tier offers (Fix Spam, M365 Setup, Audit, WP Assessment: prices £15–£30), links to a pre-filled quote/contact form on-site (`?service=<offer-slug>`), NOT to Fiverr/PeoplePerHour — routing traffic back to those low-visibility platforms defeats the purpose of this project.
   - "Learn more" — for every offer, links to that offer's dedicated page (create placeholder pages if they don't exist, matching existing page template style).

3. LEAD MAGNET LANDING PAGE TEMPLATE
   Create a reusable page template for lead magnets (single column, low-friction: headline, 3-bullet value prop, opt-in form, no nav distractions). Build the opt-in form as native HTML/JS (no plugin), posting to Listmonk's public subscription API:

   Endpoint: https://mail.imadconsulting.co.uk/api/public/subscription
   Method: POST, Content-Type: application/x-www-form-urlencoded
   Fields: email, name (optional), l (list UUID — per-page configurable constant)

   NOTE: this form was originally meant to post to an n8n webhook for full orchestration (subscriber creation + immediate delivery + nurture sequence + Cal.com hand-off). n8n is temporarily down. Implement Phase 1 only: post directly to Listmonk, redirect on success to a thank-you page with a direct download link as a fallback (don't rely solely on the confirmation email arriving). Structure the endpoint URL as a single named constant so swapping to the n8n webhook later is a one-line change — mark that line with a comment as the Phase 1 → Phase 2 swap point.

   Include a honeypot field for spam mitigation and inline error handling — a failed request must not silently do nothing.

   Each magnet page should also surface a "Get started" CTA for the tripwire offer it's most naturally paired with, immediately below the opt-in form, for visitors who are ready to just buy rather than download.

4. BLOG CLEANUP
   Move the two Upwork-diary posts out of the main blog loop — unpublish, or recategorize under a clearly separate, non-primary category (e.g. "Notes") so they don't appear in the primary feed or dilute topical focus for search engines.

5. NAV UPDATE
   Add "Offers" (or similar) to primary nav, pointing at the new offers section/pages.

6. QUOTE/CONTACT FORM UPDATE
   Extend the existing contact form (or add a lightweight variant) to accept a `?service=<offer-slug>` query param and pre-select/display which offer the enquiry is about, so "Get started" clicks from the offers section arrive pre-qualified rather than dumping into a generic message box.
