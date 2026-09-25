---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-054 — Hermes orchestrator discovery: project state, recorded conflicts, live production defects and proposed roadmap (DISCOVERY ONLY — no code changed)
date: 2026-09-25
from: Hermes (orchestrator, deepseek-v4.1-flash) — first run, DISCOVERY / ALIGNMENT MODE
to: Claude[Sonnet] Web (Reviewer) / ChatGPT (Co-reviewer)
status: DISCOVERY COMPLETE — AWAITING REVIEW BEFORE IMPLEMENTATION. Nothing committed, nothing pushed, nothing deployed, no application code modified.
priority: HIGH
---

## 0. What this handover is, and what it is not

Hermes has been introduced as the project orchestrator. Per the commissioning brief
(`docs/handover/hermes-orchestrator.md`), the first run is **discovery and alignment only**.
This handover is the reviewable record of that run. It is written to be **self-contained and
copy-paste ready for Claude** — no GitHub-only references, no links that require repo access,
because the Reviewer reads handovers as pasted text.

It is **not** a request to implement anything. It reports: what the project is, what the
previous agent (OpenCode + MiMo 2.5/2.6) has already done, what is genuinely broken *right now
in production*, which documented claims contradict the code, and what an evidence-backed
roadmap would look like. The final line states the mode.

**Companion artifacts** (not part of the review chain, and currently untracked — they may enter
git as part of the T-01 go-ahead conversation):

- `.agent/investigation.md` — full investigation, 528 lines, every claim tagged `[V]` (verified
  first-hand this session) or `[I]` (delegated read-only inventory), with a raw-output appendix.
- `.agent/implementation-plan.md` — T-01…T-09 tasks with dependencies, acceptance criteria and
  the blocked-on-Malachy list.
- `.agent/orchestration-design.md` — the proposed orchestration model, integrated with the
  existing `docs/handover/` process rather than duplicating it.

---

## 1. Project identity and repository facts

| Item | Value |
|---|---|
| PROJECT_ROOT | `/home/zubbyik/wordpress_project` |
| CURRENT_BRANCH | `main` (single branch, single worktree, no PRs) |
| REMOTE_REPOSITORY | `git@github.com:tikeazubike-lab/zubbyik_press_imad.git` |
| Working tree at session start | 19 modified tracked files, 213 untracked entries |
| Commits | 87, from `a9c8639` (2026-07-08) to `828e852` (2026-09-23) |
| Unpushed work | none — `git rev-list --count origin/main..HEAD` = 0 |
| Tracked files | 130 (`malachy-portfolio` 83, `docs` 20, `imad-automation` 14, `.hermes` 4, root 9) |

Raw:

```
$ git rev-parse --show-toplevel        -> /home/zubbyik/wordpress_project
$ git branch --show-current            -> main
$ git worktree list                    -> /home/zubbyik/wordpress_project 828e852 [main]
$ git rev-list --count HEAD            -> 87
$ git rev-list --count origin/main..HEAD -> 0
$ git status --porcelain | wc -l       -> 232   (19 modified, 213 untracked)
```

---

## 2. The single most important finding: production is behind, and two of the defects are live

Read-only GETs against the live sites, run during this investigation:

```
https://imadconsulting.co.uk/                     http=200
  <meta name="google-site-verification" content="YOUR_VERIFICATION_TOKEN">
  <meta name="description" content="Portfolio · 2026">
  <title>IMAD Consulting — QA Engineer, Web Development &#038; IT Support Specialist…
  asset versions on the page: ?ver=1.3.14
  form#discovery-form : malachy_nonce, action, malachy_name, malachy_email, malachy_message, malachy_hp
  form#contact-form   : malachy_nonce, action, malachy_service, website, malachy_name,
                        malachy_email, malachy_message, source_campaign

https://imadconsult.zubbystudio.site/              http=200   ?ver=1.3.21
https://imadconsulting.co.uk/robots.txt           http=200   Sitemap: .../wp-sitemap.xml
https://api.imadconsulting.co.uk/openapi.json     http=200   {"title":"imad-automation","version":"0.1.0"}
```

So:

