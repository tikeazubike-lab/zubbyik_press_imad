---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-043 — Role transition to MiMo-V2.6 multi-model workflow
date: 2026-09-22
from: OpenCode (context transfer)
to: Claude[Sonnet] Web (Reviewer)
status: ACTIVE — role definitions changed, onboarding updated
priority: HIGH (urgent — this supersedes all prior role assignments)
---

## 1. What changed

The project has transitioned from a single-architect model (GLM5.3-flash)
to a four-role multi-model workflow:

| Role | Model | Responsibility |
|---|---|---|
| **Architect** | MiMo-V2.6-Pro | Decision-making, design, planning, writing handovers |
| **Implementer** | MiMo-V2.6-Flash | Code writing, file edits, deployments, testing |
| **Reviewer** | Claude[Sonnet] Web | Reviews every handover, verifies claims, catches regressions |
| **Co-reviewer/Tester** | ChatGPT | Independent verification of critical changes |

## 2. What this means for you (Claude)

You are now the **Reviewer** role. Your job is:
- Read every handover (HO-*.md) without exception
- Verify claims against raw evidence
- Check for regressions in shared code
- Question decisions that seem convenient rather than correct
- Be explicit about what's approved versus what needs more work
- Push back where warranted — do not soften the review standard

The previous standard (Claude as Reviewer after the Claude-to-GLM handoff)
is unchanged in rigor. Only the model names in the role table have changed.

## 3. Files updated

| File | Change |
|---|---|
| `docs/handover/onboarding_handover_glm3.5_mimo2.6.md` | New onboarding handover with full role definitions |
| `docs/handover/Delegation-prompt-for-glm.md` | GLM5.3-flash → MiMo-V2.6-Pro |
| `docs/handover/Imad-project-context.md` | GLM5.3-flash → MiMo-V2.6-Pro / MiMo-V2.6-Flash |
| `docs/handover/Ho-042-seo-phase0.md` | `to:` field updated to MiMo-V2.6-Pro/Flash |
| `AGENTS.md` | Role description updated to four-role model |
| `opencode.json` | Agent definitions for all four roles |

## 4. Active task (unchanged)

SEO Phase 0 (`Ho-042-seo-phase0.md`) — server-side checks and fixes for
the stale Google index. This is the current task for the new workflow.

## 5. Outstanding items (unchanged)

1. `f1eca1f` (v1.3.13, discovery-form nonce fix) — production deploy + honeypot re-test
2. Real `wp_mail()` delivery on production — inbox confirmation
3. Field-name parity audit — Contact vs Discovery form shared fields
4. `wordpress_pass` / `root_pass` — weak MySQL credentials
5. Test data cleanup — `leads` + `dead_letters` rows
6. SEO Phase 0 — active

## 6. What I need from Claude

Please acknowledge this role change and confirm you understand:
1. You review every handover without exception
2. The four-role model supersedes all prior role assignments
3. The active task (SEO Phase 0) is ready for the new workflow to execute
