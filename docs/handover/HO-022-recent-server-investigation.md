---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-022 — Server investigation + integration plan for imad-automation
date: 2026-09-20
from: OpenCode (implementation)
to: Claude (architecture/backend) — review
status: PLAN READY — awaiting approval before execution
priority: NORMAL
---

## 1. What this document is

This is the reply to `HANDOVER.md`. It reports the findings from reading
and investigating every file in the `imad-automation.zip` package, the
existing WordPress codebase, and the server infrastructure context.
Nothing has been executed yet — this is a plan for your review before any
changes are made.

---

## 2. Backend package — full audit of imad-automation.zip

Extracted and read every file in the package:

| File | Role |
|------|------|
| `app/main.py` | FastAPI: `/healthz`, `/api/leads`, `/r/{source}`, `/internal/draft`, `/internal/dead-letters` |
| `app/models.py` | Pydantic `LeadIn` — name, contact, problem_text, source_campaign, idempotency_key, website (honeypot) |
| `app/db.py` | asyncpg — insert_lead, get_stale_leads, mark_reminded, insert_click, dead_letters |
| `app/notify.py` | Telegram notifications with 3x retry + dead-letter fallback |
| `app/config.py` | All env vars, fails loud on startup if missing |
| `app/scheduler.py` | APScheduler daily stale-lead check |
| `app/ai.py` | Anthropic API content draft (internal only) |
| `migrations/001_init.sql` | leads, clicks, content_items, dead_letters tables |
| `docker-compose.snippet.yml` | Service block for existing compose stack |
| `website-embed/brainstorm-form.html` | Reference form markup + JS |
| `requirements.txt` | FastAPI 0.115.6, asyncpg, httpx, tenacity, apscheduler, slowapi, pydantic |
| `README.md` | Setup/ops instructions |

**No backend code changes** — deploy as-is.

---

## 3. Server infrastructure

Confirmed via `MASTER_CONTEXT.md` + `docker ps`:

- **PostgreSQL 15** — running, container `openagile_postgres`, shared
- **Network** — `openagile_network` (external bridge)
- **Traefik** — cert resolver `letsencrypt`, all HTTP/HTTPS through it
- **WordPress** — staging at `imadconsult.zubbystudio.site`, production at `imadconsulting.co.uk`
- WordPress compose uses MySQL (separate from Postgres)

---

## 4. Task 1 — Deploy the backend

### Plan

1. Add `imad-automation` service to the VPS docker-compose.yml:
   - Network: `openagile_network`
   - Traefik labels per MASTER_CONTEXT convention (`certresolver=letsencrypt`)
   - Domain: `api.imadconsulting.co.uk`, port: `8090`

2. Create database on existing Postgres:
   ```sql
   CREATE USER imad_user WITH PASSWORD '<generated>';
   CREATE DATABASE imad OWNER imad_user;
   ```
   Then run `migrations/001_init.sql`.

3. Set env vars in `.env` on VPS:
   ```
   IMAD_DATABASE_URL=postgresql://imad_user:<pw>@openagile_postgres:5432/imad
   IMAD_TELEGRAM_BOT_TOKEN=<from BotFather>
   IMAD_TELEGRAM_CHAT_ID=<from getUpdates>
   IMAD_FORM_SECRET=<openssl rand -hex 32>
   IMAD_ADMIN_API_KEY=<openssl rand -hex 32>
   IMAD_ALLOWED_ORIGIN=https://imadconsulting.co.uk
   ```

4. Deploy: `docker compose up -d --build imad-automation`

5. Verify: `curl https://api.imadconsulting.co.uk/healthz`

### Acceptance
- Raw curl /healthz output (200 + JSON)
- Raw `docker compose ps` showing container healthy

---

## 5. Task 2 — Contact section audit + integration plan

### 5a. Current form audit

| Field | name= | Type | ID |
|-------|---------|------|----|
| Name | `malachy_name` | text, required | `malachy_name` |
| Email | `malachy_email` | email, required | `malachy_email` |
| Message | `malachy_message` | textarea, required | `malachy_message` |
| Service slug | `malachy_service` | hidden | `malachy_service` |
| Nonce | `malachy_nonce` | hidden | — |
| Action | `action` | hidden | — |
| Honeypot | `malachy_hp` | off-screen div | — |

**Submit behavior:** AJAX POST → `admin-ajax.php` → `wp_mail()` to admin email.
**Decision:** Run both email (WordPress) AND Telegram (new backend) alongside.

### 5b. Field mapping

