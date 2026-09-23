---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-044 — Update for Claude[Sonnet] Web — role definitions changed, MiMo-V2.6 workflow active
date: 2026-09-22
from: MiMo-V2.6-Pro (Architect)
to: Claude[Sonnet] Web (Reviewer)
status: URGENT — read before next review
priority: HIGH
---

## 1. What changed

The project has moved from a single-architect model to a four-role
multi-model workflow. **This is a role definition change, not a
technological change** — the code, infrastructure, and processes are
unchanged. Only who does what has shifted.

| Role | Model | Responsibility |
|---|---|---|
| **Architect** | MiMo-V2.6-Pro | Decision-making, design, planning, writing handovers |
| **Implementer** | MiMo-V2.6-Flash | Code writing, file edits, deployments, testing |
| **Reviewer** | Claude[Sonnet] Web | Reviews every handover, verifies claims, catches regressions |
| **Co-reviewer/Tester** | ChatGPT | Independent verification of critical changes |

## 2. Your role (Claude) — unchanged in substance

You remain the **Reviewer**. Your responsibilities are identical to what
you've been doing since HO-042:

- Read every handover (HO-*.md) without exception
- Verify claims against raw evidence
- Check for regressions in shared code
- Question decisions that seem convenient rather than correct
- Be explicit about what's approved versus what needs more work
- Push back where warranted

**What has NOT changed:**
- The review standard
- The handover format
- The standing process rules
- The active task (SEO Phase 0)

## 3. What's new

- **MiMo-V2.6-Flash** is now the Implementer (was part of the single-architect role)
- **ChatGPT** joins as Co-reviewer — independent verification of critical changes
- The workflow is now: Architect plans → Implementer executes → Reviewer reviews → Co-reviewer verifies

## 4. Files updated

| File | Change |
|---|---|
| `docs/handover/onboarding_handover_glm3.5_mimo2.6.md` | New onboarding brief with full four-role model |
| `docs/handover/Delegation-prompt-for-glm.md` | Updated role references |
| `docs/handover/Imad-project-context.md` | Updated role references |
| `docs/handover/Ho-042-seo-phase0.md` | Updated `to:` field |
| `AGENTS.md` | Updated role description |
| `opencode.json` | Agent definitions for all four roles |
| `docs/handover/HO-043-mimo-v2.6-role-transition.md` | Transition details |

## 5. Active task (unchanged)

SEO Phase 0 (`Ho-042-seo-phase0.md`) — server-side checks and fixes for
the stale Google index. Ready for execution under the new workflow.

## 6. Outstanding items (unchanged)

1. `f1eca1f` (v1.3.13, discovery-form nonce fix) — production deploy + honeypot re-test
2. Real `wp_mail()` delivery on production — inbox confirmation
3. Field-name parity audit — Contact vs Discovery form shared fields
4. `wordpress_pass` / `root_pass` — weak MySQL credentials
5. Test data cleanup — `leads` + `dead_letters` rows
6. SEO Phase 0 — active

## 7. What I need from you

Please acknowledge:
1. You understand the four-role model and your position in it (Reviewer)
2. The review standard is unchanged
3. You will review every handover from MiMo-V2.6-Flash without exception
4. The active task (SEO Phase 0) is ready for execution
