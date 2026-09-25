---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: Integrate imad-automation backend + contextual hero CTA into existing website
date: 2026-09-20
from: Claude (architecture/backend)
to: OpenCode (mimo-2.5) — implementation
priority: NORMAL
---

## 1. Context — read this before touching anything

The business owner is replacing an earlier plan to use n8n with a small,
self-contained backend service (`imad-automation`, provided as
`imad-automation.zip`). It handles three things: lead intake from the
existing website, QR/link click tracking, and a daily stale-lead reminder.
It does **not** post to WhatsApp Status (no officially-supported API for
that exists) and does **not** run any AI-based lead scoring — those were
deliberately scoped out, do not add them back in.

The website already exists and is considered good — **do not redesign it**.
This handover is an integration task, not a rebuild. The two things you are
adding are: (1) merging the new lead-capture logic into the site's
**existing Contact section** (which already has contact info and a form —
do not add a second, separate form anywhere else on the site), and (2) a
small conditional behavior change on the two existing Hero Section buttons.

## 2. What you're integrating

`imad-automation.zip` contains a working, already-tested FastAPI backend:

```
imad-automation/
├── app/                        # backend service — deploy as-is, do not modify
│   ├── main.py                 # routes: /api/leads, /r/{source}, /healthz, /internal/*
│   ├── config.py, db.py, models.py, notify.py, ai.py, scheduler.py
├── migrations/001_init.sql     # run this against Postgres before first deploy
├── requirements.txt
├── Dockerfile
├── docker-compose.snippet.yml  # merge into the existing compose stack
├── website-embed/
│   └── brainstorm-form.html    # markup + JS to embed in the existing site — RESTYLE, don't redesign
└── README.md                   # full setup/ops instructions — follow this for backend deploy
```

**Read `README.md` inside the zip in full before starting.** It covers env
vars, Telegram bot setup, and the systemd/Docker deploy options. Don't
duplicate that content here — this handover covers integration into the
*existing website*, which the README doesn't know about.

## 3. Task 1 — Deploy the backend

