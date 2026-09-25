---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-049 — Title/meta consistency fixes, REST nonce parity, GSC placeholder removed (v1.3.15)
date: 2026-09-23
from: MiMo-V2.6-Pro (Architect)
to: Claude[Sonnet] Web (Reviewer) / ChatGPT (Co-reviewer)
status: COMPLETE on staging — production deploy PENDING as its own tracked step
priority: HIGH
---

## 1. What this unit of work is

Tasks 1, 4, and 5 from the approved sequence: fix the title/meta
inconsistencies in the theme, close the REST nonce gap, and gate/remove the
Google Search Console placeholder. Code is verified on staging (bind-mounted
= live immediately). **Not yet deployed to production** — that is the next
tracked step and needs its own verification.

## 2. Problems found (raw evidence, pre-fix)

### Staging vs production disagree on the tagline

```
=== STAGING HOME (before) ===
name="description" content="IMAD Consulting helps businesses fix email deliverability, ... QA engineering, system administration, and IT support."
title: IMAD Consulting — QA Engineer, Web Development & IT Support Specialist

=== PRODUCTION HOME (before) ===
name="description" content="Portfolio · 2026"
title: IMAD Consulting — QA Engineer, Web Development & IT Support Specialist
```

Root cause: `header.php:8` was
`get_bloginfo('description') ?: '<curated text>'`. The `?:` fallback only
fires when the tagline is **empty**. Staging's tagline is empty → curated
text (with the stale "system administration" wording). Production's tagline
is the stale `Portfolio · 2026` → non-empty → fallback never fires.

**This means HO-047's claim that the meta description was fixed "pending
production deploy" was false for production**: deploying that code could
never change production's meta description, because production's tagline is
non-empty. HO-047's §2 "After" block showed the curated text; production
actually serves `Portfolio · 2026`.

### Subpage titles leak the pre-rebrand blogname

```
=== PRODUCTION /thank-you/ ===
<title>Thank You &#8211; Reliable &#8211; QA Engineer, SysAdmin &#038; IT Support</title>

=== PRODUCTION /blogs/ ===
<title>IMAD Consulting — QA Engineer, Web Development &#038; IT Support Specialist &#8211; Reliable &#8211; QA Engineer, SysAdmin &amp; IT Support</title>
```

WP core (`wp-includes/general-template.php:1511-1522`) builds `<title>` by
joining non-empty parts of `document_title_parts`: `title`, `page`,
`tagline`, `site`. The `site` part is the blogname on non-front views.
`malachy_seo_title()` only touched `title` and `tagline`, and only on
front/home — so:

- subpages got the stale DB `blogname` (`Reliable - QA Engineer, SysAdmin & IT Support`) appended;
- `/blogs/` (posts page, `is_home()` true) got the full custom title **plus**
  the stale blogname → doubled string.

Root cause of the stale value: `blogname` was being actively re-poisoned by
two writers — `functions.php:232` (Reconcile Content button) and
`inc/data-seeder.php:101` — both writing
`'Reliable - QA Engineer, SysAdmin & IT Support'`. Staging's DB confirmed:

```
blogname=Reliable - QA Engineer, SysAdmin & IT Support
```

### GSC placeholder shipping to production

```
=== PRODUCTION HOME ===
google-site-verification" content="YOUR_VERIFICATION_TOKEN"
```

### REST permission_callback nonce gap

`inc/contact-handler.php:30-32` read only `$_POST['malachy_nonce']`, while
`malachy_process_contact()` accepts either `malachy_nonce` or
`discovery_nonce` (HO-041). Same shared-code/one-caller pattern that broke
twice before. Note: **no JS currently calls the REST route** (both forms
post to `admin-ajax.php`), so this was latent — but it's the registered
public route and had to match.

## 3. Changes made (all files `php -l` clean)

### `header.php`

1. **Meta description** — hardcoded curated text; deliberately does **not**
   read `blogdescription` (production's tagline stays stale until Malachy
   edits it in WP admin; code no longer depends on it). Wording updated
   `system administration` → `web development` to match `6389f46`.
   Same text on every page until per-page descriptions arrive with the SEO
   plugin (Phase 1, blocked on Malachy).
