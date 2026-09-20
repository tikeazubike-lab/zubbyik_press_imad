"""
Central config. Everything comes from environment variables so the same
image runs in dev/staging/prod without code changes. Fail loudly at
startup if something required is missing — silent misconfiguration is
worse than a crash on boot.
"""
import os
import sys


def _require(name: str) -> str:
    val = os.getenv(name)
    if not val:
        print(f"FATAL: missing required env var {name}", file=sys.stderr)
        sys.exit(1)
    return val


class Settings:
    # Postgres — reuse your existing instance, dedicated database for this app
    DATABASE_URL: str = _require("IMAD_DATABASE_URL")  # postgresql://user:pass@host:5432/imad

    # Telegram is the notification channel (free, instant, has a real API —
    # unlike WhatsApp Status/automation, Telegram bots are fully official and stable)
    TELEGRAM_BOT_TOKEN: str = _require("IMAD_TELEGRAM_BOT_TOKEN")
    TELEGRAM_CHAT_ID: str = _require("IMAD_TELEGRAM_CHAT_ID")

    # Anthropic API key for content-draft assistance (Workflow 2 from the plan)
    ANTHROPIC_API_KEY: str = os.getenv("IMAD_ANTHROPIC_API_KEY", "")

    # Shared secret your website's form JS sends in a header, so random bots
    # hitting the endpoint directly (not through your site) are rejected.
    # This is NOT strong auth — it's a cheap filter. Real spam defense is the
    # honeypot field + rate limiter below.
    FORM_SECRET: str = _require("IMAD_FORM_SECRET")

    # Admin key for the content-draft / internal endpoints — never exposed to
    # the public website, only used by you (e.g. curl / a small script)
    ADMIN_API_KEY: str = _require("IMAD_ADMIN_API_KEY")

    # Restrict CORS to your actual domain(s). Comma-separated for multiple
    # origins (e.g. production + a staging domain for testing before a
    # change goes live) — browsers enforce CORS per-origin, so testing the
    # form on staging will silently fail if staging isn't in this list.
    ALLOWED_ORIGINS: list[str] = [
        o.strip() for o in os.getenv(
            "IMAD_ALLOWED_ORIGINS", "https://imadconsulting.co.uk"
        ).split(",") if o.strip()
    ]

    # How many days of no contact before a "follow up" reminder fires
    FOLLOWUP_STALE_DAYS: int = int(os.getenv("IMAD_FOLLOWUP_STALE_DAYS", "3"))

    # Time (UTC, 24h) the daily follow-up check runs
    FOLLOWUP_CHECK_HOUR_UTC: int = int(os.getenv("IMAD_FOLLOWUP_HOUR_UTC", "8"))


settings = Settings()
