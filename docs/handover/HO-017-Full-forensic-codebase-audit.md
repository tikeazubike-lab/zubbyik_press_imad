TASK: Full forensic codebase audit of [portfolio site] — handover
document for Claude review. Investigation only. Do not edit, rename,
delete, reformat, or commit anything. The repository must remain
completely unchanged.

CONTEXT: This site is being repurposed into a lead-capture funnel. Do
not invent capabilities, services, technologies, results, testimonials,
or claims. Every conclusion must cite its evidence (file + section).
If something can't be confirmed from the repo, mark it UNKNOWN — never
infer, never guess.

---

## 1. REPOSITORY MAP
Recursively inspect the full codebase, not just the obvious template
directory. Identify: framework, language(s), build system, dependencies,
entry points, pages/templates, components, layouts, data/config files,
assets, styling system, animations, forms, API integrations, analytics,
SEO config, CMS/content sources, deployment/CI config, README/docs,
TODO/FIXME items, and any unused or apparently abandoned code.

## 2. FILE-BY-FILE CONTENT DISCOVERY
Extract every meaningful piece of user-facing content verbatim — not
summarized — from every page/template that exists (Hero, About, Skills,
Projects, Experience, Blog, Contact, and anything else found): headings,
body copy, nav labels, CTA/button text, form labels, footer content,
meta titles/descriptions, Open Graph tags, schema markup, alt text.
Also search inside JS/TS objects, JSON, YAML, CSS, component props,
constants, and mock data. Flag any placeholder/lorem-ipsum/unfinished
content still present.

## 3. POSITIONING — WHAT THE SITE CURRENTLY SELLS
From evidence only: what does the business appear to be, who does it
serve, what problem does it solve, what services are explicitly stated
vs. only implied by what's built. Document every CTA: location, action,
destination, and whether it's functional or decorative.

## 4. LEAD CAPTURE AUDIT
For every mechanism found (contact forms, email/WhatsApp/phone links,
newsletter signup, chat widgets, webhooks, CRM integrations), produce:

| Mechanism | Location | Trigger | Destination | Data Captured | Working/Uncertain/UI-only/Dead |

Do not assume something works because the UI exists — check the actual
submission path.

## 5. TECHNICAL ARCHITECTURE
Concise diagram + description of the real flow: browser → frontend →
form/API handling → external services → lead destination (email/DB/CRM).
Identify the exact files implementing each hop.

## 6. ROUTE / PAGE INVENTORY
Every route: source file, purpose, main sections, primary/secondary CTA,
SEO metadata, lead mechanism, key dependencies. Include unlinked/orphan
routes too.

## 7. COMPONENT INVENTORY
Table of reusable components (file, purpose, used by), with particular
attention to Hero, service/skill cards, portfolio cards, CTA, contact
form, nav, footer, and any chatbot/analytics components.

## 8. ASSET INVENTORY
Every image/icon/font: filename, type, where used, apparent purpose,
whether it looks unused. Mark UNKNOWN rather than guessing what an
image represents if it can't be confirmed from code/context.

## 9. SECURITY RE-VERIFICATION (mandatory — do not skip)
This site had a confirmed WordPress-root malware backdoor
(`compress.zlib://wp-slgnup.gz`, found and removed in HO-012). Re-check
current state with actual commands/output, not assumption:
- WordPress core + every plugin version, checked against current known CVEs
- Diff of ALL core files against a clean WordPress download (not just index.php)
- Any `.php` files inside `wp-content/uploads/`
- Full admin user list — flag anything unrecognized
- Crontab contents (`crontab -l`, `/etc/cron.d/`)
- Full `.htaccess` contents
- File permissions on `wp-config.php` and key directories (flag anything world-writable)

## 10. OPEN TECHNICAL DEBT RE-CHECK (from HO-012)
Confirm actual current state, not assumed-fixed, of:
- `prefers-reduced-motion` support on the hero scroll animation
- `aria-live` region on the rotating quote text
- Font-loading scope (are all 3 fonts still loaded site-wide?)
- Desktop fallback behavior if GSAP/JS fails to load
- Mobile animation fallback (still fade-only by design?)
- Console errors on staging — get the actual list, not just a count

## 11. VERIFIED vs. IMPLIED vs. UNKNOWN
Classify every finding relevant to positioning/capability into exactly
one of:
- VERIFIED IMPLEMENTATION — demonstrably built
- VERIFIED CONTENT — explicitly stated on the site
- IMPLIED CAPABILITY — reasonably suggested but not stated
- UNKNOWN — cannot be established from the repo
Never let an Implied Capability get written up as a marketing claim.

## 12. CONTENT / POSITIONING ISSUES
Evidence-based only (contradictions, vague/unsupported claims, outdated
content, weak/missing CTAs, unclear audience, services in code but not
explained). Format each as: Evidence → Location → Why it matters →
Confidence.

## 13. BUSINESS CAPABILITY INVENTORY (for Claude)
- Services (evidence-supported only)
- Technical capabilities (frameworks, infra, integrations)
- Portfolio/case studies: name, problem, work performed, tech, outcome,
  evidence source, what can safely be claimed, what must NOT be claimed
- Differentiators and trust signals (only if evidence-backed)
- Conversion opportunities the current build already supports

## 14. SECOND-PASS KEYWORD SWEEP
Grep the full repo for: TODO, FIXME, placeholder, lorem, testimonial,
case study, contact, mailto, whatsapp, phone, webhook, API, chatbot,
CRM, calendar, booking, analytics, schema, og:, twitter:, canonical,
SPF, DKIM, DMARC, SMTP, DNS. Reconcile any new findings against the
inventory above.

## 15. OUTPUT
One Markdown file: `WEBSITE_CODEBASE_HANDOVER.md`, sections numbered to
match this list. End with:

INVESTIGATION RESULT
Files inspected:
Routes identified:
Components identified:
Lead mechanisms identified (and working/uncertain/dead breakdown):
Security re-check: PASS / ISSUES FOUND (list)
Tech-debt items still open:
Unknowns requiring clarification:
Files modified: 0
Git changes made: 0