- **Production serves theme v1.3.14, which equals HEAD.** Staging serves v1.3.21, which equals
  the *uncommitted working tree* — the bind-mount behaviour documented in the context doc is
  confirmed.
- **Two defects are live in production right now**: a placeholder Google Search Console token
  shipped to real visitors, and a stale tagline (`Portfolio · 2026`) serving as the site's meta
  description. Both are already fixed in the uncommitted `header.php`. `HO-049` said the
  description fix was real "pending production deploy"; its own errata and `HO-049 §2` later
  admitted that was false for production. **The live probe agrees with the errata** — production
  still shows the stale value.
- Production is **7 theme versions behind** the working tree (1.3.14 → 1.3.21): a whole
  workstream (HO-049 → HO-053) that is complete on staging and lives only on one disk.

This is the highest-consequence issue in the project: not code quality, but **state divergence**.
Production, staging, HEAD, the working tree and the documentation each tell a different story.

---

## 3. Current implementation status

| Workstream | Status | Evidence |
|---|---|---|
| Theme v1.3.14 on production | COMPLETE — live | live `?ver=1.3.14`, equals HEAD |
| Theme v1.3.15→v1.3.21 (HO-049…HO-053) | COMPLETE on staging; **uncommitted, undeployed** | live `?ver=1.3.21` on staging; 1 388 insertions in the theme diff; `HO-050:8` states it verbatim |
| Production SEO meta | **BROKEN LIVE** | placeholder GSC token + stale description (§2) |
| Discovery form nonce/honeypot | FIXED and live | both live forms post `malachy_nonce`; handler accepts either name (`inc/contact-handler.php:73,81`) |
| Contact/Discovery field parity | PARTIAL — real mismatches remain | §6 |
| FastAPI backend | COMPLETE and live | `/openapi.json` 200, 5 routes, `/healthz` |
| Daily follow-up reminder | **DEAD CODE — cannot fire** | `app/db.py:70` filters `status='contacted'`; nothing ever writes `leads.status` |
| `content_items` + `/internal/draft` | DEAD | no writes/reads; the generated draft is discarded |
| Theme automated tests | **DO NOT EXIST** | `npm run test` = stub, exit 1 (§5) |
| Production deploy procedure | UNDOCUMENTED — two competing methods | `HO-039:155` (`git pull`) vs theme `README.md:20-23` (zip/FTP) |

---

## 4. What OpenCode + MiMo has already accomplished (credited, not re-litigated)

The predecessor built something genuinely good, and the new orchestration layer must extend it,
not replace it:

1. A working **dual-channel** lead pipeline — Telegram `@imadlead_bot` + email, firing
   independently via `Promise.allSettled`, never chained (a chained design was caught and
   rejected in the HO-023 thread because it silently drops leads).
2. **TLS boundaries worked out properly**: HTTP-01 added for `api.imadconsulting.co.uk` while
   DNS-01 stays the default everywhere else, because Traefik/lego credentials are process-wide —
   a real constraint, diagnosed rather than guessed at.
3. A **root cause** for the chatbot's broken Tier 1: stale OPcache bytecode — not patched over.
4. Discovery that the **Discovery Call form had never worked at all** since it was created
   (nonce field-name mismatch, swallowed by an "invalid nonce" branch), then fixed.
5. The **recurring failure pattern** identified and written down: shared code validated against
   only one of its callers (honeypot field name, then nonce field name).
6. **SEO Phase 0 executed**, including root-causing why Google's index shows stale unrelated
   content for the domain.
7. A **four-role model confirmed by Malachy directly** rather than self-declared by an agent.

What it did *not* do: ship. There is no release step attached to the culture it built.

---

## 5. Validation performed in this investigation (raw output)

All commands non-destructive; nothing started, built, installed or deployed; no write inside the
repository by any delegated worker.

