"""
Telegram is the notification channel: official bot API, no policy risk
(unlike unofficial WhatsApp automation), free, instant, and works from your
phone with zero extra app. Retries with backoff before giving up — and if
it truly can't be delivered, it goes to the dead_letters table instead of
vanishing, which is the actual failure mode that matters (a real lead's
notification silently never arriving).
"""
import logging
import httpx
from tenacity import retry, stop_after_attempt, wait_exponential, RetryError
from .config import settings
from . import db

log = logging.getLogger("imad.notify")

TELEGRAM_URL = f"https://api.telegram.org/bot{settings.TELEGRAM_BOT_TOKEN}/sendMessage"


@retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=1, max=10))
async def _send(text: str) -> None:
    async with httpx.AsyncClient(timeout=10) as client:
        resp = await client.post(
            TELEGRAM_URL,
            json={"chat_id": settings.TELEGRAM_CHAT_ID, "text": text, "parse_mode": "HTML"},
        )
        resp.raise_for_status()


async def notify(text: str, kind: str = "generic", payload: dict | None = None) -> None:
    try:
        await _send(text)
    except (RetryError, httpx.HTTPError) as e:
        log.error("notification failed after retries: %s", e)
        await db.insert_dead_letter(kind, payload or {"text": text}, str(e))


async def notify_new_lead(lead_id: int, name: str, contact: str, problem_text: str) -> None:
    text = (
        f"🟢 <b>New lead #{lead_id}</b>\n"
        f"Name: {name}\n"
        f"Contact: {contact}\n\n"
        f"{problem_text}"
    )
    await notify(text, kind="new_lead", payload={"lead_id": lead_id})


async def notify_followup_due(lead_id: int, name: str, contact: str, days_stale: int) -> None:
    text = (
        f"⏰ <b>Follow-up due</b>\n"
        f"Lead #{lead_id} — {name} ({contact})\n"
        f"No contact in {days_stale}+ days. Worth a nudge?"
    )
    await notify(text, kind="followup", payload={"lead_id": lead_id})
