---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: Onboarding Handover — MiMo-V2.6 multi-model workflow introduction
date: 2026-09-22
from: OpenCode (context transfer)
to: MiMo-V2.6-Pro (Architect) / MiMo-V2.6-Flash (Implementer) / Claude[Sonnet] Web (Reviewer) / ChatGPT (Co-reviewer)
status: ACTIVE — this is the onboarding brief for all new sessions
priority: HIGH
---

## 1. What this document is

This is the onboarding handover for the IMAD Consulting project's new
multi-model workflow. Every new session reads this file first. It replaces
the previous single-model setup (GLM5.3-flash) and defines a four-role
workflow.

## 2. Role definitions

| Role | Model | Responsibility |
|---|---|---|
| **Architect** | MiMo-V2.6-Pro | Decision-making, design, planning, writing handovers. Originates all design decisions. |
| **Implementer** | MiMo-V2.6-Flash | Code writing, file edits, deployments, testing. Executes the Architect's plans. |
| **Reviewer** | Claude[Sonnet] Web | Reviews every handover without exception. Verifies claims, catches regressions, questions convenient-but-wrong decisions. Pushes back where warranted. |
| **Co-reviewer / Tester** | ChatGPT | Second pair of eyes on critical changes. Independent verification of implementation claims. Catches things the primary reviewer misses. |

**How they interact:**

```
MiMo-V2.6-Pro (Architect)
  ↓ writes plan/handover
MiMo-V2.6-Flash (Implementer)
  ↓ executes plan, writes HO-*.md
Claude[Sonnet] Web (Reviewer)
  ↓ reviews every HO-*.md
ChatGPT (Co-reviewer)
  ↓ independently verifies critical claims
Final approval
```

## 3. Read these first (auto-loaded via opencode.json)

1. `docs/handover/Imad-project-context.md` — infrastructure, locked decisions, outstanding items, standing process rules
2. `docs/handover/Delegation-prompt-for-glm.md` — historical role transition (superseded by this document)

**Active task:** `docs/handover/Ho-042-seo-phase0.md` — SEO Phase 0 server-side checks and fixes.

## 4. Standing process rules (apply to every change)

1. **Investigate before deciding.** grep, curl, `git log -S`, direct file reads. Never assume based on a comment or an earlier handover.
2. **Raw command output, not narrated summaries.** "I confirmed X" is not evidence. The actual command output is.
3. **State deviations from a plan explicitly** with the reason — never silently resolve an ambiguity and mention it only if asked.
4. **Deployment to production is its own tracked step** with its own verification. Staging-tested does not mean production-live.
5. **Write a handover (HO-*.md)** for every completed unit of work, using the same format as existing HO-*.md files (type/project/title/date/from/to/status/priority frontmatter).
6. **When blocked on something only Malachy can answer** — say so plainly and wait. Don't guess and proceed.

## 5. Recurring failure pattern — watch for this

Shared code validated against only one of its callers has broken twice:
the honeypot field name (HO-026) and the nonce field name (HO-041).
Before touching any handler, template, or JS file shared by more than one
form/feature, check **every** caller.

## 6. Handover format

Every completed unit of work gets a handover with this frontmatter:

```yaml
---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-XXX — descriptive title
date: YYYY-MM-DD
from: [your role/model]
to: [reviewer/model]
status: [ACTIVE / DONE / OPEN / etc.]
priority: [HIGH / NORMAL / LOW]
---
```

Then the content: raw evidence, decisions made, deviations from plan,
remaining items.

## 7. Never

- Retry a rate-limited operation without fixing root cause first
- Revisit locked decisions without a documented reason
- Check only one caller of shared code
- Commit secrets to the repository
- Call a unit "complete" without production deployment verification

## 8. Infrastructure quick reference

- **Production:** `imadconsulting.co.uk` — InMotion shared WordPress hosting
- **Staging:** `imadconsult.zubbystudio.site` — Netcup VPS (bind-mounted theme)
- **Backend API:** `api.imadconsulting.co.uk` — FastAPI on Netcup VPS
- **Shared Postgres:** `openagile_postgres` on VPS (reused, do not create new)
- **Theme repo:** `malachy-portfolio` (WordPress theme)
- **Secrets:** `.env` on VPS only, never committed

## 9. Outstanding items (as of this handover)

1. `f1eca1f` (v1.3.13, discovery-form nonce fix) — deployed to staging, needs production deploy + honeypot re-test
2. Real `wp_mail()` delivery on production — never confirmed with received-email proof
3. Field-name parity audit — full audit of shared field names between Contact and Discovery forms
4. `wordpress_pass` / `root_pass` — weak MySQL credentials in git history, non-blocking
5. Test data cleanup — `leads` ~8 rows, `dead_letters` 4 rows
6. **SEO Phase 0** — active task, see `Ho-042-seo-phase0.md`