Follow `imad-automation/README.md` steps 1–4 exactly:
1. Create the `imad` database and apply `migrations/001_init.sql` against
   the existing shared Postgres instance (do not create a new Postgres
   container — reuse what's already running).
2. Create the Telegram bot, get the token + chat ID.
3. Set the env vars listed in the README as actual secrets on the VPS
   (`.env` file or existing secrets mechanism) — never commit them to the
   repo.
4. Add the service block from `docker-compose.snippet.yml` into the site's
   existing `docker-compose.yml`, correcting the `networks:` entry to match
   whatever the real Traefik network name is on this VPS (check the
   existing compose file for the current network name — don't guess).
5. Deploy, then confirm: `curl https://api.imadconsulting.co.uk/healthz`
   returns `{"status":"ok",...}`.

**Acceptance for Task 1:** raw `curl` output from `/healthz` showing a 200
response, and raw `docker compose ps` output showing the container running
and healthy. Not a narrated "it's deployed" — the actual command output.

## 4. Task 2 — Merge lead capture into the EXISTING Contact section

There is already a Contact section on the site with contact info (email/
phone/socials) and a form. **Do not add a new form elsewhere.** The goal is
one section, one form, doing this job — not a second competing way to
reach out.

`website-embed/brainstorm-form.html` in the zip is a **reference
implementation** — it shows the required fields, the honeypot technique,
and the working `fetch()` submit logic. It is not meant to be pasted in
wholesale next to the existing form. Adapt its behavior into the existing
markup instead.

### 4a. Audit first — do not modify until you've confirmed this

Before changing anything, determine and report back:
- What fields does the current Contact form actually have? (name / email /
  phone / message — exact current field names and `name=` attributes)
- What does the current form **do on submit** right now? (a `mailto:`
  link, a POST to an existing backend endpoint, a third-party service like
  Formspree, etc.)
- If it currently sends you an email directly, flag this back explicitly
  before deciding whether to retire that path — do not silently remove an
  existing notification channel without confirming the new Telegram-based
  one is meant to fully replace it, not run alongside it.

### 4b. Field mapping

Map the existing form's fields onto the backend's expected schema
(`app/models.py` → `LeadIn`): `name`, `contact` (email or phone/WhatsApp —
whichever the existing form already collects is fine, don't force both),
`problem_text` (map to the existing message/textarea field). If the
existing field is literally labeled "Message," that's fine functionally —
whether the *placeholder wording* should shift toward "What are you trying
to achieve?" to match the brainstorm positioning is a copy decision, flag
it back rather than deciding unilaterally, same as the hero button copy in
Task 3.

### 4c. Implementation

- Add the honeypot field into the existing form's markup, using the exact
  same off-screen technique as the reference file
  (`position:absolute; left:-9999px` — **not** `display:none`, which some
  bots specifically detect and skip).
- Replace the existing submit handler with a `fetch()` POST to
  `https://api.imadconsulting.co.uk/api/leads`, building the JSON payload
  from the *actual* existing field values (see `brainstorm-form.html` for
  the exact payload shape and required `X-Form-Secret` header). Do not
  rename existing field `name=` attributes if other code depends on them —
  build the payload object in JS from whatever the current field names
  are.
- Replace `PASTE_YOUR_IMAD_FORM_SECRET_HERE` with the real
  `IMAD_FORM_SECRET` value.
- Leave the existing contact info (email/phone/socials) exactly as-is,
  visible alongside the form — some visitors will still want to just email
  or call directly rather than describe their problem in a form, and that
  path should keep working.
- Confirm the Contact section has (or add, if missing) a stable `id`
  attribute on the section wrapper — this becomes the scroll target for
  Task 3, replacing the placeholder `#imad-brainstorm-form` id used in the
  reference file.

**Acceptance for Task 2:** raw diff or before/after of the actual Contact
section markup showing the mapping decisions made in 4a/4b, plus a real
form submission (via the actual browser, not a raw curl) resulting in a
Telegram message arriving — screenshot or message text as proof, not a
description of having tested it.

## 5. Task 3 — Conditional Hero Section CTA

This is the new piece the business owner and I discussed this session —
not in the original backend package, implement fresh.

### Current state
Hero Section has two buttons:
- **"View my project"** → routes/scrolls to the project section
- **"Book a discovery call"** → opens a sidebar with contact info

### Required behavior
Both buttons **stay exactly as they are today for organic visitors**. The
change only applies when the visitor arrived via a tracked campaign link
(i.e. through the backend's `/r/{source}` redirect, which appends
`?utm_source=...&utm_campaign=...` to the homepage URL).

**When `utm_source` is present in the URL on page load:**
- "View my project" button's **label** changes to something continuing the
  conversation — e.g. `Continue — tell me your problem` (exact copy is the
  business owner's call, not yours to invent freely — flag it back if the
  existing hero copy conventions suggest something different)
- Its **action** changes from scrolling to the project section to
  scrolling to the existing **Contact section** (the same section modified
  in Task 2 — use its actual `id`, not the placeholder
  `#imad-brainstorm-form` name from the reference file)
- The `utm_campaign` value from the URL should be read and passed into the
  form's `source_campaign` hidden field (or set via JS on the form object)
  so the lead record in the database reflects which Status episode/QR code
  produced it — this ties directly into the `source_campaign` column
  already in the `leads` table schema (see `migrations/001_init.sql`)

**When no `utm_source` is present** (direct visit, organic search,
LinkedIn, etc.): both buttons behave exactly as they do today. No visible
change for the majority of visitors.

**"Book a discovery call" is unaffected in both cases** — do not touch its
behavior.

### Implementation approach
This is a small client-side JS addition, not a backend or routing change:

```js
// Add to the site's existing hero/page-init JS — do not create a new
// separate script file unless the site's existing structure requires it
(function () {
  const params = new URLSearchParams(window.location.search);
  const source = params.get('utm_source');
  const campaign = params.get('utm_campaign');
  if (!source) return; // organic visitor — leave hero buttons untouched

  const viewProjectBtn = document.querySelector('[SELECTOR_FOR_EXISTING_BUTTON]'); // find actual selector in current markup
  if (!viewProjectBtn) return;

  viewProjectBtn.textContent = 'Continue — tell me your problem';
  viewProjectBtn.onclick = function (e) {
    e.preventDefault();
    document.getElementById('SELECTOR_FOR_CONTACT_SECTION')?.scrollIntoView({ behavior: 'smooth' });
  };

  // Carry the campaign forward so the lead record shows which content drove it
  if (campaign) {
    const campaignField = document.querySelector('#SELECTOR_FOR_CONTACT_SECTION [name="source_campaign"]');
    if (campaignField) campaignField.value = campaign;
    else window.__imad_campaign_override = campaign; // fallback if the Task 2 handler reads this instead
  }
})();
```

Note the two placeholders — **find and use the actual existing
selector/id for the "View my project" button and the Contact section
wrapper in the current codebase.** Do not guess or add new classes/ids
just to make this easier; use what's already there so this stays a
minimal diff. This is now a genuinely small change: no new section, no
modal, just a scroll target that already exists.

The Contact form (Task 2) needs a `source_campaign` field — hidden, since
the visitor never needs to see it — that this script writes into before
submit. Add one hidden input to the existing form for this if it doesn't
already have an equivalent field, rather than inventing a separate
mechanism.

**Acceptance for Task 3:**
- Visiting the homepage with `?utm_source=status&utm_campaign=ep001`
  manually appended to the URL shows the swapped button copy and, on
  click, scrolls to the Contact section — raw screen recording or
  screenshot, not a description.
- Visiting the homepage with no query params shows the original,
  unchanged "View my project" behavior — same proof standard.
- A test lead submitted via the swapped-button path shows
  `source_campaign = 'ep001'` in the `leads` table — raw `psql` query
  output confirming the column value, not a narrated "it worked."
- Confirm there is exactly one contact/lead form on the site after this
  work — not two.

## 6. Explicit constraints — do not do these

- Do not redesign or restyle any part of the site beyond what Tasks 2–3
  require.
- Do not remove or alter "Book a discovery call" in any way.
- Do not add WhatsApp Status publishing automation of any kind (see §1).
- Do not add AI-based lead scoring/classification to the intake flow.
- Do not commit `IMAD_FORM_SECRET`, `IMAD_ADMIN_API_KEY`, or any other
  secret to the repository, in code, in comments, or in commit messages.
- Do not change the honeypot's CSS hiding technique (see Task 2).
- Do not create a new Postgres instance — the migration targets the
  existing shared instance.
- Do not invent new hero copy beyond a reasonable placeholder — flag the
  exact wording back for confirmation rather than shipping your own final
  copy choice silently. Same applies to any Contact form placeholder-text
  changes in Task 2.
- Do not create a second form anywhere on the site. If Task 2's audit
  (§4a) finds the existing form's current submit behavior (e.g. a direct
  email) seems worth preserving alongside the new pipeline, flag that back
  as a question rather than deciding to run both silently.

## 7. Reporting back

When complete, report with:
- The actual commit hash(es), not a narrated "committed and pushed"
- Raw output for each acceptance criterion in §3–5 above
- Explicit confirmation of the exact CSS selector/id used for the "View my
  project" button and for the Contact section wrapper (so it's on record
  what Task 3 actually hooked into)
- Any deviation from this handover, stated explicitly with the reason —
  do not silently resolve an ambiguity and only mention it if asked
