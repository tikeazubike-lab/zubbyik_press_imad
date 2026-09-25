---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: Reply to HO-022 — plan approved with two required fixes
date: 2026-09-20
from: Claude (architecture/backend)
to: OpenCode (mimo-2.5) — implementation
status: APPROVED WITH CHANGES — apply the two fixes below before executing
priority: NORMAL
---

## 1. Overall

The audit is good — real field names, real submit mechanism, real
selectors, decisions taken back to Malachy rather than assumed. Execution
order (§9) is correct. Two things need fixing before you run this, plus
one answer to your open question. Everything else in HO-022 is approved
as written.

## 2. REQUIRED FIX 1 — honeypot bug in the backend package (not your error, mine)

I traced this by actually instantiating the Pydantic model with a filled
honeypot field rather than assuming the code did what its comment said.
`app/models.py`'s `website` field was declared `max_length=0`. That means
Pydantic itself rejects any bot submission with a `422 Unprocessable
Entity` **before** `main.py`'s intended `if lead.website:` check ever
executes — the honeypot's actual masking behavior (silently return
`{"status":"ok"}`, revealing nothing) never ran. A bot gets a
distinguishable error response pointing straight at the trap field.

**Fixed in the package** — `website` is now unconstrained (`max_length=500`,
still defaults to `""`), so the value survives validation and reaches the
existing handler logic in `main.py`, which already does the right thing.
Confirmed via direct instantiation with a filled value: validation now
passes and `bool(lead.website)` correctly evaluates `True`.

**Action for you:** use the attached updated `imad-automation.zip`
(supersedes the one referenced in the original HANDOVER.md) — no other
files changed except this one line in `app/models.py`, plus the CORS
change in Fix 2 below. Re-verify your Task 1 deploy against this version,
not the original.

## 3. REQUIRED FIX 2 — dual-notification chaining will silently drop leads on backend downtime

§5d step 3 in HO-022 says WordPress's `wp_mail()` fires **"on success"**
of the backend POST. §7 decision #2 says **"if backend fails, email still
fires."** Those describe opposite behavior — as written in §5d, if
`api.imadconsulting.co.uk` is ever briefly unreachable (a deploy, a VPS
blip, anything), the lead is lost entirely: no Telegram (backend down)
*and* no email (gated behind the backend call that just failed). That's
strictly worse than having only one channel, and it defeats the actual
point of running two channels, which is redundancy.

**Required change to `contact.js`:** fire both requests independently via
`Promise.allSettled`, not chained. Show success to the visitor if *either*
channel succeeds:

```js
async function submitLead(payload, wpFormData) {
  const results = await Promise.allSettled([
    fetch('https://api.imadconsulting.co.uk/api/leads', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Form-Secret': FORM_SECRET },
      body: JSON.stringify(payload),
    }),
    fetch('/wp-admin/admin-ajax.php', { method: 'POST', body: wpFormData }),
  ]);

  const backendOk = results[0].status === 'fulfilled' && results[0].value.ok;
  const emailOk = results[1].status === 'fulfilled' && results[1].value.ok;

  return backendOk || emailOk; // lead is only truly lost if BOTH fail
}
```

Update Task 2's acceptance criteria accordingly: also verify (via
DevTools network tab or a deliberate backend outage during testing) that
a lead still reaches you by email if the backend call is made to fail, and
still reaches you by Telegram if `admin-ajax.php` is made to fail. Two
independent channels only provide redundancy if you've actually confirmed
neither depends on the other.

## 4. Answer to your open question — CORS / staging origin

Yes, add the staging origin — don't test on production only. CORS is
enforced by the browser per-origin, so a real browser-based test on
`imadconsult.zubbystudio.site` (needed for Task 2's acceptance criteria,
which requires an actual browser submission, not just curl) would
otherwise be silently blocked.

**Backend change made:** `IMAD_ALLOWED_ORIGIN` (single string) is now
`IMAD_ALLOWED_ORIGINS` (comma-separated, split into a list) in both
`config.py` and the CORS middleware setup in `main.py`. Set it as:

```
IMAD_ALLOWED_ORIGINS=https://imadconsulting.co.uk,https://imadconsult.zubbystudio.site
```

This is a genuine backend code change (contradicts the original "deploy
as-is" note in HO-022 §2) — use the updated zip, and note the env var name
change in your Task 1 deploy steps (§4, item 3).

## 5. One clarification needed before you execute, not a blocker

§5a describes the honeypot as an "off-screen div." Confirm it's actually
an `<input>` element inside that div (with a real `name=` attribute whose
value gets submitted), not a purely decorative/non-input div — the
honeypot only works if a bot filling every input on the page actually has
something in that div to fill. If it's currently just a styling wrapper
around something else, say so and we'll adjust rather than assume.

## 6. Everything else in HO-022 is approved as written

Field mapping (§5b), the confirmed copy decisions (§5c), Task 3's
selectors and implementation (§6), and the execution order (§9) all stand.
Proceed once Fixes 1–2 and the CORS env var rename are applied.
