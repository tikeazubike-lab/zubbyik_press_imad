# .agent/orchestration-design.md — how Hermes will orchestrate this project

**Status**: PROPOSAL — for review before any of it is operationalised.
**Produced by**: Hermes (orchestrator), 2026-09-25, from `.agent/investigation.md`.
**Design constraint taken from the commissioning brief**: keep it minimal; integrate with what
exists rather than duplicating it; do not build a framework because a framework is possible.

---

## 1. The topology that already exists (preserved, not replaced)

```
HUMAN (Malachy)
   │  go-ahead / decisions / dashboard access
   ▼
HERD  ────────────────  local operator interface          [exists — untouched]
   ▼
HERMES (this orchestrator, local)                         [role defined here]
   │  discovery · planning · decomposition · dependency state
   │  worker selection · supervision · validation · gates · recovery
   ▼
OPENCODE on the VPS  ──  coding workers                   [exists — untouched]
   ▼
PROJECT REPOSITORY  /home/zubbyik/wordpress_project
```

Nothing in this design creates a second orchestration layer, replaces HERD, replaces OpenCode,
or introduces another framework. No Paperclip / Pi / Goose / n8n.

---

## 2. Division of responsibility

| Hermes does | OpenCode does |
|---|---|
| Investigate state (git, live probes, file reads) and record it | Write and edit code in the repo |
| Decide *what* to do next, and in what order | Execute one task contract at a time |
| Decompose work into tasks with dependencies and acceptance criteria | Run the task's tests locally |
| Select the worker and model class per task | Deploy when a task explicitly says so |
| Verify worker claims against raw output | Report results in the result contract (§7) |
| Own the record (`docs/handover/`), the gates and recovery | — |
| Escalate to Malachy instead of guessing | Report blockers instead of expanding scope |

**Hermes must not re-implement OpenCode's job inline.** If a change needs code editing, it goes
out as a task contract, even when Hermes could do it directly in this session.

---

## 3. Task lifecycle

```
PROPOSED ──▶ READY (deps satisfied) ──▶ ASSIGNED ──▶ RUNNING
                                          │              │
                                          │              ├─▶ VERIFYING (raw output checked)
                                          │              │        │
                                          │              │        ├─▶ DONE ──▶ REVIEWED
                                          │              │        └─▶ FAILED (bounded retry)
                                          └──────────────┴─▶ BLOCKED (escalate, don't loop)
```

Entry/exit rules:
- A task is **PROPOSED** with all fields from `.agent/implementation-plan.md`; it becomes
  **READY** only when every dependency has *verified* completion — not a worker's "done".
- **DONE** requires: acceptance criteria met, raw evidence captured, handover written.
- **REVIEWED** requires the project's own gate: Claude Web on every handover, ChatGPT as
  co-reviewer on critical changes. Hermes applies transitions; a reviewing subagent cannot
  close work itself.
- **BLOCKED** is a first-class outcome, not a failure. Anything needing Malachy (go-ahead,
  dashboard, product decision) is BLOCKED and waits — the project's rule, and the reason
  §11 of the plan lists seven such items instead of inventing work.

## 4. Dependency evaluation

Dependency-driven, not heartbeat-driven. State changes (a task reaching DONE, a human
unblocking B-01) trigger re-evaluation of what is now READY. Concretely for this project:

```
B-01 (Malachy: go-ahead)
   └── T-07 hygiene ──▶ T-01 release commit ──▶ T-02 live SEO verify ──▶ T-06 release procedure
                              │                                              └── T-08 test net
                              ├── T-03 form parity (independent of T-01 ordering, same surface)
                              ├── T-04 secrets + rotation
                              └── T-05 follow-up job (needs B-07 decision)
                                     └── T-09 reviewer pass over all of the above
```

No parallel workstream is proposed: with one `main`, no worktrees in use, and state divergence
as the top risk, parallelism would manufacture the confusion this project is suffering from.

## 5. State persistence

