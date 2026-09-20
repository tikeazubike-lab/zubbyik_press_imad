"""
imad-automation — the whole n8n replacement.

Three jobs, same as before:
  1. Receive lead submissions from your existing website (no redirect, no
     separate page — your site's JS posts here in the background)
  2. Log QR/link clicks for basic attribution
  3. Run a daily check for leads that have gone quiet and need a nudge

Plus one internal helper: trigger an AI content draft for your own use.

Run with: uvicorn app.main:app --host 0.0.0.0 --port 8090
"""
import logging
from contextlib import asynccontextmanager
from datetime import datetime, timezone

from fastapi import FastAPI, Request, Header, HTTPException, Depends
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import RedirectResponse, JSONResponse
from slowapi import Limiter
from slowapi.util import get_remote_address
from slowapi.errors import RateLimitExceeded

from .config import settings
from .models import LeadIn, DraftRequest
from . import db, notify, scheduler, ai

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(name)s: %(message)s",
)
log = logging.getLogger("imad.main")

limiter = Limiter(key_func=get_remote_address)


@asynccontextmanager
async def lifespan(app: FastAPI):
    await db.init_pool()
    scheduler.start()
    log.info("imad-automation started")
    yield
    scheduler.shutdown()
    await db.close_pool()
    log.info("imad-automation stopped")


app = FastAPI(title="imad-automation", lifespan=lifespan)
app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, lambda r, e: JSONResponse(status_code=429, content={"detail": "rate limited"}))

app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.ALLOWED_ORIGINS,
    allow_methods=["POST", "GET"],
    allow_headers=["*"],
)


def verify_form_secret(x_form_secret: str = Header(...)) -> None:
    if x_form_secret != settings.FORM_SECRET:
        raise HTTPException(status_code=401, detail="unauthorized")


def verify_admin_key(x_admin_key: str = Header(...)) -> None:
    if x_admin_key != settings.ADMIN_API_KEY:
        raise HTTPException(status_code=401, detail="unauthorized")


@app.get("/healthz")
async def healthz():
    """Point uptime monitoring (or a simple cron+curl check) at this."""
    return {"status": "ok", "time": datetime.now(timezone.utc).isoformat()}


@app.post("/api/leads", dependencies=[Depends(verify_form_secret)])
@limiter.limit("5/minute")
async def submit_lead(request: Request, lead: LeadIn):
    if lead.website:  # honeypot tripped
        log.warning("honeypot triggered, silently dropping submission")
        return {"status": "ok"}  # respond success so bots don't learn to adapt

    lead_id, was_new = await db.insert_lead(
        lead.name, lead.contact, lead.problem_text, lead.source_campaign, lead.idempotency_key
    )
    if was_new:
        await notify.notify_new_lead(lead_id, lead.name, lead.contact, lead.problem_text)
    return {"status": "ok", "lead_id": lead_id}


@app.get("/r/{source}")
async def track_click(source: str, request: Request, campaign: str = "default"):
    """QR codes and Status links point here. Logs then forwards straight to
    your existing homepage or wherever you want — no intermediate page shown."""
    await db.insert_click(
        source, campaign, request.client.host if request.client else "unknown",
        request.headers.get("user-agent", ""),
    )
    return RedirectResponse(url=f"https://imadconsulting.co.uk/?utm_source={source}&utm_campaign={campaign}")


@app.post("/internal/draft", dependencies=[Depends(verify_admin_key)])
async def generate_content_draft(req: DraftRequest):
    """You call this yourself (curl, a small script, or a button on an
    internal-only page) — never exposed to the public site."""
    try:
        text = await ai.generate_draft(req.topic, req.pillar)
    except Exception as e:
        log.error("draft generation failed: %s", e)
        raise HTTPException(status_code=502, detail="draft generation failed after retries")
    return {"draft": text}


@app.get("/internal/dead-letters", dependencies=[Depends(verify_admin_key)])
async def list_dead_letters():
    """Check this occasionally (or wire a weekly reminder to yourself) to
    catch anything that failed to send and never got resolved."""
    rows = await db.get_unresolved_dead_letters()
    return [dict(r) for r in rows]