2. **GSC meta tag** — removed the `YOUR_VERIFICATION_TOKEN` placeholder;
   now emitted only when `get_option('malachy_gsc_token')` is non-empty.
3. **JSON-LD `jobTitle`** — `QA Engineer, SysAdmin & IT Support Specialist`
   → `QA Engineer, Web Development & IT Support Specialist`.

### `functions.php`

1. `malachy_seo_title()` now controls all three relevant parts:
   - front/home: full brand title, `tagline` cleared, `site` cleared
     (kills the `/blogs/` doubling);
   - every other view: `site` forced to `IMAD Consulting` (kills the stale
     blogname suffix regardless of DB state).
2. Reconcile Content button: `blogname` writer changed from the stale
   `Reliable - ...` string to `IMAD Consulting`.
3. `malachy_gsc_token` registered via `register_setting` +
   settings-page field (Settings → Malachy Portfolio), with description
   text explaining the meta tag is hidden until set.
4. Version → `1.3.15`.

### `inc/data-seeder.php`

Seeder's `blogname` writer: same stale string → `IMAD Consulting`.

### `inc/contact-handler.php`

REST `permission_callback` now accepts **either** nonce field name, read
via `$request->get_param()` (works for JSON bodies, not just `$_POST`),
returning `(bool) wp_verify_nonce(...)` — parity with
`malachy_process_contact()`.

### `style.css`

`Description:` header updated (stale `Reliable — ... SysAdmin` →
`IMAD Consulting — ... Web Development`); `Version: 1.3.15`.

### Staging DB (one-off, not code)

`blogname` updated in staging's DB (`Reliable - ...` → `IMAD Consulting`) so
the DB itself no longer carries the stale value; the code fix above means
production renders correctly even before its DB is touched.

## 4. Verification — raw output (staging, post-fix)

```
=== STAGING HOME ===
name="description" content="IMAD Consulting helps businesses fix email deliverability, migrate to Microsoft 365, secure their domain, and modernize WordPress with AI chatbot integration. QA engineering, web development, and IT support."
<title>IMAD Consulting — QA Engineer, Web Development &#038; IT Support Specialist</title>
"jobTitle": "QA Engineer, Web Development & IT Support Specialist",
ver=1.3.15
google-site-verification count: 0 (absent)

=== STAGING /thank-you/ ===
<title>Thank You &#8211; IMAD Consulting</title>

=== STAGING offer page ===
<title>Fix Business Emails Going to Spam &#8211; IMAD Consulting</title>

=== REST route (staging) ===
no nonce:          HTTP 401 rest_forbidden
bogus nonce:       HTTP 401 rest_forbidden
valid malachy_nonce:    HTTP 500 {"code":"mail_failed",...}  ← passed permission, reached handler
valid discovery_nonce:  HTTP 500 {"code":"mail_failed",...}  ← second field name accepted

=== admin-ajax path (unchanged, regression check) ===
invalid nonce:     HTTP 200 {"success":false,"data":{"message":"Security check failed..."}}
honeypot filled:   HTTP 200 {"success":true,"data":{"message":"Thank you! Your message has been sent."}}  ← fake success, no mail attempted
empty honeypot:    HTTP 200 {"success":false,"data":{"message":"Could not send message..."}}  ← full path reached wp_mail (expected fail: staging has no working mail)
```

`mail_failed` on staging is expected (no configured outbound mail on the
staging WP) — the point is the request **passed the permission callback and
reached the handler**, proving both nonce field names work.

### 4b. Production honeypot re-test (closes context outstanding item 1)

Ran against live production (v1.3.14) 2026-09-23, nonce scraped from the
live page (`d93c2c4b44`):

```
=== PROD honeypot: website filled + valid nonce ===
HTTP 200 {"success":true,"data":{"message":"Thank you! Your message has been sent."}}   ← fake success (caught)

=== PROD honeypot: malachy_hp filled + valid nonce ===
HTTP 200 {"success":true,"data":{"message":"Thank you! Your message has been sent."}}   ← fake success (caught)

=== PROD legacy discovery_nonce field name + valid value, no honeypot ===
HTTP 200 {"success":true,"data":{"message":"Thanks! I'll get back to you soon."}}       ← handler accepted legacy name; wp_mail returned true

=== PROD invalid nonce ===
HTTP 200 {"success":false,"data":{"message":"Security check failed. Please refresh and try again."}}
```

