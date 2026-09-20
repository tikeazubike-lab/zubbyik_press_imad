-- imad-automation initial schema
-- Apply with: psql "$IMAD_DATABASE_URL" -f migrations/001_init.sql

CREATE TABLE IF NOT EXISTS leads (
    id              SERIAL PRIMARY KEY,
    name            TEXT NOT NULL,
    contact         TEXT NOT NULL,
    problem_text    TEXT NOT NULL,
    source_campaign TEXT,
    status          TEXT NOT NULL DEFAULT 'new',   -- new / contacted / qualified / proposal / client / lost
    idempotency_key TEXT NOT NULL UNIQUE,
    last_touch      TIMESTAMPTZ NOT NULL DEFAULT now(),
    reminded_at     TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS clicks (
    id         SERIAL PRIMARY KEY,
    source     TEXT NOT NULL,
    campaign   TEXT NOT NULL,
    ip         TEXT,
    ua         TEXT,
    ts         TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS content_items (
    id           SERIAL PRIMARY KEY,
    topic        TEXT NOT NULL,
    pillar       TEXT,
    draft_text   TEXT,
    status       TEXT NOT NULL DEFAULT 'draft',   -- draft / approved / published
    published_at TIMESTAMPTZ,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Anything that failed to send after retries lands here instead of
-- vanishing silently. Check /internal/dead-letters occasionally.
CREATE TABLE IF NOT EXISTS dead_letters (
    id          SERIAL PRIMARY KEY,
    kind        TEXT NOT NULL,
    payload     JSONB NOT NULL,
    error       TEXT NOT NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    resolved_at TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_leads_status ON leads(status);
CREATE INDEX IF NOT EXISTS idx_clicks_campaign ON clicks(campaign);
