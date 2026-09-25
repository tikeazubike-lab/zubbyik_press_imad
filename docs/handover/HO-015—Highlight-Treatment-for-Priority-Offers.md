# HO-015 — Highlight Treatment for Priority Offers (Addendum to HO-013/HO-014)

**Scope:** this touches only the visual/ordering treatment of specific offers across existing touchpoints, plus one CTA-logic adjustment. It does not change layout structure, animation system, or anything already implemented correctly in HO-014.

## Source of truth — offers to highlight

| # | Offer | Price | CTA type |
|---|---|---|---|
| 1 | Fix Business Emails Going to Spam | £15 | Get started |
| 2 | Set Up Microsoft 365 Business Email With Your Custom Domain | £30 | Get started |
| 3 | Migrate Business Email to Microsoft 365, Google Workspace | £50 | Get started |
| 4 | Migrate Your Website and Business Email to a New Host | £50 | Get started |
| 5 | Audit and Report on Your Business Email Security and Deliverability | £15 | Get started |
| 9 | Migrate and Rebuild a WordPress Site Onto a Modern Stack for AI Chatbot Integration | from £220 | Book a discovery call |

Not highlighted, unchanged from HO-014: #6 (DNS Lockdown), #7 (Monitoring) — "Learn more" only. #8 (WP Chatbot Assessment) stays "Get started" per the original tripwire logic, just without the highlight badge.

**CTA logic change from HO-014:** #3 and #4 move from "Learn more only" to "Get started," using the same `/?service=<slug>#contact` pattern as the existing tripwire offers. #9 gets a new CTA type — "Book a discovery call" — linking to the Cal.com booking URL instead of the contact form.

## 1. `template-parts/section-offers.php` — badge + reorder + CTA update

- Add a `highlight` boolean (default `false`) to each offer's data array.
- Set `highlight => true` for offers #1, #2, #3, #4, #5, #9 only.
- Render a badge on highlighted cards: `<span class="badge-highlight">Most Requested</span>`, positioned top-right of the card, above the title.
- Within each of the 3 topic groups, sort highlighted offers first, preserving original relative order otherwise (stable sort) — do not reorder the groups themselves:
  - Email Deliverability & Security: Fix Spam (H), Audit (H), DNS Lockdown, Monitoring
  - M365, Google Workspace & Migrations: all three already highlighted — order unchanged (M365 Setup, Migrate to M365/Workspace, Migrate Website+Email to New Host)
  - AI & WordPress Modernization: WP Rebuild (H) first, then WP Assessment
- CTA update: add "Get started" (`/?service=<slug>#contact`) to offers #3 and #4, matching the existing tripwire CTA markup exactly.
- New CTA variant for offer #9 only: replace "Get started" — which it never had — with a second button "Book a discovery call," linking to the Cal.com booking URL (define as a constant alongside `MALACHY_LEAD_ENDPOINT` in `template-lead-magnet.php`, e.g. `MALACHY_BOOKING_URL`). #9 keeps its existing "Learn more" CTA as well — both buttons appear on that card.

## 2. `assets/css/main.css` — badge styling

- Add `.badge-highlight` using existing OKLCH design tokens — a small pill, accent-colored background, no animation, no glow (per the site's existing restraint — see reduced-motion handling at lines 1680-1728, this element needs none since it's static).
- Optional: a slightly stronger card border (`border-color` at higher opacity of the existing accent) on `.offer-card.highlight` to reinforce the badge without adding a second competing visual signal. Don't add both a border treatment AND a background tint — pick one.

## 3. Offer detail pages (`/offers/<slug>/`)

- Add the same `.badge-highlight` element near the page title/header for offers #1, #2, #3, #4, #5, #9, for consistency with the homepage card. No other changes to these pages.

## 4. Hero rotating quotes (`template-parts/section-hero.php` lines 46-54, driven by `assets/js/animations/hero.js`)

- Review the 7 quotes already written under HO-014 §1. Confirm each highlighted offer (#1, #2, #3, #4, #5, #9) is represented by at least one quote. If any highlighted offer currently has no corresponding quote, rewrite the least-relevant existing quote to cover it — do not add an 8th quote or change the rotation mechanism.
- Do not reorder the quote rotation itself; `data-quote` attributes and `hero.js` behavior stay as-is. Content coverage only, not sequencing.

## 5. Chatbot (`inc/ai-chat-bot.php`, "what do you offer" response, lines 784-787 per HO-014 §7)

- When listing services in the default "what do you offer" answer, list the 6 highlighted offers first, then #6, #7, #8 after. Keep this ordering static in the response text — do not build dynamic sorting logic for a static Q&A response.

## 6. Do not touch

- Layout structure, GSAP animation system, Skills/Projects/Experience sections, blog cleanup, nav, Listmonk/lead-magnet form logic, or the Phase 1 → Phase 2 endpoint swap point. All of that is governed by HO-013/HO-014 and is unaffected by this addendum.

Output only the changed/added code (badge markup + CSS, updated offer data array with `highlight` flags and new CTA markup for #3/#4/#9, any rewritten hero quote, and the reordered chatbot response text), clearly labeled by file path.