Both honeypot field names caught; legacy nonce field name accepted;
invalid nonce rejected. The no-honeypot request is the first fresh
handler-level `wp_mail` success on production (sends one test email to the
admin inbox from "DiscTest" — usable as the inbox-check candidate for
outstanding item 2, which stays open until a received-email screenshot
exists). Rate-limit order verified in code: nonce → honeypot → rate-limit
increment, so the honeypot probes did not consume rate-limit quota.

Production pre-deploy state (unchanged, for the deploy step to compare):

```
=== PRODUCTION (still v1.3.14, still buggy) ===
contact.js?ver=1.3.14
google-site-verification" content="YOUR_VERIFICATION_TOKEN"
name="description" content="Portfolio · 2026"
<title>Thank You &#8211; Reliable &#8211; QA Engineer, SysAdmin &#038; IT Support</title>
```

## 5. Findings noted, NOT fixed here (flagged for decisions)

1. **`IMAD_FORM_SECRET` in client JS** (`assets/js/contact.js:97`, present
   since `4524cde`). Cross-checked HO-036, which scanned git history and
   explicitly ruled this **intentional**: it's a browser-visible shared
   secret for the `X-Form-Secret` header by design (the FastAPI route
   requires it; real abuse protection is rate-limiting + honeypot, not the
   secret's secrecy). No action taken — rotating it would require a
   coordinated JS + backend `.env` change. Flagging because a future scan
   will hit it again.
2. **Settings fields that silently don't save**: `malachy_phone`,
   `malachy_whatsapp`, `malachy_twitter` have inputs on the settings page
   but **no `register_setting()`** — saving the form discards them.
   (`malachy_portrait`, `malachy_hero_subtitle`, `malachy_resume_url`,
   `malachy_contact_email`, and now `malachy_gsc_token` are registered;
   grep-confirmed.) Pre-existing; not in this task's scope. Needs a small
   follow-up.
3. **Chatbot persona strings still say "SysAdmin"** in
   `inc/ai-chat-bot.php:705,722,756` — same rename as `6389f46` but in the
   chatbot's context/prompt. Out of scope for title/meta; flagging for
   content consistency.
4. **Staging `/blogs/` is a 404** (no posts page exists on staging;
   `show_on_front=posts`). Production has `/blogs/` as the posts page.
   The title fix covers both cases, but staging can't be used to
   eyeball the production `/blogs/` doubling — verified via the `site`
   part logic instead.

## 6. Deviations from plan

None material. One addition beyond the three assigned tasks: the two
`blogname` **writers** (reconcile button + seeder) were also fixed, because
leaving them would re-poison the DB on the next reconcile/seeder run —
root-cause fix, not scope creep. Staging's DB `blogname` was updated
directly (stated above); production's DB is left alone (code renders
correctly regardless; WP-admin edit optional for Malachy).

## 7. Production deploy — NEXT STEP (own tracked item, not done)

- [ ] Commit theme changes (v1.3.15) + these handovers
- [ ] Deploy to `imadconsulting.co.uk` (method: whatever produced v1.3.14 —
      **undocumented gap**, see HO-047/context; needs recording)
- [ ] Verify production raw output matches §4 staging block (title, meta,
      jobTitle, no GSC placeholder, `ver=1.3.15`, subpage titles)
- [ ] Optional: set `blogname` = `IMAD Consulting` in production WP admin
      (Settings → General) — no longer required for correct rendering
- [ ] Malachy: paste GSC token into Settings → Malachy Portfolio when ready

## 8. Records corrected in this session (task 3)

- `docs/handover/Imad-project-context.md` — outstanding items 1/3/4/5/6
  updated to actual state; version → 1.3.15 pending deploy.
- `docs/handover/HO-046-field-parity-audit.md` §6 — dated correction note.
- `docs/handover/HO-047-seo-phase0-execution.md` — dated errata on the
  meta-description claim (see §2 above).
- `docs/handover/HO-048-*.md` — backfill for the two undocumented commits.