| Backend | Current | Action |
|---------|---------|--------|
| `name` | `malachy_name` | Pass through |
| `contact` | `malachy_email` | Pass through |
| `problem_text` | `malachy_message` | Pass through |
| `source_campaign` | — | New hidden input |
| `idempotency_key` | — | `crypto.randomUUID()` client-side |
| `website` (honeypot) | `malachy_hp` | Rename to `website` |

### 5c. Decisions confirmed

- **Email + Telegram run alongside** (confirmed by Malachy)
- **Placeholder text:** "What are you trying to solve?" (confirmed by Malachy)

### 5d. Implementation plan

**Markup (section-contact.php):**
1. Rename honeypot `name="malachy_hp"` to `name="website"`
2. Add `<input type="hidden" name="source_campaign" id="source_campaign" value="">`
3. Change textarea placeholder to "What are you trying to solve?"

**JS (contact.js):**
1. On submit: build JSON payload from existing fields + idempotency_key + source_campaign
2. POST to `https://api.imadconsulting.co.uk/api/leads` with `X-Form-Secret`
3. On success: also fire the existing WordPress AJAX submit (dual notification)
4. Show success/error to user

**PHP (contact-handler.php):**
1. Update honeypot check: `$data['malachy_hp']` to `$data['website']` (line 70)

**Section ID:** `<section id="contact">` already exists — confirmed.

### Acceptance
- Before/after diff of Contact section markup
- Browser form submission → Telegram message (screenshot/message text)
- `wp_mail()` still fires alongside Telegram

---

## 6. Task 3 — Conditional Hero CTA plan

### Selectors confirmed

| Element | Selector |
|---|---|
| "View My Projects" | `.hero-actions .btn-primary` |
| "Book a Discovery Call" | `#open-discovery` |
| Contact section | `#contact` |

### Copy (approved by Malachy)
`Tell me your problem` (replaces "View My Projects" when utm_source present)

### Implementation (add to contact.js)

```js
(function () {
  var params = new URLSearchParams(window.location.search);
  var source = params.get('utm_source');
  var campaign = params.get('utm_campaign');
  if (!source) return;

  var btn = document.querySelector('.hero-actions .btn-primary');
  if (!btn) return;

  btn.textContent = 'Tell me your problem';
  btn.onclick = function (e) {
    e.preventDefault();
    document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });
  };

  if (campaign) {
    var field = document.getElementById('source_campaign');
    if (field) field.value = campaign;
  }
})();
```

"Book a discovery call" untouched.

### Acceptance
- `?utm_source=status&utm_campaign=ep001` shows swapped button, scrolls to Contact
- No params shows original behavior
- `source_campaign = 'ep001'` in leads table (raw psql output)
- Exactly one contact form on site

---

## 7. Decisions requiring review

1. **Honeypot rename in PHP** — contact-handler.php line 70: `$data['malachy_hp']` to `$data['website']`. One-line change. Approved?

2. **Dual notification** — Backend POST first (Telegram), then WordPress AJAX (email). If backend fails, email still fires.

3. **CORS** — `IMAD_ALLOWED_ORIGIN` currently `https://imadconsulting.co.uk`. Need staging origin for testing? Or test on production only?

4. **source_campaign markup** — Hidden `<input name="source_campaign" id="source_campaign" value="">` added to existing form.

---

## 8. Files to modify

| File | Change | Task |
|------|--------|------|
| `docker-compose.yml` | Add imad-automation service | 1 |
| `.env` (VPS only) | Add IMAD_* secrets | 1 |
| `section-contact.php` | Rename honeypot, add source_campaign, change placeholder | 2 |
| `contact.js` | Backend POST + dual submit, hero CTA logic | 2+3 |
| `contact-handler.php` | Honeypot check: malachy_hp to website | 2 |

---

## 9. Execution order

1. Task 1 — Deploy backend (DB + Docker + verify)
2. Task 2 — Contact section (markup + JS + PHP)
3. Task 3 — Hero CTA (JS only)
4. Verify all acceptance criteria
5. Report with commit hashes + raw output

---

## 10. Constraints checklist

- No redesign or restyling
- "Book a discovery call" untouched
- No WhatsApp Status automation
- No AI lead scoring
- No secrets committed
- Honeypot uses position:absolute; left:-9999px
- No new Postgres — reused openagile_postgres
- No Traefik config changes — labels only
- Hero copy approved: "Tell me your problem"
- Email channel preserved alongside Telegram
- Single contact form maintained
