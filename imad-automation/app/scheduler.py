"""
This replaces n8n's cron-trigger node. APScheduler runs in-process — no
separate cron daemon, no separate container, one less thing that can fall
out of sync with the app. If the process restarts, APScheduler's job simply
re-registers on startup; nothing is lost because the job is idempotent
(re-checks the DB each run rather than tracking its own state).
"""
import logging
from apscheduler.schedulers.asyncio import AsyncIOScheduler
from apscheduler.triggers.cron import CronTrigger
from .config import settings
from . import db, notify

log = logging.getLogger("imad.scheduler")

scheduler = AsyncIOScheduler()


async def check_stale_leads() -> None:
    stale = await db.get_stale_leads(settings.FOLLOWUP_STALE_DAYS)
    log.info("follow-up check: %d stale lead(s)", len(stale))
    for row in stale:
        await notify.notify_followup_due(
            row["id"], row["name"], row["contact"], settings.FOLLOWUP_STALE_DAYS
        )
        await db.mark_reminded(row["id"])


def start() -> None:
    scheduler.add_job(
        check_stale_leads,
        trigger=CronTrigger(hour=settings.FOLLOWUP_CHECK_HOUR_UTC, minute=0),
        id="stale_lead_check",
        replace_existing=True,
        misfire_grace_time=3600,  # if the process was down at trigger time, still run within an hour of restart
    )
    scheduler.start()
    log.info("scheduler started — daily follow-up check at %02d:00 UTC", settings.FOLLOWUP_CHECK_HOUR_UTC)


def shutdown() -> None:
    scheduler.shutdown(wait=False)