**Authoritative state lives in the repository and in Hermes's own durable stores — never in
model conversation history.** Specifically:

| Concern | Where it lives (already exists) |
|---|---|
| **The record of a completed unit of work** | **`docs/handover/HO-NNN-<slug>.md`** — the project's format (frontmatter `type/project/title/date/from/to/status/priority`), and the only artifact a reviewer actually receives. Every unit of work in this plan produces exactly one. |
| Decisions, evidence, deviations, open items | inside that handover — raw output, not narrated summary |
| Supporting detail too long for a handover | `.agent/*.md` (this trio) — referenced *from* the handover, never a substitute for it. HO-054 is the discovery run's handover. |
| Project-level facts | `docs/handover/Imad-project-context.md` — **needs fixing first**: it is stale (differs from reality on the theme version), untracked, and has a case-variant twin (C1/C10). Do not extend it until it is repaired. |
| Task graph / status | `.agent/implementation-plan.md` until the reviewer approves anything more |
| Plans (Hermes-native) | `.hermes/plans/` (already in use, 4 artifacts) |
| Agent + model routing | `opencode.json` (already in use) |
| Credentials | VPS `.env` only; never in `.agent/`, never in a handover, never in a prompt |

**Recovery after interruption**: re-read the newest `HO-*.md` (for discovery, HO-054), then
`.agent/investigation.md` + `.agent/implementation-plan.md`, then `git status` and the live
version probe (§9) to discover what actually happened. No hidden state, no daemon, no polling loop.

## 6. Worktree strategy

**None for now — deliberately.** The repo has one branch, one worktree, no PR flow, and 213
untracked entries. Introducing worktrees before the tree is clean would multiply the ambiguity.
Revisit only if (a) T-07 has cleaned the tree, (b) two tasks are provably independent, and
(c) the deploy procedure is canonical (T-06).

---

## 7. Worker task contract and result contract

Every task sent to OpenCode carries: objective · context · relevant files · constraints ·
acceptance criteria · tests required · expected artifacts · **prohibited scope** · dependencies
· expected result format. A worker that discovers out-of-scope prerequisite work reports it —
Hermes decides, then either issues a new task or adjusts scope. Silent scope expansion is
treated as a failure regardless of whether the code works.