```
$ npm run test            -> "Error: no test specified"      exit 1   <-- AGENTS.md names this as the verification command
$ php -l (27 tracked theme .php files)  -> OK=27 FAIL=0      (php 8.3.6)
$ py_compile imad-automation/app/*.py   -> OK on 8 files
$ npx playwright test --list --config=tests/visual/config/visual.config.ts  -> Total: 54 tests in 2 files
$ bash -n malachy-portfolio/bin/*.sh    -> OK x3
$ docker compose -f docker-compose.yml config --quiet  -> exit 0 (obsolete `version:` warning only)
$ git status --porcelain | wc -l        -> 232  (unchanged after every check above)

$ grep -n baseURL tests/visual/config/visual.config.ts
    19:    baseURL: process.env.VISUAL_BASE_URL || 'https://imadconsulting.co.uk',
```

Two important consequences:

- **`AGENTS.md` claims `npm run test` verifies the project. It verifies nothing and cannot
  pass.** Any future claim of "tests pass" must not lean on it.
- **The only automated check in the repo targets production by default.** All four
  `npm run visual:*` scripts and all three `bin/*.sh` scripts write inside the repo (routes.json,
  screenshots, a zip inside the tracked theme dir, `rm -rf build/`), and `VISUAL_BASE_URL` is
  unset and absent from the repo, so they fall through to `https://imadconsulting.co.uk`. They
  were therefore **not** run. There is today no safe way to ask "did that change break the front
  page?" without touching production.

---

## 6. Confirmed problems (all reproduced, none inferred)

1. **Live production meta defects** (§2) — placeholder GSC token and stale description.
2. **1 388 insertions uncommitted** — HO-049 → HO-053 exist on one disk. Total-loss risk.
3. **Form field parity is still imperfect**, in exactly the class that burned this project twice:
   - `source_campaign` is posted by the contact form (`template-parts/section-contact.php:66`)
     and **never read** by the handler (handler read in full).
   - `malachy_service` reaches the PHP email body (`contact-handler.php:98,116-119`) but is
     **absent from the JSON payload** (`assets/js/contact.js:137-144`), so Telegram leads arrive
     unlabelled.
   - The two forms share one handler while sending different field sets; the honeypot differs by
     form (`website` vs `malachy_hp`) and is tolerated by a dual check rather than converged.
   - `malachy_phone`, `malachy_whatsapp`, `malachy_twitter` render as settings inputs but have
     **no `register_setting` entry** — only three calls exist in the theme
     (`inc/meta-boxes.php:135`, `functions.php:329,341`) — so submitting the options page
     discards them.
   - Two admin pages share the option group `malachy_theme_settings`, so submitting one can blank
     the other's fields.
4. **The daily follow-up reminder cannot fire** while `docs/manual/imad-automation-workflow.md`
   §4 tells the owner to mark leads `contacted` — a feature advertised as working that has no
   code path to ever run.
5. **Live secrets in tracked files** (values never printed anywhere in this record):
   - `assets/js/contact.js:97` holds a 64-hex literal **byte-identical** to `IMAD_FORM_SECRET` in
     the VPS `.env` (sha256 prefix `96ef7cc2fbf5` on both sides), sent as `X-Form-Secret`, in a
     tracked and publicly served file.
   - Tracked `docker-compose.yml` at HEAD holds three **literal 48-char hex DB passwords**
     (`WORDPRESS_DB_PASSWORD:14`, `MYSQL_PASSWORD:45`, `MYSQL_ROOT_PASSWORD:46`). Verified they
     do **not** equal the live Postgres credential in `.env`, so these are the stack's own values
     rather than the API credential — but they are committed, and history retains the weak
     human-typed values from `a09a221`/`4524cde`/`f7c5594` until the `41dd54e` rotation.
   - This **contradicts** `HO-022:239` ("No secrets committed") and `AGENTS.md`'s own "Never
     commit secrets" rule. `HO-026` also reproduces a form secret and a Postgres password in
     prose inside a tracked handover.
   - Hygiene inversion: `.env` is mode `664` (world-readable) while the compose file is `600`.
6. **Stale duplicate theme** at `build/malachy-portfolio/` (v1.2.1, gitignored) still carrying
   pre-HO-041 honeypot logic — any repo-wide grep that does not exclude `build/` returns
   contradictory answers about form security.
7. **Dead/duplicated code**: `AnimationManager.js` enqueued as a dependency of all 8 section
   modules and called by none; `add_editor_style()` pointing at a file that does not exist;
   `404.php` using Tailwind classes the theme never ships; the offers section rendering 4 of 9
   offers by construction; the REST route `malachy/v1/contact` with no client caller;
   `malachy_resume_url` write-only.
