# imad-automation

A ~350-line replacement for the three things you were going to use n8n for:
lead intake, click logging, and a daily stale-lead reminder. No workflow
engine, no visual editor, no separate database for the tool's own state —
just a small FastAPI service, your existing Postgres, and Telegram for
notifications.

## What this deliberately does NOT do

- **Does not publish to WhatsApp Status.** No officially-supported API for
  this exists (Meta's Cloud API has no Status endpoint), and the unofficial
  libraries that fake it work by impersonating WhatsApp Web and can get your
  business number banned. Publish Status manually — it's a two-minute task.
- **Does not classify or score leads with AI.** At your current volume
  you'll read every submission yourself within a minute of the Telegram
  ping. Add this later only if volume genuinely makes manual triage the
  bottleneck.
- **Does not send automated follow-up messages.** It reminds *you* to
  follow up like a human would. Automating the actual outreach is exactly
  the "robotic spam machine" you said you didn't want.

## Setup

1. **Create the database and apply the schema:**
   ```bash
   createdb imad   # on your existing shared Postgres instance
   psql "$IMAD_DATABASE_URL" -f migrations/001_init.sql
   ```

2. **Create a Telegram bot** (2 minutes, official, free):
   - Message [@BotFather](https://t.me/BotFather) on Telegram, `/newbot`, follow prompts
   - Message your new bot once, then visit
     `https://api.telegram.org/bot<TOKEN>/getUpdates` to find your `chat_id`

3. **Set environment variables** (put these in a `.env` file next to your
   compose file, or your VPS's secrets manager — never commit them):
   ```
   IMAD_DATABASE_URL=postgresql://imad_user:...@postgres:5432/imad
   IMAD_TELEGRAM_BOT_TOKEN=...
   IMAD_TELEGRAM_CHAT_ID=...
   IMAD_ANTHROPIC_API_KEY=...        # optional, only needed for /internal/draft
   IMAD_FORM_SECRET=<generate: openssl rand -hex 32>
   IMAD_ADMIN_API_KEY=<generate: openssl rand -hex 32>
   IMAD_ALLOWED_ORIGINS=https://imadconsulting.co.uk   # comma-separated if testing on staging too
   ```

4. **Add the service to your existing `docker-compose.yml`** — see
   `docker-compose.snippet.yml`, adjust the network name to match your
   actual Traefik setup, then:
   ```bash
   docker compose up -d --build imad-automation
   curl https://api.imadconsulting.co.uk/healthz
   ```

5. **Embed the form** — copy the markup/JS from
   `website-embed/brainstorm-form.html` into an existing section of your
   current site, restyle it with your site's own CSS classes, and paste
   your real `IMAD_FORM_SECRET` value into the JS. This is a bot filter,
   not real auth — it's visible in your page source, and that's fine, its
   only job is to reject direct hits from random scanners.

6. **Point your QR code / Status links at**
   `https://api.imadconsulting.co.uk/r/status?campaign=ep001` — it logs the
   click and forwards straight to your homepage with UTM params attached,
   no intermediate page.

## Running without Docker (systemd alternative)

If you'd rather run it as a plain systemd service instead of another
container:

```ini
# /etc/systemd/system/imad-automation.service
[Unit]
Description=imad-automation
After=network.target postgresql.service

[Service]
User=imad
WorkingDirectory=/opt/imad-automation
EnvironmentFile=/opt/imad-automation/.env
ExecStart=/opt/imad-automation/venv/bin/uvicorn app.main:app --host 0.0.0.0 --port 8090
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now imad-automation
sudo journalctl -u imad-automation -f   # logs
```

`Restart=always` is the systemd equivalent of Docker's
`restart: unless-stopped` — pick whichever matches how the rest of your
stack is already run, don't mix both for the same service.

## Operational notes

- **Health check:** `GET /healthz` — wire this into whatever you already
  use for uptime monitoring (even a simple cron `curl` + Telegram alert on
  non-200 is enough at this scale).
- **Failed notifications:** `GET /internal/dead-letters` (requires
  `X-Admin-Key` header) lists anything that failed to send after 3 retries.
  Check it weekly until you have a reason to automate that check too.
- **Rate limiting:** the `/api/leads` endpoint is capped at 5 requests/min
  per IP via `slowapi`. Adjust in `main.py` if legitimate traffic ever
  exceeds that (unlikely at your current scale).
- **Idempotency:** the form JS generates a UUID per page load and sends it
  as `idempotency_key`. A double-submit (flaky mobile network, accidental
  double-click) inserts one row, not two.

## Testing it end to end

```bash
# Health check
curl https://api.imadconsulting.co.uk/healthz

# Simulate a lead submission
curl -X POST https://api.imadconsulting.co.uk/api/leads \
  -H "Content-Type: application/json" \
  -H "X-Form-Secret: $IMAD_FORM_SECRET" \
  -d '{"name":"Test","contact":"test@example.com","problem_text":"Testing the pipeline","idempotency_key":"test-001"}'

# Should trigger a Telegram message to you within a second or two.

# Trigger a content draft (admin only)
curl -X POST https://api.imadconsulting.co.uk/internal/draft \
  -H "Content-Type: application/json" \
  -H "X-Admin-Key: $IMAD_ADMIN_API_KEY" \
  -d '{"topic":"why slow websites lose customers","pillar":"websites"}'
```