Every worker returns: `task_id · status · summary · files_changed · tests_run · test_results ·
artifacts · blockers · follow_up_tasks · notes`, with `status ∈ {SUCCESS, PARTIAL_SUCCESS,
FAILURE, BLOCKED, NEEDS_REVIEW}`. **A natural-language "done" is never accepted as proof**;
Hermes re-checks the specific claim (file contents, the command's raw output, the live URL).

## 8. Gates

```
PLAN → IMPLEMENT → TEST → REVIEW → INTEGRATE
```
- **TEST gate**: the task's named command must have been run, with raw output preserved. Today
  the honest answer is that the theme has *no* real test — so for theme tasks the gate is
  `php -l` + live/staging verification until T-08 closes that gap. Never claim a test that was
  not run; the current repo makes it easy to fake this (`npm run test` "passes" nothing).
- **REVIEW gate**: Claude Web reviews every handover (no sampling) — handovers go to it as
  copy-paste text, so they must be self-contained and free of GitHub-only references.
- **Review delivery path (terminus, and it is not optional)**: work ends at Claude, not at
  "handover written". The chain is
  `worker → Hermes verifies raw output → HO-NNN.md written in docs/handover/ → pasted to Claude Web → Claude's findings applied → HO-NNN marked reviewed`.
  Because **Claude has no GitHub access**, the handover must be pasteable as-is: no links that
  require repo access, no "see commit abc123", no reliance on the reader having the tree — every
  claim carries its raw evidence inline. ChatGPT co-reviews critical changes only (T-03, T-04),
  recommending rather than approving, and its input is treated as one more claim to verify.
  A task is not closed until Claude's review has come back and been acted on.
- **INTEGRATE gate**: production deployment is its own tracked step with its own verification.
  Staging-tested ≠ production-live; the live probe in §9 is the evidence.

## 9. Verification helper (the one piece of new automation worth having)

A single read-only probe answers "where does this project actually stand?" — local version,
staging version, production version, live head tags. It is already written and used in this
investigation (`/home/zubbyik/.hermes/cache/scratch/hermes-discovery/probe_live_forms.py`,
read-only GETs + git reads). T-06 promotes it into the repo as `scripts/`. **Deterministic
script, not an LLM call** — cheaper and reproducible.

## 10. Failure and retry

Bounded: attempt → analyse → one retry if recoverable → escalate/BLOCKED. Classify before
retrying: transient · worker/tool · implementation · test · dependency · environmental ·
ambiguous-requirement. **Never retry a rate-limited operation without fixing the root cause**
(Cloudflare/Let's Encrypt — this project has the scars; `HO-031` shows what a premature retry
cost) and never re-run a paying/destructive step to "see if it works".

## 11. Cost control

One OpenCode Go subscription is the budget. Rules: cheap Flash-class models for mechanical work
(commits, docs moves, hygiene, probes); Pro-class only where judgement is load-bearing (form
parity T-03, any design decision); no re-discovery of unchanged context (this investigation's
digests on disk exist for exactly that reason); deterministic scripts over LLM calls for state
checks; workers receive only the files they need, never the repository; sessions are not
heartbeat-polled. Target: *maximum useful engineering output per unit of model usage.*

**Unresolved prerequisite**: the commissioning brief says the orchestrator model is "Kimi K2.7
Code", this session runs `deepseek-v4.1-flash`, `opencode.json` pins `mimo-v2.6-pro/flash`, and
the current context doc refers to a "GLM5.3" architect (conflict C4). Model names are therefore
**not hard-coded** anywhere in this design; routing is decided per task from what the installed
OpenCode configuration actually offers, verified at execution time.

## 12. Security

- Least privilege for workers: no credentials in task contracts or prompts; secrets are read
  from the VPS `.env` by the service, never pasted into a handover.
- Never commit or push anything without the go-ahead; never commit a secret. Two live secrets
  are currently exposed (tracked `contact.js`, tracked `docker-compose.yml`) and the standing
  claim that none are committed is false — T-04 exists to close that.
- Hermes writes only documentation by default; `.agent/` is documentation. Any state-changing
  action (commit, deploy, DB write, container restart) requires an explicit task and, where the
  project's policy demands it, Malachy's go-ahead.

## 13. HERD visibility

HERD remains the human's window onto all of this; nothing here asks it to change. What a human
operator should be able to see without reading 56 handovers: the task list and statuses
(this plan), the current drift (the §9 probe), and what is blocked and on whom (§11 of the
plan, "Blocked on Malachy"). Those three views are the minimum viable interface to this
orchestration.

## 14. Change control over this design (and over the instruction files)

This design is a **proposal**. It deliberately does **not**:
- create `agents.yaml`, `workflow.yaml`, `state/*.json`, or `roles/*.md` — the equivalents
  already exist (`opencode.json` for agent/model routing; `docs/handover/` for roles, gates and
  the record). Creating both would guarantee the drift this project is already suffering.
- edit `AGENTS.md`, `opencode.json`, HERD config, or OpenCode config — that is Malachy's call,
  and conflict B-04 (which instruction source is authoritative) must be settled by him first.
- commit or push anything. `.agent/` is currently untracked by design. Whether it enters git is
  part of the T-01 go-ahead conversation.

One deliberate deviation from the commissioning brief, stated with its reason: the brief's
suggested `.agent/` tree (AGENT.md, agents.yaml, workflow.yaml, state/, roles/) was reduced to
the three named artifacts because the brief itself instructs "do not create this entire
structure blindly — first determine whether an equivalent structure already exists", and it
does. The remaining scaffolding is deferred to a reviewer's decision rather than assumed.