8. **Repo hygiene**: 213 untracked entries including a 656 MB Playwright browser cache, a
   root-owned `db-data/` MySQL datadir, tarballs and zip copies, plus **9 stray `.md` files at
   the repo root** — among them `HANDOVER.md` and `Handover.md` (byte-identical duplicates) and
   `HO-013`/`HO-014`/`HO-016-checkwebsite`, which therefore escape any `docs/handover/HO-*.md`
   glob.

---

## 7. Recorded conflicts between documentation and reality

The full list is 19 items in the companion investigation; the load-bearing ones:

| # | Conflict | Which side reality is on |
|---|---|---|
| C1 | `Imad-project-context.md` says "version currently 1.3.13 pending deploy" — and tells the reader to verify via `MALACHY_THEME_VERSION`, which reads **1.3.21** (HEAD: 1.3.14) | The doc is stale and self-refuting |
| C2 | `AGENTS.md` names "Active task: SEO Phase 0" (completed at `4ae6f3b`) and names `npm run test` as verification (stub, exit 1) | Wrong on both counts |
| C3 | `AGENTS.md` and `opencode.json` disagree on which docs auto-load; the onboarding doc calls one of `AGENTS.md`'s named docs "superseded" | Two loaders, two answers |
| C4 | Model identity: this session runs `deepseek-v4.1-flash`; the brief says "Kimi K2.7 Code"; `opencode.json` pins `mimo-v2.6-pro/flash`; the context doc refers to a GLM5.3 architect | Unresolved — needs Malachy (B-04) |
| C5 | `HO-041` still reads `Status: OPEN` while the context doc marks its item CLOSED with ancestor confirmation | Closing evidence exists; the doc was never edited |
| C6 | Production deploy method: `HO-039:155` `git pull` vs theme `README.md:20-23` zip/FTP | Two procedures, no canonical one |
| C7 | Honeypot/nonce field names documented at three points, resolved in code by dual-name fallbacks rather than convergence | Code is tolerant; the contract is undocumented |
| C8 | August 2026: handovers record work dated to 2026-08-27, git records **zero commits** that month (2026-07: 22, 2026-09: 65) | Docs-only history — treat as UNCERTAIN |
| C9 | `HO-051` is referenced by `HO-052 §7` but was never written | Record gap |
| C10 | Two case-variant context docs (`imad-project-context.md` / `Imad-project-context.md`); `HO-016` used twice; `docs/adr/` empty despite 3 `*-adr-report-milestone-1.md` files | Structural duplication |

---

## 8. Proposed roadmap (proposal only — this handover does not authorise it)

Sequence reflects dependencies and the project's own rules. Each becomes its own `HO-055+`
handover on execution. Full acceptance criteria, test requirements and expected artifacts are in
`.agent/implementation-plan.md`; reproduced here in summary for review.

**Phase 0 — Discovery / alignment.** T-00: this handover + the three `.agent/` artifacts. COMPLETE.

**Phase 1 — Unblock the release; fix what is live-broken.**
- **T-01** Commit and release theme v1.3.15→v1.3.21 (reviewed commits split by concern, then
  production deploy with its own verification). Gated on Malachy's go-ahead.
- **T-02** Verify the two live production meta defects are gone after that deploy (no
  `YOUR_VERIFICATION_TOKEN` anywhere in the rendered head; description = curated string, not
  `blogdescription`).
- **T-03** Close the form field-parity gaps (§6.3) — **checking every caller of every shared
  handler/template/JS file first**, as the project's own rule demands, including the stale
  `build/` copy.

