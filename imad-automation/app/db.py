"""
Thin data layer over asyncpg. No ORM — at three tables and a handful of
queries, an ORM buys nothing but an extra dependency to understand when
something goes wrong at 11pm.
"""
import asyncpg
import logging
from datetime import datetime, timezone
from .config import settings

log = logging.getLogger("imad.db")

_pool: asyncpg.Pool | None = None


async def init_pool() -> None:
    global _pool
    _pool = await asyncpg.create_pool(
        dsn=settings.DATABASE_URL,
        min_size=1,
        max_size=5,
        command_timeout=10,
    )
    log.info("db pool initialized")


async def close_pool() -> None:
    if _pool:
        await _pool.close()


def pool() -> asyncpg.Pool:
    assert _pool is not None, "call init_pool() first"
    return _pool


# ---------- leads ----------

async def insert_lead(name: str, contact: str, problem_text: str,
                       source_campaign: str | None, idempotency_key: str) -> tuple[int, bool]:
    """
    Returns (lead_id, was_new). Idempotency key (e.g. a hash of contact+problem_text
    sent from a client-side nonce) prevents duplicate rows if the browser
    double-fires the form submit — a real, common failure mode on flaky mobile
    connections, not a hypothetical one.
    """
    async with pool().acquire() as conn:
        existing = await conn.fetchrow(
            "SELECT id FROM leads WHERE idempotency_key = $1", idempotency_key
        )
        if existing:
            return existing["id"], False
        row = await conn.fetchrow(
            """
            INSERT INTO leads (name, contact, problem_text, source_campaign, idempotency_key)
            VALUES ($1, $2, $3, $4, $5)
            RETURNING id
            """,
            name, contact, problem_text, source_campaign, idempotency_key,
        )
        return row["id"], True


async def get_stale_leads(stale_days: int) -> list[asyncpg.Record]:
    async with pool().acquire() as conn:
        return await conn.fetch(
            """
            SELECT id, name, contact, status, last_touch
            FROM leads
            WHERE status = 'contacted'
              AND last_touch < now() - ($1 || ' days')::interval
              AND reminded_at IS NULL
            """,
            str(stale_days),
        )


async def mark_reminded(lead_id: int) -> None:
    async with pool().acquire() as conn:
        await conn.execute(
            "UPDATE leads SET reminded_at = now() WHERE id = $1", lead_id
        )


# ---------- click tracking ----------

async def insert_click(source: str, campaign: str, ip: str, ua: str) -> None:
    async with pool().acquire() as conn:
        await conn.execute(
            "INSERT INTO clicks (source, campaign, ip, ua) VALUES ($1, $2, $3, $4)",
            source, campaign, ip, ua,
        )


# ---------- dead letters (failed outbound sends — notifications, etc.) ----------

async def insert_dead_letter(kind: str, payload: dict, error: str) -> None:
    """
    If a Telegram notification fails after retries, we do NOT just log and
    forget — that's how a real lead silently never gets seen. We record it
    here so a daily health-check job (or you, manually) can recover it.
    """
    import json
    async with pool().acquire() as conn:
        await conn.execute(
            "INSERT INTO dead_letters (kind, payload, error) VALUES ($1, $2::jsonb, $3)",
            kind, json.dumps(payload), error,
        )


async def get_unresolved_dead_letters() -> list[asyncpg.Record]:
    async with pool().acquire() as conn:
        return await conn.fetch(
            "SELECT id, kind, payload, error, created_at FROM dead_letters WHERE resolved_at IS NULL ORDER BY created_at"
        )
