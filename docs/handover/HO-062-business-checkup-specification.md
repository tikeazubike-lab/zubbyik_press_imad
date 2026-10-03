---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-061 — Business Checkup Product Specification & UX Handover
date: 2026-10-01
from: Hermes (on-ground agent) — answer to HO-061 §23
to: ChatGPT (review) / Malachy (approval of the OPEN items)
status: SPECIFICATION COMPLETE — nothing implemented, nothing modified except this document, nothing committed, nothing deployed
priority: HIGH
---

> **Filing note.** HO-061 §23 requires the deliverable be titled "HO-061 — Business Checkup
> Product Specification & UX Handover". `docs/handover/HO-061.md` already exists as ChatGPT's
> specification brief (2026-10-01), so this document is filed as **HO-062** rather than
> overwriting the brief — the same convention this project used for HO-055 → HO-056 and
> HO-059 → HO-060. The title above is the required title; the filename is
> `docs/handover/HO-062-business-checkup-specification.md`. Rename on request.

Labels used throughout: **CONFIRMED** (verified in the codebase), **RECOMMENDED**
(engineering/product recommendation), **OPEN** (needs Malachy's approval), **DEFERRED**
(deliberately out of MVP).

---

## 1. Executive summary

A Business Checkup can be added as a single standalone page that reuses this site's existing
lead funnel end to end. No backend change, no schema change, no new service, no new
dependency, and no new notification path is required for the MVP.

Shape of the recommendation:

- **One page** at `/business-checkup/`, built as a WordPress page template modelled on the
  existing `template-lead-magnet.php` — the precedent that already proves a standalone page
  with its own JS, its own honeypot and a CTA into the contact form.
- **8 questions**, 6 of them scored, across the two dimensions HO-060 said were MVP-viable:
  Business Health and WhatsApp Readiness.
- **Deterministic scoring, client-side.** No AI, no server round-trip, no storage.
- **Result shown before any contact details are requested**, then a handoff into the existing
  contact flow via the site's own pre-fill mechanism
  (`/?service=<slug>&utm_source=…&utm_campaign=…#contact`), which is already used by the
  offers section (`template-parts/section-offers.php:154`).
- **Attribution is preserved with zero changes to shared JS** by carrying `utm_*` through the
  checkup URL and forwarding it into that link — because `contact.js` reads the parameters
  from `location.search` on the homepage, where the form lives.

Why this is the smallest safe version: with the handoff above, the checkup submits through the
**existing** contact form, which already provides the nonce, the honeypot, the 3-per-hour IP
limit, the WordPress `wp_mail()` email path, the FastAPI Telegram path and the
`dead_letters` fallback. Nothing about notification, validation or lead capture needs to be
touched. Phase 1 therefore creates new files and modifies essentially nothing shared.

Three corrections to the premises of HO-060/HO-061, found during this re-inspection and
detailed in §19: `.btn-secondary` does not exist in this codebase; `.reveal` is **not** a
styled component (zero CSS rules — it is a front-page, JS-driven class); and a plain
WordPress page renders with blog styling (`page.php`), so the checkup genuinely needs its own
page template.

---

## 2. Confirmed constraints (from HO-060, re-verified)

CONFIRMED in code during this pass:

| Constraint | Evidence |
|---|---|
| No SPA, no bundler, no frontend framework | theme is classic PHP; JS is plain ES5 in `assets/js/` |
| Assets are enqueued with a version cache-buster | `functions.php:17` `MALACHY_THEME_VERSION` = 1.3.21; enqueues at `functions.php:93-206` |
| Page templates are a first-class pattern | `template-lead-magnet.php:3`, `template-thank-you.php:3` |
| A plain page gets blog styling — a custom template is required for a distinct layout | `page.php` renders `<section class="blog-page">…single-post-content` with `padding:6rem` |
| Contact pre-fill mechanism exists and is already used by offers | `contact.js:99-123`; `template-parts/section-offers.php:154` builds `/?service=<slug>#contact` |
| Nine service slugs are the only accepted `?service=` values | `contact.js:100-110` |
| UTM is read from `location.search` only — no persistence | `contact.js:112,197-213` |
| Contact form posts to FastAPI **and** WP AJAX, success if either succeeds | `contact.js:149-191` |
| WP handler owns nonce + both honeypot names + 3/hour/IP + `wp_mail` | `inc/contact-handler.php:68-92,127` |
| `POST /api/leads` needs `X-Form-Secret`, 5/min/IP, dedupes on `idempotency_key` | `main.py:86-98`; `db.py:39-61`; `models.py:7` (`problem_text` max 4 000) |
| WhatsApp link + number option already exist | `template-parts/section-hero.php:16,91` (`wa.me/<malachy_whatsapp>`) |
| No analytics of any kind | grep for gtag/GTM/plausible/matomo/fbq/dataLayer/sendBeacon: zero hits |
| GSAP/ScrollTrigger are front-page-only and desktop-only | `functions.php:104-154` (`wp_is_mobile()` + `is_front_page()`) |
| Reduced-motion is already handled globally | `main.css:2556` (kills animations/transitions, `scroll-behavior:auto`); also `AnimationManager.js:21`, `about.js:13`, `contact.js:13` |
| Production PHP floor is 7.0 | `docs/ops/project-reseed-preflight.php:22`; `HO-057:206` |
| Visual-regression harness exists (15 tracked routes) | `npm run visual:capture` → `tests/visual/config/visual.config.ts` |

Also CONFIRMED: the repo checkout sits **inside** the web root (HO-058), which is why this
specification stores nothing in files.

---

## 3. Recommended product concept

**Decision** — One visitor-facing "Business Checkup" that internally covers two diagnostic
dimensions (Business Health, WhatsApp Readiness), presented as a single tool with one result.
**Reason** — Matches HO-060 §6 and HO-061 §3: one tool, multiple dimensions behind it.
Website Health, Email/Deliverability and the AI Action Plan stay internal future phases, not
public tabs.
**Trade-offs** — The result is coarser than five specialised tools; mitigated by naming the
dimension scores separately in the result.
**Implementation consequence** — One page, one template, one JS file, one question set. No
tab bar, no sub-routes, no per-dimension pages.

Positioning copy constraint: the tally must read as a *diagnostic*, not a disguised form —
which is why the result is shown **before** any contact details are asked for (§6).

---

## 4. Visitor journey

**Decision** — Linear stepper: intro → 8 questions, one per screen → result → handoff.

```
Entry (nav / direct / campaign URL, utm_* possibly present)
   │
   ▼
Intro screen            "8 quick questions · about 2 minutes · no signup"
   │  [Start the checkup]  ←  checkup_start
   ▼
Question 1 of 8  ── progress bar ── [Back] [Next]
   │   (answers editable until the result; Back returns one step, choices preserved)
   ▼
Question 8 of 8
   │  [See my result]
   ▼
Result screen           score bands + findings + priority + why-it-matters
   │  ← no contact details requested to reach this point  ←  checkup_complete
   ├── Primary CTA  →  /?service=<slug>&utm_source=…&utm_campaign=…#contact
   └── Secondary CTA →  https://wa.me/<malachy_whatsapp>   (existing option)
```

| Question | Decision |
|---|---|
| What the visitor sees | One question per screen, plain language, business context line, no jargon |
| What is collected | 8 answers, held in memory (and optionally `sessionStorage` for same-tab resume). No personal data until the visitor chooses to contact (see §8) |
| Progress | Visible from question 1: "Question 3 of 8" + `role="progressbar"` |
| One-at-a-time vs all at once | One at a time (mobile-first; also keeps the result honest — the visitor commits to each answer) |
| Going backwards | Yes — `[Back]`, previous selection preserved |
| Editing answers | Yes, until the result screen; after the result, `[Start over]` resets |
| Abandonment | Nothing is stored server-side in MVP. No partial lead, no interruption. Re-entering starts fresh, or resumes if `sessionStorage` holds an unfinished run in the same tab (RECOMMENDED, see §12) |
| After completion | Result screen, then the two CTAs. Nothing auto-submits |
| Result before contact details | **Yes. Contact details are never requested by the checkup itself in MVP** |

Trade-offs: a stepper adds state handling versus showing all 8 questions on one screen;
justified because the audience arrives from WhatsApp/social on a phone.

---

## 5. MVP question tree

8 questions: 6 scored (0–15 points), 2 unscored (segmentation and routing).

| ID | User-facing question | Answer type | Options | Req | Scoring | Dimension | Result implication | Handoff implication |
|---|---|---|---|---|---|---|---|---|
| **Q1** `stage` | "Which best describes your business right now?" | single select (radio) | Solo / freelancer; Small team (2–10); Growing (11–50); Larger / multiple teams | Yes | **0** (segmentation) | — | Sets the result's framing sentence only | Carried to the lead only in Phase 2 (§8); no MVP effect |
| **Q2** `conversations` | "Do you have one place where every customer conversation is recorded?" | single select | Yes, always; Mostly; No, it's scattered; I don't track them | Yes | 3 / 2 / 1 / 0 | Business Health | 0–1 → "enquiries can go missing" finding | Feeds priority only |
| **Q3** `presence` | "When someone searches for your business, what do they find?" | single select | A website I'm happy with; A website, but it's out of date; Only social/directory listings; Not much / I'm not sure | Yes | 3 / 1 / 1 / 0 | Business Health | 0–1 → "the first impression is working against you" finding | Feeds priority → likely `wp-rebuild` |
| **Q4** `email_identity` | "How do quotes, invoices and updates go out to customers?" | single select | Business email on my own domain; Mostly WhatsApp; A mix of personal/social and business email; I'm not sure what's used | Yes | 3 / 2 / 1 / 0 | Business Health | 0–1 → "your email identity is inconsistent" finding | Feeds priority → likely `m365-setup` |
| **Q5** `wa_profile` | "Do you have a WhatsApp Business profile, or just your normal WhatsApp?" | single select | Yes, Business profile; Not sure; No, just my personal WhatsApp | Yes | 2 / 1 / 0 | WhatsApp Readiness | 0–1 → "you're not using the business tools" finding | Feeds priority → WhatsApp CTA (see §8 note) |
| **Q6** `wa_number` | "Is the WhatsApp number customers use the same one you advertise?" | single select | Yes, same everywhere; Different in some places; Not advertised / not sure | Yes | 2 / 1 / 0 | WhatsApp Readiness | 0–1 → "customers may be messaging a number nobody watches" finding | Feeds priority → WhatsApp CTA |
| **Q7** `wa_response` | "What usually happens when a new customer messages you on WhatsApp?" | single select | I reply the same day; Messages wait until I get to them; They often get missed; Nobody really monitors it | Yes | 2 / 1 / 0 / 0 | WhatsApp Readiness | 0–1 → "speed of reply is costing you enquiries" finding | Feeds priority → WhatsApp CTA |
| **Q8** `priority` | "Which of these would you most like to fix first?" *(optional)* | single select, skippable | Emails not reaching customers; My website is out of date and not bringing enquiries; My business email isn't on my own domain; I'd rather someone checked it all over | No | **0** (routing) | — | Overrides the *stated* priority; the computed priority is still shown alongside | **Maps to a real service slug** → the primary CTA: `fix-spam` / `wp-rebuild` / `m365-setup` / `email-audit` |

Rationale for 8 (the brief's target was 6–8): 6 scored questions are the minimum that covers
both dimensions with 3 signals each, and Q8 exists to route the CTA to a real offer. Two
questions are deliberately unscored so the score stays explainable and the lead routing stays
honest.

Explicitly **not** asked: company size in numbers, revenue, industry vertical, current
providers, budget, timeline, phone number, email. None is needed for the MVP result, and each
would add friction or "collected because it might be useful later".

---

## 6. Scoring model

**Decision** — Deterministic, client-side, pure function `scoreCheckup(answers)`. No AI, no
server call, no persistence.

| Dimension | Questions | Range | Bands |
|---|---|---|---|
| Business Health | Q2, Q3, Q4 | **0–9** | 0–3 At risk · 4–6 Partly covered · 7–9 Solid |
| WhatsApp Readiness | Q5, Q6, Q7 | **0–6** | 0–2 At risk · 3–4 Partly covered · 5–6 Solid |
| Overall | all 6 scored | **0–15** | 0–5 "Needs attention now" · 6–10 "Some gaps" · 11–15 "In good shape" |

- **Points per answer** are exactly the values in §5 — no hidden weighting; the artefact that
  explains a band is a plain addition of the selected point values.
- **Dimension percentage** = dimension score ÷ dimension max (used only for comparing the two
  dimensions against each other, since their maxima differ).
- **Priority issue** = the lowest-scoring *scored* question (largest points lost); if Q8 was
  answered, the result shows the visitor's stated area as "what you told us matters most" and
  the computed one as "what the checkup found" — both are shown, never silently swapped.
- **Ties / mixed signals** — deterministic, documented rule: equal points lost → the earlier
  question in the order Q2 → Q3 → Q4 → Q5 → Q6 → Q7 wins; equal *dimension percentages* →
  Business Health wins. No randomness, no "it depends".
- **Bands are thresholds on integers**, so there is no floating-point ambiguity.

Trade-offs: no weighting means Q2–Q4 (max 3 each) outweigh the WhatsApp trio (max 2 each) in
the overall total; acceptable because the two dimensions are also reported separately, and it
keeps the arithmetic explainable on a one-line "how this was scored" disclosure in the result.

**Explainability requirement** — the result screen includes a collapsible "How this was
scored" line listing the six scored answers and their points. This is what makes it read as a
diagnostic rather than a lead magnet.

---

## 7. Result-state specification

**States** — three overall bands × two dimension bands. The overall band drives the headline;
the dimension bands drive the findings and the CTA.

Structure of the result screen (all nine elements required by HO-061 §9):

| # | Element | Specification |
|---|---|---|
| 1 | Result title | Per overall band: "Needs attention now" / "Some gaps" / "In good shape" — paired with the business name from Q1 where it reads naturally |
| 2 | Score representation | Overall band pill + two labelled dimension scores, e.g. `Business Health 5/9 · WhatsApp Readiness 2/6`, plus the progress-bar visual reusing existing tokens |
| 3 | Plain-language explanation | 2–3 sentences, no jargon, states what the band means for a business of Q1's size |
| 4 | 3–5 key findings | Generated deterministically from the lowest-scoring answers: one finding per scored answer below its maximum, ordered by points lost descending, capped at 5. Each finding = what it means + why it matters (no accusation, no fake statistics) |
| 5 | Priority issue | The computed priority (§6), labelled "The first thing I would fix" |
| 6 | Recommended next action | One concrete step, tied to the priority (e.g. "get your business email onto your own domain" → `m365-setup`) |
| 7 | Relevant IMAD context | The matching offer title, drawn from the existing nine service slugs (`contact.js:100-110`) — the same wording the site already uses in the offers section |
| 8 | Primary CTA | `/?service=<slug>&utm_source=…&utm_campaign=…#contact` — carries attribution and pre-fills the contact form |
| 9 | Secondary CTA | WhatsApp: `https://wa.me/<malachy_whatsapp>` (existing option, `section-hero.php:91`), plus "Start over" |

**Copy constraint (must not be violated):** no invented performance claims, guarantees,
certifications, client results, benchmarks or capabilities. Findings are phrased as
observations about the visitor's own answers ("you told us enquiries are scattered, which is
usually where they get lost") and every service reference must match wording already on the
site. Nothing in the result may imply an audit has actually been performed — the checkup is a
self-assessment, and the copy must say so.

**Band copy skeleton** (to be written by the implementer, reviewed by Malachy — OPEN item 7):

- At risk → "Right now, a customer trying to reach you can fall through the gap."
- Partly covered → "The basics are there, but there are gaps a customer will notice."
- Solid → "You're in good shape — the gaps left are small and worth closing."

---

## 8. Lead handoff decision

**Decision (RECOMMENDED, MVP)** — **Option A: the existing contact flow.**

```
Result → [?service=<slug>&utm_source=…&utm_campaign=…#contact]
       → existing contact form (pre-filled context line + hidden malachy_service)
       → existing dual submit (FastAPI + WP AJAX) — completely unchanged
```

**Reason** — It is the only option that preserves, with zero new code, the whole of what makes
this funnel resilient: the nonce, both honeypot names, the WordPress 3-per-hour IP limit,
`wp_mail()` (the email fallback), the FastAPI Telegram notification and the `dead_letters`
safety net. It also reuses the mechanism the offers section already relies on
(`section-offers.php:154`), so it is the least novel thing that could possibly work. And it
does not create a second lead-entry path — which HO-061 §10 explicitly asks about.

**Trade-offs (stated plainly):**

| Dimension | Option A (existing flow) | Option B (direct `POST /api/leads`) |
|---|---|---|
| UX friction | One extra screen and 3 fields (name, email, message) | Lowest — the checkup could post on completion |
| Duplicate submissions | None: one submit path | New risk: if the visitor later also uses the contact form on the same page load, both share `window.__imad_idem` and the second is deduped → **silently no notification** (`db.py:47-52`) |
| Notification behaviour | Identical to today (Telegram **and** email) | Telegram only — **`wp_mail()` fallback is lost** for that lead |
| Email fallback | Yes | **No** |
| Existing validation | Nonce + both honeypots + 3/hour/IP + sanitisation | Honeypot + 5/min/IP + Pydantic lengths only; no nonce |
| Idempotency | Untouched | Must mint its own key (see above) |
| Rate limits | 3/hour/IP (WP) | 5/min/IP (API) |
| Attribution | Preserved *if* carried in the link (§9) | Preserved via `source_campaign` in the payload |
| Implementation surface | Zero existing-file change | New submit code in the checkup JS; new failure/status handling to write and maintain |
| Maintainability | One lead path to reason about | Two paths that can drift — the class of bug this project has already been bitten by |
| Lead quality | The visitor's own words in `message` | Structured diagnostic attached to the lead |

**Implementation consequence for MVP:** the result carries the diagnostic **nowhere** — the
lead is a normal contact enquiry with a pre-filled service context. That is the honest cost of
Option A and it is acceptable for a first version, because the qualification value is the
priority slug plus the visitor's own message.

**Phase-2 upgrade — RECOMMENDED: A′ (additive), not B.** If the diagnostic genuinely needs to
reach the lead, prefer Option A′: keep the single existing submit path and add one additive
hidden field (e.g. `checkup_summary`) to the existing form plus a bounded append in
`contact.js`/`contact-handler.php`. Reasons: it preserves the email fallback and the nonce
path, keeps one lead path, and limits the change to a small reviewed diff on shared files —
the exact class of change this project requires review for.

**Option B is justified only if** a lead must be created *without* the visitor completing a
form (e.g. campaign traffic that abandons at the result screen). If that is wanted, it must be
a conscious decision (OPEN item 3) with the lost email fallback acknowledged, and the checkup
must mint its own idempotency key.

---

## 9. Attribution decision

**Decision (RECOMMENDED, MVP)** — **Option A: carry the parameters.** Every link that points
at the checkup carries the campaign parameters through, and the result's primary CTA appends
them to the contact URL:

```
/r/status?campaign=ep001
   → /?utm_source=status&utm_campaign=ep001          (existing redirect, unchanged)
   → campaign link on the site: /business-checkup/?utm_source=status&utm_campaign=ep001
   → result CTA: /?service=fix-spam&utm_source=status&utm_campaign=ep001#contact
   → contact.js reads them on the homepage → #source_campaign = "ep001"   (unchanged)
```

**Reason** — `contact.js` reads `utm_*` from `location.search` on the homepage, which is
exactly where the final CTA lands. Passing them through in the link therefore restores the
full existing attribution chain **without modifying any shared file**. It is the smallest
possible change and it has no privacy cost (the parameters are already in URLs today).

**Trade-offs / failure modes:**

| Approach | Simplicity | Existing code | Privacy | Failure mode | Deployment risk |
|---|---|---|---|---|---|
| **A. Carry in URL** (RECOMMENDED) | Highest | **No change to shared files** | None (same as today) | Attribution is lost if the visitor bookmarks the bare `/business-checkup/` URL or arrives via a link that omitted the params | None |
| B. `sessionStorage` in `contact.js` | Medium | **Modifies a shared file used by every page and by the hero CTA swap** | Stores data client-side; needs a first-party notice if treated as tracking | Silent, hard-to-debug attribution drift if the write path fails | Touches the file with this project's documented repeated-failure history (shared code validated against one caller) |
| C. Other (cookie / server-side) | Low | New server behaviour, new consent questions | Highest cost | — | Not warranted for MVP |

**Implementation consequence:** the checkup page reads `utm_source`/`utm_campaign` from its own
URL and appends them to the CTA. If the params are absent, the CTA is simply
`/?service=<slug>#contact` — the existing behaviour, no error, no degraded path.

**DEFERRED:** sessionStorage persistence (Option B). Revisit only if measured data shows
attribution actually leaking across the checkup step — and if it is revisited, it is a
reviewed Extend to `contact.js`, not part of this MVP.

---

## 10. Data-storage decision

**Decision (RECOMMENDED, MVP)** — **Store nothing server-side.** Option A of HO-061 §12, in
its strictest form: not only are individual answers not stored, no diagnostic summary is sent
either, because the MVP handoff (§8) is the existing contact form. Answers live in the
browser for the duration of the run only.

| Option | Complexity | Lead usefulness | Reporting | Privacy | Future AI input | Reproduce a result | Migration risk |
|---|---|---|---|---|---|---|---|
| **A. Nothing stored** (RECOMMENDED, MVP) | None | Lower (only the slug + the visitor's message) | None | Best | None | Only within the same browser session | None |
| **B. `diagnostic_data JSONB` on `leads`** (phase 2) | Low–medium: one nullable column + one `insert_lead` parameter, **applied by hand** (no migration runner exists — `001_init.sql:2`) | Higher | Queryable | Same as any lead data | Good | Yes | Low if nullable/additive |
| **C. New table** (last resort) | High | Highest | Good | — | Good | Yes | High — new joins, new retention questions |

**Reason** — MVP has no requirement that depends on server-side answers. Storing them would add
a schema change, a manual migration on a live VPS, and personal-data retention questions, in
exchange for nothing the MVP does.

**What would justify changing it later (→ Option B):** any of (a) reports must be re-rendered
or emailed later; (b) checkup outcomes must be compared over time or across campaigns;
(c) the AI Action Plan (phase 5) needs the structured answers as input; (d) the business wants
to see aggregate "what are people failing at" reporting. JSONB is idiomatic here —
`dead_letters.payload` is already JSONB (`001_init.sql:41`).

**Session persistence (client-side, RECOMMENDED):** keep the in-progress run in
`sessionStorage` so a refresh mid-checkup resumes, and clear it after the result. No server
involvement, no cookie, no cross-site effect.

---

## 11. Public entry-point decision

**Decision (RECOMMENDED)** — Smallest viable discovery set:

1. **One navigation entry** — `template-parts/navigation.php:11-14` is a plain array that
   already supports non-anchor routes (`/blog` uses `'route' => true`), so this is one added
   line.
2. **The direct URL** `/business-checkup/` for campaigns, QR codes and social links —
   carry `utm_*` (§9).
3. **The existing campaign path** keeps working untouched: `/r/status?campaign=ep001` →
   homepage → (link the visitor onward).

**Explicitly not changed:** the hero (`section-hero.php`) and its conditional CTA swap
(`contact.js:200-208`). Two writers on that button is a known conflict class in this project.

**Trade-offs — and a real consequence:** any *visible* site-wide link means touching a shared
template, and the navigation markup is inside every page's **visual baseline**
(`tests/visual/visual-baseline/`, 15 routes). Adding the nav entry therefore requires
regenerating the baselines, which is precisely why it is an OPEN decision rather than a silent
inclusion. The alternative with zero shared-file impact is campaigns-only (direct URLs) for
MVP and the nav entry one release later, once the copy is validated.

| Entry point | Code impact | Baseline impact | Discovery |
|---|---|---|---|
| Nav entry (RECOMMENDED) | 1 line in `navigation.php` | Regenerate baselines | Site-wide |
| Offers-section contextual link | Small change inside `section-offers.php` | That section's baseline only | Contextual, high intent |
| Footer link | 1 line in `template-parts/footer.php` | Footer region of every page | Low |
| Campaign/direct URLs only | **Zero** | None | Requires traffic you control |
| Hero (secondary CTA) | Modifies the hero | Hero baseline + CTA-swap interaction | Highest visibility — **not recommended** for MVP |

**OPEN (item 2):** add the nav entry in MVP, or ship campaigns-only and add it after copy
review.

---

## 12. Mobile UX

Primary audience arrives from WhatsApp/social on a phone. **CONFIRMED**: GSAP and ScrollTrigger
are not loaded on mobile at all (`functions.php:104`, `wp_is_mobile()` gate), so the checkup
must be pure DOM/CSS with no animation dependency — which the plan already satisfies.

| Aspect | Specification |
|---|---|
| Question layout | One question per screen; question text ≥ 1.125rem; context line in `--ink-soft`; answer options as full-width stacked choices |
| Answer controls | Radio inputs styled as tappable cards (label wraps the input, so the whole card is the hit area) — **not** a `<select>`, which is harder to use on mobile and hard to style consistently |
| Button sizing | Minimum 44×44 px hit area (Apple/Android guidance); primary action full-width, `[Back]` as a smaller secondary control that never crowds the primary |
| Progress | "Question 3 of 8" + a thin bar; `role="progressbar"` with `aria-valuemin/max/now` |
| Result layout | Single column: band pill → dimension scores → findings (stacked cards) → priority → CTAs. No side-by-side grids on small screens |
| Scrolling | Scroll to top of the question block on every step change (respecting the global reduced-motion rule that sets `scroll-behavior: auto`) |
| Sticky controls | Question actions in a bottom bar so the primary action stays reachable without scrolling — using `env(safe-area-inset-bottom)` padding |
| WhatsApp CTA | Full-width secondary button, `target="_blank" rel="noopener noreferrer"` (matching `section-hero.php:91`), with the number from the existing `malachy_whatsapp` option — never hard-coded in JS |
| Input zoom | Any text input at ≥ 16px so iOS does not zoom on focus (MVP has no text inputs, so this is a guard for phase 2) |
| Breakpoints | Reuse the existing ones (767/768/1200) — no new breakpoints |

---

## 13. Accessibility

Minimum bar (all required, not "later"):

- **Keyboard**: every choice reachable by Tab; radio groups support arrow-key selection natively; Enter/Space advances; no keyboard trap; focus never lost on step change.
- **Focus management**: on each step change, move focus to the question heading (`tabindex="-1"` + `.focus()`) so screen readers announce the new question; visible focus ring from existing tokens (`--ring`).
- **Semantics**: each question is a `<fieldset>` with a `<legend>` holding the question text; the progress bar is `role="progressbar"`; the result screen announces itself.
- **Announcements**: a single `aria-live="polite"` region announces the step ("Question 3 of 8"), the completion, and the result headline once.
- **Labels**: every input has a real label; no placeholder-as-label.
- **Errors**: if validation blocks progress, an inline message tied by `aria-describedby`, plus focus moved to the offending group; never a colour-only signal.
- **Contrast**: use existing tokens (`--foreground` on `--background`, `--primary` on `--background`); the band pill must not rely on colour alone — it carries text.
- **Reduced motion**: nothing new needed — `main.css:2556` already neutralises animation/transition durations and scroll behaviour globally; the checkup adds no motion that would bypass it (and no GSAP).
- **Tap targets**: ≥ 44×44 px.
- **Zoom**: layout must survive 200% zoom without horizontal scrolling or clipped choices.

---

## 14. Security

**Acceptable for MVP (client-side, no enforcement needed):**

- A visitor editing the client-side score in devtools. The score is a self-assessment with no
  security value and no server consequence — it changes nothing downstream. Nothing may ever
  be granted, priced or promised based on the score.
- Oversized/malicious input in the checkup itself: MVP collects no free text and no personal
  data; answers are a fixed set of option tokens, so the inputs are inherently constrained.
- Attribution spoofing via `utm_source`/`utm_campaign`: **already possible today** — the
  backend stores `source_campaign` verbatim (`db.py:55`) and the parameters are user-supplied.
  The checkup neither worsens nor fixes this; it must not be treated as a new defect, but it
  must not be silently "validated" either.
- Spam/automated submissions: handled by the existing funnel the handoff uses — nonce, both
  honeypots and the 3-per-hour IP limit (`contact-handler.php:74-92`). The checkup introduces
  no new public write endpoint.

**Must be enforced server-side before any production expansion:**

- Any URL inspection (Website Health): allow-list schemes, resolve + reject private/loopback/
  link-local/metadata address ranges, cap redirects, cap response size, hard timeouts, and per-IP
  rate limiting — otherwise the service becomes an SSRF probe and a scanning proxy.
- Any DNS inspection (Email/Deliverability): per-IP rate limiting and caching, or the service
  becomes a DNS oracle for third parties.
- Any lead created *without* a completed form (Option B) — that endpoint needs the same
  arbitrary-input defences a public API needs.
- Any URL-carried diagnostic summary (if phase 2 chooses to pass one): treat as untrusted,
  length-cap it, sanitise it, and never render it as HTML.
- **Never** store answers or reports as files: the repo checkout is web-reachable (HO-058).

No new security infrastructure is proposed for the MVP: it adds no endpoint, no credential and
no new trust boundary.

---

## 15. Future capability boundaries (additive, no redesign)

| Capability | Boundary when added | Must not require |
|---|---|---|
| **Website Health** | New FastAPI route that fetches the visitor's own site server-side, returning a small findings object; the checkup page renders it in place of a deterministic question block | No change to the page's shape, the scoring contract, the result structure or the lead path |
| **Email / Deliverability** | New FastAPI route performing SPF/DKIM/DMARC/MX lookups (DNS library) — or a WP REST route if shared-host DNS proves workable (UNKNOWN, needs testing on the host) | No new table; results are transient and rendered |
| **WhatsApp Readiness** | Already the questionnaire dimension in MVP — no new boundary needed | — |
| **AI Action Plan** | Reuse **one** existing client, no third provider. **RECOMMENDED: the theme's existing OpenAI-compatible client** (`inc/ai-chat-bot.php:1306-1321`, `opencode.ai/zen/v1/chat/completions`, key server-side in PHP) because the report is a frontend-facing, page-scoped feature and that plumbing already exists and is proven in this theme; the FastAPI Anthropic client (`app/ai.py`) is currently admin-only (`/internal/draft`) and would need a new public route plus CORS consideration | No new provider, no new key management scheme, no new orchestration |
| **Analytics** | See §16 | No third-party tag manager required |

Each is additive: a phase adds a route, a question block and a result component; the journey,
the scoring contract and the handoff stay as specified.

---

## 16. Analytics plan

**CONFIRMED:** no analytics exist. Adding a mechanism is net-new infrastructure, so:

**MVP: none.** Rationale: the existing `clicks` table already answers the only MVP-level
question ("did the campaign link get used?"), and instrumenting the checkup would require a new
write path that HO-061 §19 says not to add now.

**Event model (for the phase where analytics is approved):**

| Event | Priority | Why |
|---|---|---|
| `checkup_complete` | **Phase-2 essential** | The single number that says whether the tool works |
| `checkup_view` | Phase-2 | Reach, and where the campaign traffic actually lands |
| `checkup_contact_click` | Phase-2 | The conversion step that matters |
| `checkup_start` | Phase-2 (same as view minus bounce; often merges with `checkup_view`) | Drop-off before the first question |
| `checkup_result_view` | Phase-2 | Distinguishes "completed" from "read the result" |
| `checkup_whatsapp_click` | Optional | Useful only if the WhatsApp CTA is a real channel |
| `checkup_question_answered` | Optional | Per-question drop-off analysis; only worth it once volume justifies it |
| `checkup_lead_created` | Optional | Only meaningful if the handoff ever becomes Option B (today the lead arrives through the contact form) |

**Cheapest future mechanisms, in order of preference:** (1) one small `POST /api/event` route in
the existing FastAPI service writing to a new `events` table — a manual migration, so it waits
for the phase where it is wanted; (2) **zero-migration alternative**: reuse the existing
`clicks(source, campaign, ip, ua)` table with a documented convention
(`source='checkup'`, `campaign=<event>`) — no schema change, at the cost of semantic
overloading; (3) a third-party tag manager — **not recommended**, it contradicts the brief's
dependency constraints.

---

## 17. File-level change surface

```
CREATE
  malachy-portfolio/template-business-checkup.php
      Page template (Template Name header) modelled on template-lead-magnet.php:
      intro → stepper → result, its own <main>, inline <script> OR an enqueued JS file.
      Depends on: nothing. Risk: low (new file, no shared code).
  malachy-portfolio/assets/js/business-checkup.js
      Question set, pure scoreCheckup(answers), result rendering, CTA construction.
      Exports the scoring function for tests (e.g. window.__checkupScore / module guard).
      Depends on: template markup ids/classes. Risk: low.
  malachy-portfolio/assets/css/business-checkup.css
      Bounded rules, every selector prefixed .checkup-*, reusing existing tokens only.
      Depends on: nothing. Risk: low (no global selectors).
  tests/visual/business-checkup.spec.ts   (or a new spec alongside the existing two)
  docs/handover/HO-062-… (this document) + the implementation handover when written.

MODIFY  (keep to the minimum; both are OPEN items)
  malachy-portfolio/functions.php
      Add ONE guarded enqueue: business-checkup CSS+JS only when
      is_page_template('template-business-checkup.php'). 3 lines, additive, no behaviour
      change on any other page. Depends on: the new files existing. Risk: low, but this
      function serves every page — hence the guard and hence review.
  malachy-portfolio/template-parts/navigation.php
      ONE array entry (label + '/business-checkup/' + 'route' => true). OPTIONAL (§11).
      Depends on: the page existing. Risk: low in code, non-zero in tests — it changes the
      homepage visual baseline, so baselines must be regenerated.

DO NOT TOUCH
  malachy-portfolio/assets/js/contact.js          dual-submit contract, hero CTA swap, ?service=
  malachy-portfolio/inc/contact-handler.php       nonce, both honeypots, 3/hour, wp_mail
  malachy-portfolio/template-parts/section-hero.php   hero markup + CTA
  malachy-portfolio/template-parts/section-contact.php existing form fields
  malachy-portfolio/assets/js/animations/*        GSAP pipeline, AnimationManager
  imad-automation/app/main.py                     /r/{source}, /api/leads, /healthz
  imad-automation/app/db.py, notify.py, scheduler.py, models.py, config.py
  imad-automation/migrations/001_init.sql         leads schema frozen for MVP
  .htaccess / docker-compose.yml                  unrelated open items (HO-058)

DEFER
  Website Health (server-side fetch + SSRF guards)
  Email / Deliverability (DNS)
  AI Action Plan (reuse the existing client)
  analytics infrastructure
  leads.diagnostic_data JSONB (migration 002)
  sessionStorage attribution persistence in contact.js
```

Implementer note: if the inline `<script>` route is chosen (as `template-lead-magnet.php:84-155`
does), `functions.php` does not need to be touched at all — that is the zero-shared-file
variant, at the cost of a slightly less maintainable file. The recommendation is the enqueued
file with the template guard.

---

## 18. Testing strategy

**Definition of required tests — no tests are written in this phase.**

### 18.1 Unit / logic (`node:test`, built-in, **zero new dependency**)

Design constraint: scoring must live in one pure function (`scoreCheckup(answers)` → band,
dimension scores, findings, priority, CTA slug) so it can be tested without a DOM.

Cases: each question's point values; band boundaries at every edge (3/4, 6/7, 5/6, 10/11,
2/3, 4/5); all-minimum and all-maximum runs; the tie-break rules (equal points lost →
earlier question; equal dimension percentages → Business Health); Q8 override showing both
stated and computed priority; unseen/absent Q8; unknown answer token rejected; findings capped
at 5 and ordered by points lost; CTA slug mapping for all four Q8 options; CTA construction
with and without `utm_*` present.

### 18.2 Browser (Playwright, existing harness: `tests/visual/config/visual.config.ts`)

1. Direct visit to `/business-checkup/` (no UTM) — loads, no console errors.
2. Campaign visit with `?utm_source=status&utm_campaign=ep001`.
3. Start the checkup (`[Start]` → Q1).
4. Answer all 8 questions (including a run that skips Q8).
5. Navigate backwards; assert the previous choice is still selected.
6. Complete the checkup → result rendered.
7. Result shows the expected band, dimension scores and findings for a fixed answer set (the
   deterministic assertions from 18.1, asserted through the DOM).
8. Primary CTA href is exactly `/?service=<slug>&utm_source=…&utm_campaign=…#contact` when UTM
   was present, and `/?service=<slug>#contact` when it was not.
9. Mobile viewport (e.g. 390×844): one question per screen, controls reachable, sticky bar not
   covering content.
10. Keyboard-only run: Tab/arrow/Enter through to the result.
11. Invalid/missing answer: advancing without selecting is blocked with an announced message.
12. Refresh mid-checkup: resumes from sessionStorage (if that sub-feature is included) or
    restarts cleanly — assert the chosen behaviour explicitly.

### 18.3 Regression (must all hold)

- Homepage renders and its visual baseline is unchanged — **unless** the nav entry (§11) is
  included, in which case the baseline is regenerated deliberately and the diff reviewed.
- Hero CTA unchanged: with `?utm_source=…` present the button text still becomes "Tell me your
  problem" and scrolls to `#contact` (`contact.js:200-208`).
- The existing contact form still submits and still reports success when either path succeeds.
- `/r/{source}?campaign=…` still logs a click and still redirects with the same UTM pair.
- The checkup page itself does not load GSAP (assert no `gsap` script tag on that page).

### 18.4 Acceptance criteria for the implementation phase

A test run counts as evidence only if it shows: the unit suite passing; all 12 browser
scenarios passing; the regression list above verified; and the raw command output pasted into
the implementation handover.

---

## 19. Corrections to HO-060 / HO-061 premises (must be recorded, not silently absorbed)

Found by re-inspecting the code during this specification pass:

1. **`.btn-secondary` does not exist.** HO-060 §4/§12 and HO-061 §15 both list it as reusable.
   Grep across CSS, PHP and templates: **zero occurrences**. Only `.btn-primary` exists (6
   rules in `main.css`). The checkup's secondary action (WhatsApp / Back) therefore needs a
   small `.checkup-*` class reusing `--secondary`, not an existing component.
2. **`.reveal` is not a styled component.** It has **zero CSS rules** in `main.css` yet appears
   as markup in 7 template-parts. The only JS that touches it is `global.js:16`, scoped to a
   journal container (`journal.querySelectorAll('.reveal')`). Homepage section animation comes
   from the per-section modules under `assets/js/animations/`, which are **front-page-only**
   (`functions.php:142`). Consequence: `.reveal` must **not** be used for the checkup's
   entrance — it would be inert at best; and the checkup must not depend on any front-page
   animation module for content visibility.
3. **A plain WordPress page renders with blog styling.** `page.php` wraps content in
   `<section class="blog-page"> … .single-post-content` with `padding:6rem`. A distinct checkup
   layout therefore genuinely requires its own page template (as `template-lead-magnet.php`
   does) — it is not merely a stylistic preference.
4. **Correction to my own earlier note in this session**: `prefers-reduced-motion` *is* handled
   — globally at `main.css:2556` and in three JS modules. Accessibility here is cheaper than I
   first reported: the global rule already neutralises new motion.
5. **No WhatsApp service slug exists** among the nine (`contact.js:100-110`). WhatsApp-dimension
   findings therefore cannot use `?service=<slug>`; they hand off through the existing `wa.me`
   CTA instead. Adding a WhatsApp offer slug is a content decision (OPEN item 8).

HO-060 §2/§5/§6/§8/§9/§10/§16 findings were re-verified in this pass and stand unchanged.

---

## 20. Acceptance criteria (HO-061 §22 checklist, resolved)

- [x] Public URL/slug defined — `/business-checkup/`, page template `template-business-checkup.php`; final public name OPEN (1)
- [x] Visitor journey defined — §4
- [x] MVP question set defined — §5 (8 questions, 6 scored)
- [x] Scoring algorithm defined — §6 (deterministic, explainable, tie rules stated)
- [x] Result states defined — §7 (3 overall bands × 2 dimension bands)
- [x] Result copy structure defined — §7 (9 elements + band skeletons); final wording OPEN (7)
- [x] Lead handoff method selected — §8 (Option A for MVP; A′ recommended for phase 2)
- [x] Attribution strategy selected — §9 (carry `utm_*` through; no `contact.js` change)
- [x] Data-storage strategy selected — §10 (store nothing; JSONB deferred with stated triggers)
- [x] Public entry-point strategy selected — §11 (one nav entry + direct/campaign URLs; add-vs-defer OPEN (2))
- [x] Mobile UX defined — §12
- [x] Accessibility requirements defined — §13
- [x] Security boundaries defined — §14 (MVP-acceptable vs must-be-server-side, separated)
- [x] Future Website Health boundary defined — §15
- [x] Future Email Health boundary defined — §15
- [x] Future AI boundary defined — §15 (reuse the theme's existing OpenAI-compatible client)
- [x] Analytics deferred/defined appropriately — §16 (none in MVP; event model + cheapest mechanisms)
- [x] Exact file change surface defined — §17 (CREATE / MODIFY / DO NOT TOUCH / DEFER)
- [x] Regression risks identified — §18.3 + §11 baseline consequence
- [x] Test plan defined — §18
- [x] No-overhaul constraint preserved — §1/§17: no backend, schema, notification or funnel change

### Open decisions requiring Malachy's approval

1. **Public name and slug** — "Business Checkup" may read as medical; the site's voice is
   plain-spoken ("bring me the problem"). Slug `/business-checkup/` is the working choice.
2. **Nav entry in MVP, or campaigns-only first?** — it changes the homepage visual baseline and
   is the only site-wide visible change proposed.
3. **Phase-2 handoff: A′ (additive field on the existing form) or B (direct `/api/leads`)?** —
   B loses the email fallback for that lead; A′ touches shared files and needs review.
4. **`leads.diagnostic_data JSONB`** — approve only when §10's triggers are met.
5. **Analytics mechanism** — approve the phase, and whether reusing `clicks` (zero migration) is
   acceptable despite semantic overloading.
6. **AI provider for the future Action Plan** — the theme's existing OpenAI-compatible client
   (recommended) vs the backend's Anthropic client; do not introduce a third.
7. **Result copy sign-off** — band wording, findings phrasing, and the "self-assessment, not an
   audit" disclaimer.
8. **WhatsApp service slug** — decide whether a WhatsApp-related offer slug is added so
   WhatsApp findings can pre-fill the contact form like the other areas, or stay `wa.me`-only.

---

## 21. Compliance statement

This document is the specification HO-061 §23 asked for. In producing it: **no theme file, no
backend file, no template, no JavaScript, no CSS, no migration, no API, no configuration was
created or modified, and nothing was committed, pushed or deployed.** The only artefact is this
document. Implementation begins only after this specification is reviewed and approved.
