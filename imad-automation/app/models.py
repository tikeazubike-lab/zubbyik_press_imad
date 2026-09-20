from pydantic import BaseModel, Field


class LeadIn(BaseModel):
    name: str = Field(min_length=1, max_length=200)
    contact: str = Field(min_length=3, max_length=200)  # email or whatsapp number
    problem_text: str = Field(min_length=5, max_length=4000)
    source_campaign: str | None = Field(default=None, max_length=100)
    idempotency_key: str = Field(min_length=8, max_length=128)

    # Honeypot: a field real visitors never fill in because it's hidden via
    # CSS in the actual form. Bots that blindly fill every input trip it.
    # No length constraint here on purpose — a bot filling this in must reach
    # the handler in main.py (which checks `if lead.website:` and silently
    # returns "ok") rather than being rejected here with a 422, which would
    # reveal to the bot exactly which field is the trap.
    website: str = Field(default="", max_length=500, description="honeypot — must stay empty")


class DraftRequest(BaseModel):
    topic: str = Field(min_length=3, max_length=300)
    pillar: str = Field(default="general")
