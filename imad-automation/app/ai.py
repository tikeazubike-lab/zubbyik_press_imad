"""
Content-draft generation — the one place AI touches your content pipeline.
Kept as a plain function, not a workflow engine step, so it's testable and
you can call it from a script too, not just the API.
"""
import httpx
from tenacity import retry, stop_after_attempt, wait_exponential
from .config import settings

PROMPT_TEMPLATE = """You are writing a WhatsApp Status script for IMAD Consulting, \
a technology consultant whose positioning is "you don't need to become a technical \
expert -- bring me the problem." Audience: non-technical UK/EU/US small business owners.

Topic: {topic}
Pillar: {pillar}

Write exactly 6 short beats, each under 25 words, following this structure:
1. HOOK -- a "Do you know that..." surprising fact, no jargon
2. WHY IT MATTERS -- one sentence connecting it to lost time/money/customers
3. EXAMPLE -- a concrete, relatable small-business scenario
4. WHAT'S ACTUALLY GOING ON -- plain-English mechanism, zero technical terms
5. WHAT TO DO -- one honest, non-salesy next step
6. CTA -- "Bring me the problem, let's figure out the technology together."

Rules: no listicle tone, no "10 tips", write like texting a smart friend who
isn't technical. Avoid absolute claims you can't back up. Return only the six
numbered beats, nothing else.
"""


@retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=2, max=15))
async def generate_draft(topic: str, pillar: str) -> str:
    if not settings.ANTHROPIC_API_KEY:
        raise RuntimeError("IMAD_ANTHROPIC_API_KEY not set")
    async with httpx.AsyncClient(timeout=30) as client:
        resp = await client.post(
            "https://api.anthropic.com/v1/messages",
            headers={
                "x-api-key": settings.ANTHROPIC_API_KEY,
                "anthropic-version": "2023-06-01",
                "content-type": "application/json",
            },
            json={
                "model": "claude-sonnet-4-6",
                "max_tokens": 500,
                "messages": [
                    {"role": "user", "content": PROMPT_TEMPLATE.format(topic=topic, pillar=pillar)}
                ],
            },
        )
        resp.raise_for_status()
        data = resp.json()
        return "".join(block.get("text", "") for block in data.get("content", []))
