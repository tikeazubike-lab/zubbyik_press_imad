# IMAD Lead Capture — Workflow Manual

A short, practical guide: what triggers what, how a lead flows through the
system, what you have to do, and where the results land.

---

## 1. The big picture

```
                    ┌─────────────────────────────┐
   You publish a    │  QR code / Status link      │
   campaign link ──▶│  api.imadconsulting.co.uk   │
                    │      /r/status?campaign=X   │
                    └──────────────┬──────────────┘
                                   │ logs the click, then
                                   ▼
                    ┌─────────────────────────────┐
                    │  Homepage + UTM params      │
                    │  ?utm_source=status&        │
                    │   utm_campaign=X            │
                    └──────────────┬──────────────┘
                                   │ page detects utm_source
                                   ▼
                    ┌─────────────────────────────┐
                    │ Hero button swaps to        │
                    │ "Tell me your problem"      │
                    │ → scrolls to Contact form   │
                    └──────────────┬──────────────┘
                                   │ visitor submits the form
                                   ▼
              ┌────────────────────┴────────────────────┐
              ▼                                         ▼
   ┌─────────────────────┐                 ┌─────────────────────┐
   │  Telegram           │                 │  Email              │
   │  @imadlead_bot      │                 │  wp_mail() to you   │
   │  (instant)          │                 │  (backup channel)   │
   └─────────────────────┘                 └─────────────────────┘
              │                                         │
              └────────────────────┬────────────────────┘
                                   ▼
                    ┌─────────────────────────────┐
                    │  Lead stored in Postgres    │
                    │  with campaign attribution  │
                    └─────────────────────────────┘
```

Both notification channels fire **independently** — if Telegram is down you
still get the email, and vice versa. The lead is only lost if both fail.

---

## 2. Flow A — Campaign / QR link (attributed leads)

**Trigger:** someone opens a link you published.

**Your input:** publish a link of the form

```
https://api.imadconsulting.co.uk/r/status?campaign=ep001
```

- `status` = the source (a label you choose — e.g. `status`, `qr`, `linkedin`)
- `campaign` = the episode/QR identifier (e.g. `ep001`), stored on the lead

**What happens automatically:**

1. The link logs a click (source, campaign, IP, user-agent) and instantly
   forwards the visitor to the homepage with `?utm_source=status&utm_campaign=ep001`
   — no intermediate page.
2. The homepage notices `utm_source` and changes the hero's first button from
   **"View My Projects"** to **"Tell me your problem"**.
3. That button scrolls to the Contact section instead of the projects section.
4. The `utm_campaign` value is written into a hidden `source_campaign` field on
   the form.
5. When the visitor submits, both Telegram and email fire, and the lead is
   stored with `source_campaign = ep001`.

**Output:** a Telegram message on `@imadlead_bot`, an email, and a row in the
`leads` table showing which campaign produced it.

---

## 3. Flow B — Direct visitor (organic)

**Trigger:** someone visits the site normally (search, LinkedIn, typed URL).

**Your input:** none.

**What happens:** nothing changes. Both hero buttons behave exactly as before
("View My Projects" → projects). If they use the Contact form, it still fires
Telegram + email and stores the lead — just with an empty `source_campaign`.

---

## 4. Daily follow-up reminder

**Trigger:** a scheduled job inside the backend runs once a day at **08:00 UTC**.

**What it checks:** leads whose **status is `contacted`** and that haven't been
touched in **3+ days** (both values configurable — see §7).

**Your input:** after you reach out to a lead, mark it as contacted so the job
knows to watch it:

```sql
UPDATE leads SET status = 'contacted', last_touch = now() WHERE id = <id>;
```

**Output:** a Telegram message on `@imadlead_bot`:

```
⏰ Follow-up due
Lead #12 — Jane Smith (jane@company.com)
No contact in 3+ days. Worth a nudge?
```

Each lead is reminded once (`reminded_at` is stamped), so you won't be nagged
repeatedly.

---

## 5. What you do vs. what is automatic

| Step | Who |
|---|---|
| Create the campaign link / QR code | **You** |
| Publish it on Status / LinkedIn / print | **You** |
| Click logging + redirect | Automatic |
| Hero button swap + scroll target | Automatic |
| Campaign attribution onto the form | Automatic |
| Store lead + Telegram + email | Automatic |
| Reply to the lead | **You** |
| Mark the lead `contacted` (so reminders work) | **You** |
| Daily stale-lead reminder | Automatic |
| Read the notification and act | **You** |

---

## 6. Where your results land

| Output | Where |
|---|---|
| New lead ping | Telegram — **`@imadlead_bot`** (dedicated leads bot) |
| Backup notification | Email to the WordPress admin address |
| Raw lead record + campaign | Postgres `imad` database, `leads` table |
| Click log | Postgres `clicks` table |
| Failed notifications | `dead_letters` table (nothing vanishes silently) |

Useful queries:

```bash
# all leads, newest first
docker exec -i openagile_postgres psql -U imad_user -d imad \
  -c "SELECT id, name, contact, source_campaign, status, created_at FROM leads ORDER BY id DESC;"

# which campaigns are producing leads
docker exec -i openagile_postgres psql -U imad_user -d imad \
  -c "SELECT source_campaign, count(*) FROM leads GROUP BY source_campaign;"

# click counts per campaign
docker exec -i openagile_postgres psql -U imad_user -d imad \
  -c "SELECT campaign, count(*) FROM clicks GROUP BY campaign;"

# anything that failed to notify
docker exec -i openagile_postgres psql -U imad_user -d imad \
  -c "SELECT id, kind, error, created_at FROM dead_letters WHERE resolved_at IS NULL;"
```

---

## 7. The knobs (configuration)

| Setting | Where | Default |
|---|---|---|
| Follow-up threshold (days) | `IMAD_FOLLOWUP_STALE_DAYS` | `3` |
| Daily check hour (UTC) | `IMAD_FOLLOWUP_HOUR_UTC` | `8` |
| Allowed web origins | `IMAD_ALLOWED_ORIGINS` | production + staging |
| Telegram bot | `IMAD_TELEGRAM_BOT_TOKEN` / `..._CHAT_ID` | `@imadlead_bot` |
| Form bot-filter secret | `IMAD_FORM_SECRET` (mirrored in `contact.js`) | — |

Secrets live in `.env` on the VPS (gitignored), never in the repo.

---

## 8. Health & troubleshooting (one-liners)

```bash
# is the backend alive?
curl -s https://api.imadconsulting.co.uk/healthz

# is the container healthy?
docker ps --filter name=imad-automation

# recent backend activity
docker logs imad-automation --tail 30

# simulate a lead (proves the whole backend path end to end)
docker exec imad-automation python -c "
import urllib.request, json, time
d = json.dumps({'name':'Manual Test','contact':'test@example.com',
                'problem_text':'health check','website':'','source_campaign':'manual',
                'idempotency_key':'manual-'+str(time.time())}).encode()
r = urllib.request.Request('http://localhost:8090/api/leads', data=d,
      headers={'Content-Type':'application/json','X-Form-Secret':'<IMAD_FORM_SECRET>'},
      method='POST')
print(urllib.request.urlopen(r).read().decode())
"
```

**If a lead never arrived:** check `dead_letters` first — that's the system's
own record of notifications that failed after retries, rather than a guess.