**Phase 2 — Supporting work.**
- **T-04** Remove live secrets from tracked files and rotate `IMAD_FORM_SECRET` (coordinate with
  T-03 — same file — and with the backend's `verify_form_secret`).
- **T-05** Make the follow-up reminder real or delete it (owner's product call — B-07).
- **T-06** One canonical release procedure + a read-only drift probe (local/staging/production
  version + live head tags) so "is production current?" is one command instead of manual curls.

**Phase 3 — Testing / hardening.**
- **T-07** Repo hygiene and record consolidation (the 9 stray root docs, the duplicate context
  docs, `HO-051`, the stale `build/` snapshot) — recommended *before* T-01's commit so the commit
  lands on a clean tree.
- **T-08** A real regression net that cannot default to production: a staging-only visual profile
  plus a lint script (`php -l` + `bash -n` + `py_compile`), and either make `npm run test` real or
  remove it and correct `AGENTS.md`.

**Phase 4 — Review / integration.** T-09: the existing gate — Claude Web reviews every handover
without exception; ChatGPT co-reviews the critical ones (T-03, T-04).

**Blocked on Malachy — not tasks for an agent; do not guess, wait:**
B-01 go-ahead to commit/push/deploy (stonewalls all of Phase 1) · B-02 Search Console token paste
+ Request Indexing · B-03 SEO Phase 1 plugin/per-page-meta decisions · B-04 which instruction
source is authoritative (C3/C4) · B-05 whether the public-JS form secret stays as an accepted
risk · B-06 production DB state (test rows; project reseed; experience dedupe) · B-07 whether the
follow-up feature is wanted at all.

**Deliberately not planned** (permanent non-goals recorded in the project's own docs): no n8n ·
no WhatsApp Status automation · no AI lead scoring · no paid SaaS · no second Postgres instance ·
no framework migration · no new orchestration framework · no parallel worktrees while the tree
carries this much unresolved state.

---

## 9. Deviations from the commissioning brief, stated explicitly

1. **`.agent/` scaffolding reduced.** The brief suggested `AGENT.md`, `agents.yaml`,
   `workflow.yaml`, `state/*.json` and `roles/*.md`. The brief also says not to create that
   structure blindly where an equivalent already exists — and equivalents do: `opencode.json`
   (agent/model routing), `docs/handover/` (roles, gates, the work record), `.hermes/plans/`
   (Hermes-native plans). Only the three named artifacts were written. The rest is deferred to a
   reviewer's decision rather than assumed.
2. **No new instruction authority created.** This handover and the `.agent/` documents *describe*;
   they do not command. Adding a fifth instruction source while C3/C4 are unresolved would deepen
   the staleness already causing the drift in §7 — so `AGENTS.md` and `opencode.json` were left
   untouched pending B-04.
3. **Model names not hard-coded** anywhere in the design, per the brief's own instruction to
   verify what the installed OpenCode configuration actually offers — and because C4 shows four
   conflicting model identities in this project's own records.
4. **No commit of the discovery artifacts.** The project's standing policy (`HO-050:8`,
   `HO-050:161`, `HO-053:71`) keeps work local/staging until go-ahead. `.agent/` and this
   handover are written to disk only.

---

## 10. Corrections applied to delegated findings (kept visible on purpose)

Two claims from the delegated read-only inventories were checked and found imprecise, and are
recorded here rather than quietly dropped:

- A delegated inventory reported the committed DB passwords as "64-hex"; my own measurement is
  **48-char hex**.
- A delegated inventory reported `php -l` passing on 43 files; my own run over the **27 tracked**
  theme PHP files gives `OK=27 FAIL=0` (the larger count likely included untracked duplicates).

Also worth noting for anyone re-running the checks: `npm run test`'s exit code must be read
without a pipe — piped through `tail`, the shell reports `tail`'s status (0), not npm's (1).

---

## 11. Application code modified: NONE

Nothing in `malachy-portfolio/`, `imad-automation/`, the database, the VPS, Traefik, HERD or the
OpenCode configuration was changed. `git status --porcelain` went from 232 to 233 entries during
this session, the single new entry being the untracked `.agent/` directory. No commit, no push,
no deploy, no container action.

**Review request:** please verify the two live production defects (§2) against your own read of
`header.php` and the live page, and specifically check the shared-code claim in §6.3 — this
project has twice been burned by a shared field name validated against only one caller, and T-03
should not proceed until a reviewer has enumerated every caller independently.

**DISCOVERY COMPLETE — AWAITING REVIEW BEFORE IMPLEMENTATION**
