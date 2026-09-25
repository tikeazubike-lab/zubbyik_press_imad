# IMAD-PROJECT-CONTEXT.md — Single Source of Truth

**Purpose**: bootstrap document for whoever is acting as Architect on this
project. Read this before making any new decision — it's the condensed
state of everything decided in HO-022 through HO-041 so you don't have to
re-derive it from 20 handovers.

**Last updated**: 2026-09-22, at the handoff from Claude-as-Architect to
GLM5.3-flash-as-Architect (see HO-042).

---

## What this project is

Lead capture + notification system for IMAD Consulting
(`imadconsulting.co.uk`), a solo IT/QA/website consulting business run by
Malachy. Goal: turn a WhatsApp Status content campaign into tracked leads,
without n8n, without paid SaaS, self-hosted where possible.

## Infrastructure (locked facts)

```
Production site:  imadconsulting.co.uk — InMotion shared WordPress hosting
                   (PHP/MySQL only — no Docker, no Postgres there)
Staging site:      imadconsult.zubbystudio.site — WordPress on the Netcup VPS
                   (bind-mounted theme files, always reflects latest code)
Backend API:       api.imadconsulting.co.uk — FastAPI service on the same
                   Netcup VPS, Dockerized, DNS-only (not Cloudflare-proxied)
Shared Postgres:   openagile_postgres container on the VPS (reused, do not
                   create a new instance — same instance also serves the
                   separate EPM v2 project)
Reverse proxy:     Traefik, two certresolvers now exist:
                     - `cloudflare` (DNS-01, existing, used by all
                       zubbystudio.site services — do not touch)
                     - `letsencrypt-http` (HTTP-01, added specifically for
                       api.imadconsulting.co.uk because the Cloudflare
                       token can't see that zone — see Locked Decisions)
Theme repo:        malachy-portfolio (WordPress theme), version currently
                   1.3.13 pending deploy — check MALACHY_THEME_VERSION
                   before assuming what's live
```

## Team structure (current, as of HO-045)

**Four-role model**, deliberately chosen by Malachy based on model
capability and budget available in OpenCode — confirmed directly, not
just asserted by an agent:

| Role | Model | Authority |
|---|---|---|
| Architect | MiMo-V2.6-Pro | Decision-making, design, planning, writes handovers |
| Implementer | MiMo-V2.6-Flash | Code, file edits, deployments, testing |
| Reviewer | Claude | Reviews every handover without exception, verifies claims against raw evidence |
| Co-reviewer | ChatGPT | Recommends only — no edit authority, no direct credential/server access; input routes through the Reviewer, treated as one more claim to verify, not accepted on its own authority |

Any future change to this structure (model swap, new participant, changed
authority) should come from Malachy directly, not from an agent's own
account of a restructuring — this was the standard applied when MiMo
replaced GLM as Architect and should hold for any future change too.



| Decision | Why |
|---|---|
| No n8n | Malachy removed it; replaced with a ~350-line FastAPI service (`imad-automation`) — simpler surface area at this project's scale |
| No WhatsApp Status automation, official or unofficial | No officially-supported API exists for Status publishing; unofficial libraries risk a business-number ban. Status stays manual, permanently — not a temporary limitation |
| Dual-channel lead notification (Telegram + email) | Fires both independently via `Promise.allSettled`, never chained — a chained design was caught and rejected in HO-023 because it silently drops leads if the first channel fails |
| `api.imadconsulting.co.uk` (not a zubbystudio.site subdomain) | Reversed once already (HO-028 → HO-029) — this is final. Ties the API to the actual production domain, not personal VPS infrastructure with its own migration history |
| Dedicated Telegram bot (`@imadlead_bot`) for leads only | Separate from the pre-existing WordPress chatbot's bot (`@imadconsultingBot`), so a new lead can't get lost in unrelated chat traffic |
| Contact section reuse, not a new form | One lead-capture form on the site — the existing Contact section was extended, not duplicated. The Discovery Call panel is a separate, pre-existing feature and was never meant to be touched by this work |
| HTTP-01 for `api.imadconsulting.co.uk`'s cert, DNS-01 stays default elsewhere | Traefik/lego credentials are process-wide, not per-resolver — two Cloudflare accounts can't both use DNS-01 in one Traefik instance. HTTP-01 sidesteps this for the one host that needs it |

## Recurring failure pattern — watch for this specifically

**Shared code validated against only one of its callers has broken twice
in this project already**: the honeypot field name (HO-026, only checked
for the Contact form, silently disabled the Discovery form's protection)
and the nonce field name (HO-041, same root cause, deeper — the Discovery
form has never worked at all since it was created). Before touching any
handler, template, or JS file shared by more than one form/feature, check
*every* caller, not just the one being changed.

## Outstanding items as of this handoff

1. **`f1eca1f` (v1.3.13, discovery-form nonce fix)** — written, tested on
   staging, **not yet deployed to production**. This is the current
   blocker on closing HO-041.
2. **Real inbox delivery of `wp_mail()` on production** — never actually
   confirmed with a screenshot/received email, only inferred from a
   handler returning a success response. Shared-hosting `mail()`
   deliverability is a known risk (weak SPF/DKIM, easily spam-filtered).
3. **Field-name parity audit** — given two separate field-mismatch bugs
   found so far, a full audit of every shared field name between the
   Contact form and Discovery form hasn't been done, only the two
   specific bugs that happened to surface.
4. **`wordpress_pass` / `root_pass`** — live MySQL credentials, weak, in
   git history, internal-network-only (no external port exposure). Agreed
   non-blocking but was never given an actual date.
5. **Test data cleanup** — `leads` table has ~8 test rows mixed with (by
   now, possibly) real submissions; `dead_letters` has 4 test rows.
6. **SEO Phase 0** — see the separate SEO handover; Google's index is
   currently showing stale, unrelated content (dated ~Dec 2023) for the
   domain, likely from before the current theme existed.

## Where to find things

- Backend code: `~/wordpress_project/imad-automation/`
- WordPress theme: `~/wordpress_project/malachy-portfolio/`
- Handover history: `~/wordpress_project/docs/handover/HO-*.md` (read
  recent ones if something here is unclear — this document is a summary,
  not a replacement for the full record on anything genuinely ambiguous)
- Secrets: `.env` on the VPS only, never committed (confirmed clean via
  full git-history scan in HO-036)

## Standing process rules (apply to every future change)

1. **"Deployed to production" is its own tracked step** with its own
   verification — never assume staging-tested means production-live.
2. **Raw command output, not narrated summaries**, for any claim about
   file/repo/deployment state. This has been the standard since HO-023 and
   has caught real problems every time it was actually followed and
   skipped consequences every time it wasn't.
3. **State deviations from a plan explicitly**, with the reason — don't
   silently resolve an ambiguity and mention it only if asked.
4. **Never retry a rate-limited operation (Let's Encrypt, etc.) without
   first fixing the root cause** — every premature retry in this project's
   history has made the underlying problem worse, not better.
