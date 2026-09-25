# .agent/investigation.md — IMAD Consulting, discovery & alignment

**Produced by**: Hermes (orchestrator) — first run, DISCOVERY / ALIGNMENT MODE
**Date**: 2026-09-25
**Scope**: read-only investigation of `/home/zubbyik/wordpress_project`. No application
code, configuration, database, infrastructure, HERD or OpenCode config was modified.
**Method note**: every claim below carries `[V]` (verified first-hand by the orchestrator
in this session, raw output reproduced in §17) or `[I]` (taken from a delegated read-only
inventory; digest path given). Where a delegated claim was checked and found imprecise, the
correction is stated. The project's own standard — raw output over narrated summaries —
has been applied to *this document* as well.

---

## 1. Executive summary

This is **not** a general software project. It is the production website and lead-capture
system of a one-person consulting business:

- **Product**: `imadconsulting.co.uk` — the freelance portfolio of Malachy Egbuna
  (QA engineer / SysAdmin / IT support / WordPress), plus a self-hosted lead-capture and
  content-automation pipeline built specifically to convert a WhatsApp-Status content
  campaign into tracked leads.
- **Two codebases in one repo**: a hand-written WordPress theme (`malachy-portfolio`,
  27 tracked PHP files, GSAP-animated front page) and a ~5-route FastAPI service
  (`imad-automation`) on a VPS behind Traefik, with Postgres, Telegram and email
  notification.
- **Maturity**: the theme is a mature, repeatedly-refined product (v1.3.21 on disk).
  The backend is small but real and deployed. What is *not* mature is the release process:
  production is **7 theme versions behind** the working tree, the last workstream
  (HO-049 → HO-053) is entirely uncommitted, and the repo root has accumulated 213
  untracked entries (screenshots, tarballs, a 656 MB Playwright browser cache, a
  root-owned MySQL datadir).
- **Where it stands**: three separate, *documented as complete* workstreams (SEO/title/meta
  fixes, the project showcase rebuild, mobile carousel progression) exist on disk and are
  live on staging, but are uncommitted and absent from production. Meanwhile production
  still serves two visibly broken SEO artifacts — `google-site-verification` =
  `YOUR_VERIFICATION_TOKEN` and a stale `description` of `Portfolio · 2026` — both of which
  the uncommitted code already fixes.
- **The single biggest risk** is not code quality; it is **state divergence**: docs, staging,
  production, HEAD and the working tree each tell a different story, and the project's own
  "single source of truth" (`Imad-project-context.md`) is demonstrably stale on the one fact
  it tells the reader to verify.

**Bottom line**: the engineering work in progress is sound and near-complete. The missing
capability is a *release and record discipline* — which is precisely the gap an orchestrator
should close, and which this project already has a partial, proven mechanism for
(`docs/handover/` + the four-role model).

---

## 2. Existing architecture (as actually implemented)

```
                 ┌──────────────────────────────────────────────┐
  WhatsApp       │  imadconsulting.co.uk  (InMotion shared host)│
  Status / QR ──▶│  WordPress + malachy-portfolio theme         │
  campaign link  │  PHP 7.0 target, no Docker, no Postgres      │
                 └───────┬───────────────────────┬──────────────┘
       /r/status?campaign=X │                    │ contact.js: Promise.allSettled
                 ┌───────▼───────────────────────▼──────────────┐
                 │  api.imadconsulting.co.uk  (Netcup VPS)      │
                 │  FastAPI · uvicorn :8090 · Docker · Traefik  │
                 │  routes: /healthz, POST /api/leads,          │
                 │  GET /r/{source}, /internal/draft,           │
                 │  GET /internal/dead-letters                  │
                 └───────┬───────────────┬──────────────────────┘
                         │ asyncpg       │ retry + dead-letter
                 ┌───────▼────────┐  ┌───▼──────────────────────┐
                 │ openagile_     │  │ Telegram @imadlead_bot   │
                 │ postgres       │  │ (leads channel)          │
                 │ db: imad       │  └──────────────────────────┘
                 └────────────────┘
```

Theme internals: `front-page.php:10-18` includes 9 template parts in order
hero → about → offers → skills → projects → experience → testimonials → blog-preview →
contact. `functions.php` owns asset enqueueing (single function, `wp_is_mobile()` gated),
a `template_include` filter forcing `home.php` for a `blog` slug page, an options page, and
the version constant. `inc/*.php` add 5 CPTs, 3 taxonomies, 6 meta boxes, the contact
handler (REST + admin-ajax), the AI chatbot, and the data seeder. GSAP/ScrollTrigger/
TextPlugin are vendored locally in `assets/js/vendor/` (pinned `3.12.5`).
`[V]` (file lists, includes) `[I]` (hook inventory: B digest, `B-code-map.md`)

Backend internals: `app/config.py` hard-requires `IMAD_DATABASE_URL`,
`IMAD_TELEGRAM_BOT_TOKEN`, `IMAD_TELEGRAM_CHAT_ID`, `IMAD_FORM_SECRET`,
`IMAD_ADMIN_API_KEY`; 4 tables (`leads`, `clicks`, `content_items`, `dead_letters`); an
in-process APScheduler daily job; Anthropic call in `ai.py` for draft generation.
`[I]` (C digest S8, B digest) — route set independently confirmed live via
`/openapi.json`. `[V]`

**Notification design (locked, and honoured by the code)**: Telegram and email fire
independently — `Promise.allSettled` in `assets/js/contact.js:150-168`, never chained.
`[I]` + locked-decision record in `Ho-reply-023`/`HO-023`.

---

## 3. Technology stack (actual, not aspirational)

| Layer | What is actually there |
|---|---|
| Theme | Hand-written PHP templates, no build step, no framework, no composer. PHP 7.0 compatibility is a hard constraint (shared host). |
| CSS | One 5 k-line `assets/css/main.css` (hand-written, 500 lines of uncommitted change) + `ai-chat-bot.css`. No preprocessor. |
| JS | Vanilla + vendored GSAP 3.12.5 + ScrollTrigger/TextPlugin. Per-section modules. No bundler, no npm build, no framework. |
| Animation | `AnimationManager.js` exposes `window.MalachyAnim` and is enqueued as a dependency of all 8 section modules — but **no module calls it** (architecturally dead). `projects.js` is deliberately vanilla+WAAPI. `[I]` |
| Backend | Python 3.12-slim, FastAPI, asyncpg, uvicorn, APScheduler, anthropic SDK. `requirements.txt`, one Dockerfile, non-root user. |
| Data | Postgres 15+ in a **shared** container `openagile_postgres` (also serves the unrelated EPM v2 project — locked decision: do not create a second instance). |
| Edge | Traefik with two certresolvers: `cloudflare` (DNS-01, default) and `letsencrypt-http` (HTTP-01, only for `api.imadconsulting.co.uk`). |
| Tooling | npm (Playwright only), `bin/*.sh` (build/package/screenshot), WP-CLI (`wp malachy reconcile`). **No CI, no Makefile, no phpunit/composer, no linter, no type checker.** `[V]` (`.github/` absent in this repo, no test runner) |

---

## 4. Repository structure (important paths only)

| Path | Purpose | State |
|---|---|---|
| `malachy-portfolio/` | The WordPress theme (83 tracked files) | v1.3.21 on disk, HEAD at 1.3.14 |
| `imad-automation/` | FastAPI lead-capture service (14 tracked files) | deployed, healthy |
| `docs/handover/` | **The project's canonical work record** — 56 documents, HO-001…HO-053 | 39 of them untracked |
| `docs/manual/`, `docs/report/`, `docs/adr/` | workflow manual, R-001 audits, ADR dir | `docs/adr/` is **empty** despite 3 files named `*-adr-report-milestone-1.md` |
| `.hermes/plans/` | 4 Hermes plan-mode artifacts, July 2026 | tracked, modified |
| `AGENTS.md`, `opencode.json` | the two instruction files that actually apply | see §5 |
| `docker-compose.yml` | VPS stack (Traefik, WP staging, Postgres, imad-automation) | **tracked, contains literal DB passwords** (§11) |
| `.env`, `.env.imad` | real secrets | untracked + gitignored (correct) |
| `build/` | stale theme snapshot **v1.2.1** | gitignored, 0 tracked files, contains pre-HO-041 honeypot code `[V]` |
| `tests/visual/` | Playwright capture suite (54 tests, 2 files) | untracked, **defaults to production** |
| repo root | 9 stray `.md` files incl. duplicate `HANDOVER.md`/`Handover.md` (byte-identical), 213 untracked entries total | hygiene debt |

---

## 5. Existing agent instructions and their effective hierarchy

Four instruction sources exist; **they do not agree**.

| Source | Scope claimed | Reality |
|---|---|---|
| `AGENTS.md` (tracked, 2 445 B) | Architect role = MiMo-V2.6-Pro; lists "Active task: SEO Phase 0"; names `docs/handover/Imad-project-context.md` + `Delegation-prompt-for-glm.md` as auto-loaded; verification = `npm run test` | Names an active task that is **already complete** (HO-047/049). The named verification command is a stub that exits 1. `[V]` |
| `opencode.json` (tracked, modified) | `instructions: [onboarding_handover_glm3.5_mimo2.6.md, Imad-project-context.md]`; agents `build`=mimo-v2.6-flash, `plan`=mimo-v2.6-pro | This is the file OpenCode actually loads. Does not include `AGENTS.md`'s two named docs. `[V]` |
| `docs/handover/onboarding_handover_glm3.5_mimo2.6.md` (untracked, 2026-09-22) | "Every new session reads this file first"; declares `Delegation-prompt-for-glm.md` *historical/superseded* | Self-declared onboarding doc, but untracked → a fresh clone never gets it. `[V]` |
| `docs/handover/Imad-project-context.md` (untracked, "last updated 2026-09-24") | "Single source of truth", bootstrap for the Architect | Untracked **and** one of its key facts is wrong (§16 conflicts C1). A case-variant twin `imad-project-context.md` (2026-09-22) sits beside it. `[V]` |

**Effective hierarchy as of today**: `opencode.json` → `onboarding_handover…md` →
`Imad-project-context.md` → (ignored by the loader) `AGENTS.md`. The `hermes-orchestrator.md`
document that commissioned this investigation adds a fifth authority and is itself untracked.

---

## 6. Development history (reconstructed from git + handovers)

87 commits, 2026-07-08 → 2026-09-23. Commit-volume by month: **2026-07: 22, 2026-08: 0,
2026-09: 65**. `[V]` The zero-commit August is a real evidence gap: handover docs record
work dated 2026-07-23 → 2026-08-27 (`HO-010`…`HO-016`) that produced **no commits at all**
— the next commit is `2c7425b` on 2026-09-03. Treat any "what happened in August" claim
as `UNCERTAIN`: the docs describe it, git does not.

| Phase | Dates | What happened | Evidence |
|---|---|---|---|
| P1 | 07-07 → 07-09 | React → WordPress theme conversion; GSAP ScrollTrigger pinning; CPTs; contact handler | `a9c8639`, `5ddb568`, `74cdb9d`, `e8c8536`; HO-001 `[I]` |
| P2 | 07-19 → 07-21 | AI chatbot, 3 iterations; root cause of a broken Tier 1 = **stale OPcache bytecode** | HO-002…HO-008; R-001 audits `[I]` |
| P3 | 07-23 → 09-03 | Repositioning to Microsoft 365 / email-deliverability; hero timeline GSAP; 9-offer ladder; `?service=` param; Listmonk lead magnet | HO-010…HO-016; `2c7425b` `[I]` |
| P4 | 09-15 → 09-18 | Full redesign to the "polished-portfolio" reference; favicon/branding churn; 1003-line forensic audit; canonical content (9 testimonials); mobile hero interlock fixed over 4 commits | `6f5e083`, `674f537`, `03b4584`, `f51b3bb`…`6a6eb47`; HO-017…HO-021 `[V]` |
| P5 | 09-20 → 09-22 | Backend integration (`4524cde`), TLS via HTTP-01, credential rotation, Telegram `@imadlead_bot`, secret scan; **HO-041: discovery form never worked since creation (nonce field-name mismatch)** | `4524cde`, `f7c5594`, `4f6fa23`, `cb4ec7e`, `f1eca1f`, `cc28e7e` `[V]` |
| P6 | 09-23 → 09-24 | SEO Phase 0 executed; title/meta/GSC; work-showcase rebuild; mobile carousel progression. **All of it uncommitted.** | `9286f7b`, `4ae6f3b`, `6389f46`, `828e852`, then working tree at v1.3.21 `[V]` |

**Release/deploy track**: production was pulled to 1.3.10 (`HO-039`), then 1.3.12, then
1.3.14 (`HO-048`). Live production today advertises `?ver=1.3.14`. `[V]` So *HEAD is live*,
and everything after HEAD is not.

---

## 7. What OpenCode + MiMo (the previous agent) has already accomplished

Simultaneously the predecessor's most impressive work and the source of today's confusion:
a **complete, evidence-disciplined engineering culture** with no release mechanism attached
to it.

Already done and verifiable:
- A working dual-channel lead pipeline (Telegram + email, independent via `allSettled`).
- A live TLS-protected API on a distinct hostname, with the certresolver boundary
  (HTTP-01 vs DNS-01) worked out and documented rather than guessed.
- Two classes of *real* bug found and root-caused through investigation rather than
  patching: the OPcache-stale-bytecode chatbot failure, and the discovery form that had
  **never worked** since its creation (nonce field-name mismatch, silently swallowed by an
  "invalid nonce" branch).
- A recurring-failure pattern captured in writing and promoted to a standing rule:
  *shared code validated against only one of its callers* (honeypot field name, then nonce
  field name).
- SEO Phase 0 executed to the point of identifying the stale-index root cause.
- A four-role model (Architect / Implementer / Reviewer / Co-reviewer) **confirmed by
  Malachy directly** rather than self-declared by an agent.

Left undone by the predecessor:
- The release: v1.3.15 → v1.3.21 uncommitted, undeployed, with production data
  (project reseed, experience dedupe) also pending.
- `HO-051` was never written even though `HO-052 §7` references the decisions it made.
- The production deploy *method* was never pinned down to one canonical procedure
  (§16 conflict C11).

---

## 8. Current implementation status inventory

| # | Workstream | Status | Evidence / confidence |
|---|---|---|---|
| 1 | Theme v1.3.14 (HEAD) on production | COMPLETE — live | `[V]` production serves `?ver=1.3.14`; `= HEAD` |
| 2 | Theme v1.3.15 → v1.3.21 (HO-049…HO-053) | **COMPLETE on staging, NOT committed, NOT deployed** | `[V]` staging serves `?ver=1.3.21`; 1 404 insertions uncommitted; HO-050 §8 status line states it verbatim |
| 3 | Production SEO meta (description, GSC token) | **BROKEN in production** | `[V]` live HTML: `description="Portfolio · 2026"`, `google-site-verification="YOUR_VERIFICATION_TOKEN"`; fixed only in uncommitted files |
| 4 | Discovery Call form (nonce/honeypot) | FIXED and live (both field names accepted) | `[V]` live + staging HTML both post `malachy_nonce`; handler accepts either name (`contact-handler.php:73`) |
| 5 | Contact form field parity | PARTIAL — several real mismatches remain | `[I]`+`[V]` see §10 item 4 |
| 6 | imad-automation backend | COMPLETE and live | `[V]` `/openapi.json` 200, 5 routes, `/healthz` present |
| 7 | Daily follow-up reminder job | **DEAD CODE — cannot ever fire** | `[V]` `db.py:70` filters `status='contacted'`; nothing in the codebase ever writes `leads.status` |
| 8 | `content_items` table + `/internal/draft` | DEAD (no writes/reads; draft discarded) | `[I]` B digest |
| 9 | AI chatbot (3-tier, 18 patterns) | Working; cosmetic staleness (`SysAdmin` strings) | `[I]` HO-006/007; `ai-chat-bot.php:705,722,756` |
| 10 | Chatbot REST lead capture | PARTIAL — nonce is decorative (never sent, `permission_callback => '__return_true'`) | `[I]` B digest |
| 11 | Automated visual regression | EXISTS but unusable safely (targets **production** by default) | `[V]` `visual.config.ts:19` |
| 12 | Test suite for theme logic | **DOES NOT EXIST** — `npm run test` is a stub, exit 1 | `[V]` |
| 13 | Production deploy procedure | UNDOCUMENTED / two competing methods | `[V]` `HO-039:155` says `git pull`; `malachy-portfolio/README.md:20-23` says FTP/cPanel zip |
| 14 | Repo hygiene | DEGRADED — 213 untracked, duplicate docs, stale `build/` v1.2.1, root-owned `db-data/` | `[V]` |
| 15 | Secrets in tracked files | **CONFIRMED PROBLEM** | `[V]` §11 |

---

## 9. Tests and validation — what was actually run

Run by the orchestrator in this session, non-destructive, nothing started or deployed:

| Command | Raw result |
|---|---|
| `npm run test` (AGENTS.md's stated verification) | `Error: no test specified` → **exit 1** `[V]` |
| `php -l` over all 27 tracked theme PHP files | `OK=27 FAIL=0` (PHP 8.3.6) `[V]` |
| `py_compile` over `imad-automation/app/*.py` (8 files) | `OK on 8 files` `[V]` |
| `npx playwright test --list --config=tests/visual/config/visual.config.ts` | `Total: 54 tests in 2 files`, exit 0 `[V]` |
| `bash -n` on the three `bin/*.sh` | 3/3 OK `[V]` |
| `docker compose -f docker-compose.yml config --quiet` | exit 0 (only obsolete-`version:` warning) `[V]` |
| Read-only GETs: production, staging, `robots.txt`, `/openapi.json` | 200s; versions and head tags in §17 `[V]` |
| Repo integrity after all of the above | `git status --porcelain` = 232 entries, unchanged `[V]` |

**Cannot be run safely today** (and why): all four `npm run visual:*` scripts and all three
`bin/*.sh` scripts — they write into the repo (`routes.json`, screenshots, a zip inside the
tracked theme dir, `rm -rf build/`), and the visual suite **defaults to hitting production**
because `VISUAL_BASE_URL` is unset and absent from the repo. There is no test that exercises
theme logic; "does it work" is currently answered by screenshots of the live site.
`[V]`+`[I]` C digest.

---

## 10. Known issues (confirmed problems, ordered by consequence)

1. **Production SEO artifacts are live-broken** — placeholder GSC token and stale tagline
   description. Fixed on disk, not deployed. `[V]`
2. **7 versions of theme work are uncommitted** — a single disk failure loses HO-049 → HO-053
   entirely; nobody else can build on it. `[V]`
3. **Two live secrets are in tracked/committable files** — see §11.
4. **Form field parity is still imperfect** (the project's own twice-burned failure class):
   - `source_campaign` is sent by the contact form (`section-contact.php:66`) and never read by
     the handler. `[V]` (handler read in full)
   - `malachy_service` reaches the PHP email body but is **absent from the JSON payload**
     (`contact.js:137-144`), so the Telegram lead arrives unlabelled. `[I]`
   - Two forms share one handler but send different field sets (discovery has no
     `malachy_service`/`source_campaign`; contact uses honeypot `website`, discovery uses
     `malachy_hp`). Both are papered over by dual-name checks rather than converged. `[V]`
   - Three settings inputs (`malachy_phone`, `malachy_whatsapp`, `malachy_twitter`) have **no
     `register_setting` entry** — only 3 calls exist in the whole theme — so submitting the
     options page discards them. `[V]`
5. **The daily follow-up job can never fire** — the feature is advertised to the owner in the
   workflow manual as working. `[V]`
6. `AnimationManager.js` is enqueued as a dependency but used by nothing; `add_editor_style()`
   points at a file that does not exist; `404.php` uses Tailwind classes the theme never
   ships; the offers section renders 4 of the 9 offers by construction. `[I]`
7. Stale theme duplicate at `build/malachy-portfolio/` (v1.2.1) still containing the
   pre-HO-041 honeypot code — any repo-wide grep without `--exclude build/` returns
   contradictory answers about form security. `[V]`

---

## 11. Technical debt

- **Repo hygiene**: 213 untracked entries including `malachy-portfolio.tar.gz`,
  `imad-automation_2.zip`/`_3.zip`, a 656 MB `.playwright-browsers/`, `.playwright-mcp/`
  logs, ~15 stale PNGs, a root-owned `db-data/` MySQL datadir, and **9 stray `.md` files at
  the repo root** — among them `HANDOVER.md` and `Handover.md`, byte-identical duplicates of
  each other, plus `HO-013`, `HO-014`, `HO-016-checkwebsite` which therefore escape any
  `docs/handover/HO-*.md` glob. `[V]`
- **Documentation duplication**: two case-variant context docs; `HO-016` used twice; missing
  `HO-023` (exists as `Ho-reply-023`), `HO-027`, `HO-028` (quoted only), `HO-051` (never
  written); empty `docs/adr/`. `[I]`
- **Secrets** `[V]` (my own measurements):
  - `malachy-portfolio/assets/js/contact.js:97` ships a 64-hex literal **byte-identical to
    `IMAD_FORM_SECRET` in `.env`** (sha256 prefix `96ef7cc2fbf5` both sides), sent as
    `X-Form-Secret`, in a file that is git-tracked and publicly served. Documented as
    intentional ("bot filter, not auth", `HO-026:129`) — accepted risk, but it means anyone
    can POST to the live lead endpoint.
  - Tracked `docker-compose.yml` @HEAD holds three **literal 48-char hex DB passwords**
    (`WORDPRESS_DB_PASSWORD:14`, `MYSQL_PASSWORD:45`, `MYSQL_ROOT_PASSWORD:46`). I verified
    they do **not** equal the live Postgres credential in `.env`, so these are the local/VPS
    stack's values, not the production API credential — but they are committed, and the
    history retains weak human-typed values from `a09a221`/`4524cde`/`f7c5594` until the
    `41dd54e` rotation. This **contradicts** `HO-022:239` ("No secrets committed") and
    `AGENTS.md:57` ("Never commit secrets"). `[V]` + `[I]` C digest S1/S2
  - Secrets are also reproduced in prose inside tracked handovers (`HO-026` prints the form
    secret and a Postgres password literal; `HO-036:141` shows a bot-token fragment). `[I]`
  - File-hygiene inversion: `.env` is mode `664` (world-readable) while the compose file
    holding some of the same material is `600`. `[V]`
- **Stale build artifact**: `malachy-portfolio/malachy-portfolio-v1.0.0.zip` is committed
  from `bin/package.sh`'s hardcoded `VERSION=1.0.0` while the theme is at 1.3.21. `[I]`
- **Dead code inventory**: `AnimationManager.js`, `content_items`, `/internal/draft`,
  `/contact` REST route (no client caller), `malachy_resume_url` (write-only), the
  `show_discovery` CTA branch, scheduler + `notify` follow-up path. `[I]`+`[V]`

---

## 12. Architectural risks

1. **State divergence is the dominant risk** — five sources of truth (production, staging,
   HEAD, working tree, docs) currently disagree, and the "single source of truth" doc is the
   one that is wrong. Every future task inherits that confusion until it is collapsed. `[V]`
2. **No release mechanism** — deploy is manual, undocumented as a procedure, has two
   competing documented methods, and is explicitly gated on Malachy's go-ahead. Any
   orchestration that assumes it can ship is wrong. `[V]`
3. **No regression net for the theme** — the only automated check is screenshots against
   *production*. Refactoring is therefore effectively unverified, which is why the project's
   own rule is "small controlled changes". `[V]`
4. **Shared-code coupling** — one PHP handler + one JS file serve two different forms with
   different field sets; one options group is shared by two admin pages (submitting page A
   can blank page B's settings). This is the structural cause of the twice-repeated failure
   pattern, not an accident. `[V]`+`[I]`
5. **PHP 7.0 on a shared host** — the theme must stay 7.0-compatible while local `php -l`
   runs 8.3. Lint passing locally is *not* evidence of production compatibility. `[V]`
6. **Single shared Postgres container** also serving an unrelated project (EPM v2) — a
   resource or migration mistake here damages another project's data. Locked decision, real
   blast radius. `[V]` (documented; not independently confirmed)
7. **The precedence problem this document itself creates** — adding a 5th instruction
   authority without resolving §5 makes staleness worse, not better. Deliberately kept to
   three documents that *describe* rather than *command* (see `.agent/orchestration-design.md`).

---

## 13. Existing conventions that must be preserved

- **Handovers are the unit of work**: `HO-NNN` with frontmatter
  `type/project/title/date/from/to/status/priority`, containing raw evidence, decisions,
  deviations, remaining items. Every unit of work gets one. `[V]`
- **Commit style**: `type: lowercase description` (`feat:` / `fix:` / `docs:` / `chore:`) —
  87/87 commits conform. Small, single-purpose commits. `[V]`
- **No branching strategy in use** — one long-lived `main`, no PRs, no worktrees. `[V]`
- **Evidence discipline**: raw command output over narrated summaries; investigate before
  deciding (`grep`, `curl`, `git log -S`); state deviations explicitly; deployment is its own
  tracked step; never retry a rate-limited operation without fixing the root cause. `[V]`
- **Change-control**: no commit, no push, no production deploy without Malachy's explicit
  go-ahead (stated verbatim in `HO-050:8`, `HO-050:161`, `HO-053:71`). `[V]`
- **Theme versioning**: `MALACHY_THEME_VERSION` in `functions.php` + `style.css` header must
  move together; it is the cache-buster for every enqueued asset. `[V]`
- **Role model**: Architect (MiMo-V2.6-Pro) → Implementer (MiMo-V2.6-Flash) → Reviewer
  (Claude Web, reviews *every* handover, no exceptions) → Co-reviewer (ChatGPT, recommends
  only). Changes to this structure come from Malachy, never from an agent. `[V]`
- **Claude has no GitHub access** — handovers reach it by copy-paste; they must therefore be
  self-contained and text-friendly. `[V]`

---

## 14. Current direction (what the project is actually moving toward)

From evidence, not aspiration: the owner is executing a **repositioning + content-driven lead
funnel** — the site's copy moved from "QA Engineer, SysAdmin & IT Support" toward
"QA Engineer, **Web Development** & IT Support" (HO-048 backfill, `6389f46`), with a 9-offer
ladder, a work showcase rebuilt as a 3D looping carousel, SEO Phase 0 completed, and a
deliberately *manual* WhatsApp-Status content channel feeding tracked campaign links into a
self-hosted backend. Non-goals are recorded as permanent, not temporary: no n8n, no
WhatsApp Status automation, no AI lead scoring, no paid SaaS.

Direction of travel for the *engineering* work: finish the outstanding release
(v1.3.15→1.3.21 + production data), then SEO Phase 1 (per-page meta, plugin — blocked on
Malachy's GSC/plugin decisions), then hardening the lead path. `[I]` P6 + open items.

---

## 15. Recommended next development tasks (evidence-derived; full detail in `.agent/implementation-plan.md`)

1. **T-01 — Commit and release the outstanding 7 versions** (HO-049→HO-053 + the
   uncommitted context/handover edits) to a reviewed commit, then deploy to production with
   its own verification. Blocked strictly on Malachy's go-ahead.
2. **T-02 — Fix the two live-broken production SEO artifacts** (GSC placeholder, stale
   description) as part of T-01's deploy, verified against the live HTML that exposed them.
3. **T-03 — Close the form field-parity gaps** (`source_campaign` unread, `malachy_service`
   missing from the JSON payload, unregistered settings options, and the two admin pages
   sharing one option group). Must check **every** caller first — the project's own rule.
4. **T-04 — Remove the live secrets from tracked files** (compose literals + the 64-hex form
   secret in `contact.js`) and rotate `IMAD_FORM_SECRET` with the WP/mail path in step.
5. **T-05 — Make the follow-up job real or delete it** — it is currently advertised to the
   owner as working and can never fire.
6. **T-06 — Establish one canonical release procedure** (production deploy method + a
   version/state check script) so "is production current?" is one command.
7. **T-07 — Repo hygiene + record consolidation** — move the 9 stray root docs into
   `docs/handover/`, de-duplicate the two context docs, retire the stale `build/` snapshot,
   resolve HO-051, and add `build/`-excluding guidance to any grep the team relies on.
8. **T-08 — Give the theme a real regression net** — a non-production-targeted Playwright
   profile (`VISUAL_BASE_URL` default) plus PHP linting in a script, so the "no automated
   check" gap closes without inventing a framework.

---

## 16. Unknowns and recorded conflicts

### Conflicts found (see digest `A-ho-inventory.md` for the full 19-item list)

| ID | Conflict | Assessment |
|---|---|---|
| C1 | `Imad-project-context.md` says "Theme repo … version currently 1.3.13 pending deploy"; reality is 1.3.21 on disk / 1.3.14 in production — **and the same doc tells the reader to check `MALACHY_THEME_VERSION`** | Doc is stale and self-refuting. `[V]` |
| C2 | `AGENTS.md` names "Active task: SEO Phase 0" (completed at `4ae6f3b`) and names `npm run test` as verification (stub, exit 1) | Instruction file is wrong on both counts. `[V]` |
| C3 | `AGENTS.md` vs `opencode.json` disagree on which docs auto-load; the onboarding doc calls one of AGENTS.md's named docs "superseded" | Two loaders, two answers. `[V]` |
| C4 | Model identity: this session runs `deepseek-v4.1-flash`; `hermes-orchestrator.md` says "Kimi K2.7 Code"; `opencode.json`/context say MiMo-V2.6-Pro/Flash; a filename still says "glm" | CONFLICTING EVIDENCE — unresolved, and material to §19 model routing in the orchestrator brief. |
| C5 | `HO-041` still reads `Status: OPEN` while `Imad-project-context.md` marks its item CLOSED with ancestor confirmation | The closing evidence exists; the doc was never edited. `[V]` |
| C6 | Production deploy method: `HO-039:155` `git pull` vs theme `README.md:20-23` zip/FTP | Two procedures, no canonical one. `[V]` |
| C7 | Honeypot/nonce field names documented at three different points (`malachy_hp` → `website`, then "both accepted") | Resolved in code by dual checks, not by convergence. `[V]` |
| C8 | Testimonial counts 6 / 4 / 0 / 9 across four documents; current seeder seeds 9 | Older docs stale. `[I]` |
| C9 | `HO-047 §2` claims the meta description was fixed "pending production deploy"; its own §5 errata and `HO-049 §2` say that was false for production | The **live probe agrees with the errata**: production still shows `Portfolio · 2026`. `[V]` |
| C10 | Two case-variant context docs; `HO-016` used twice; `HANDOVER.md` ≡ `Handover.md` | Structural duplication. `[V]` |
| C11 | August 2026: docs record work through 08-27, git records zero commits | UNCERTAIN — docs-only history for that month. `[V]` |

### Unknowns (cannot be determined without Malachy or VPS access)

- Whether production `wp_mail()` genuinely delivers to the inbox (HO-050 claims proof was
  obtained; I did not read the artifact that would confirm it).
- Current `leads`/`dead_letters` row counts and whether test rows remain (needs
  `docker exec … psql` on the VPS).
- Whether `malachy_service` actually reaches Telegram unlabelled in production traffic
  (code says yes; no live lead evidence).
- Which instruction source Malachy intends to be authoritative (C3/C4) — **this is a
  decision only he can make**.
- Whether the `openagile_postgres` shared-container arrangement is still as documented.
- The status of Google's index / GSC verification (blocked on his dashboard).

---

## 17. Evidence appendix (raw output, first-hand)

```
$ cd /home/zubbyik/wordpress_project && git rev-parse --show-toplevel; git branch --show-current
/home/zubbyik/wordpress_project
main
$ git remote -v
origin  git@github.com:tikeazubike-lab/zubbyik_press_imad.git (fetch/push)
$ git rev-list --count HEAD                       -> 87
$ git log --reverse --format='%h %ad %s' --date=short | head -1
a9c8639 2026-07-08 initial commit imadconsulting portfolio
$ git log -1 --format='%h %ad %s' --date=short    -> 828e852 2026-09-23 feat: hero role link styling + bump version 1.3.14
$ git rev-list --count origin/main..HEAD          -> 0     (nothing unpushed)
$ git worktree list                               -> /home/zubbyik/wordpress_project 828e852 [main]   (single worktree)
$ git ls-files | wc -l                            -> 130
$ git status --porcelain | wc -l                  -> 232   (19 modified, 213 untracked)
$ git log --date=format:'%Y-%m' --format='%ad' | sort | uniq -c
     22 2026-07
     65 2026-09
$ git show HEAD:malachy-portfolio/functions.php | grep -m1 MALACHY_THEME_VERSION
define( 'MALACHY_THEME_VERSION', '1.3.14' );
$ grep -m1 MALACHY_THEME_VERSION malachy-portfolio/functions.php
define( 'MALACHY_THEME_VERSION', '1.3.21' );
$ git diff --stat -- malachy-portfolio
 .../css/main.css | 500 ++++--   animations/projects.js | 551 ++++--   functions.php | 64 ++--
 header.php | 18 +-  inc/contact-handler.php | 8 +-  inc/data-seeder.php | 262 ++++--  inc/meta-boxes.php | 9 +-
 style.css | 4 +-  template-parts/section-projects.php | 232 +++---      (1404 insertions, 280 deletions)
```

Live probes (read-only GET, 2026-09-25):

```
https://imadconsulting.co.uk/           http=200 size=62772
  <meta name="google-site-verification" content="YOUR_VERIFICATION_TOKEN">
  <meta name="description" content="Portfolio · 2026">
  <title>IMAD Consulting — QA Engineer, Web Development &#038; IT Support Specialist…
  assets: ?ver=1.3.14
  form#discovery-form : malachy_nonce, action, malachy_name, malachy_email, malachy_message, malachy_hp
  form#contact-form   : malachy_nonce, action, malachy_service, website, malachy_name,
                        malachy_email, malachy_message, source_campaign
https://imadconsulting.co.uk/robots.txt  http=200  -> Sitemap: https://imadconsulting.co.uk/wp-sitemap.xml
https://imadconsult.zubbystudio.site/    http=200  assets: ?ver=1.3.21
https://api.imadconsulting.co.uk/openapi.json http=200  {"title":"imad-automation","version":"0.1.0",
  paths: /healthz, /api/leads, ...}
```

Validation commands (verbatim):

```
$ npm run test                                    -> "Error: no test specified"   exit 1
$ php -l (all 27 tracked theme .php files)        -> OK=27 FAIL=0  (php 8.3.6)
$ py_compile imad-automation/app/*.py             -> OK on 8 files
$ npx playwright test --list --config=...         -> Total: 54 tests in 2 files
$ bash -n malachy-portfolio/bin/*.sh              -> OK x3
$ docker compose -f docker-compose.yml config --quiet -> exit 0 (obsolete `version` warning only)
$ grep -n baseURL tests/visual/config/visual.config.ts:19
    baseURL: process.env.VISUAL_BASE_URL || 'https://imadconsulting.co.uk',
$ git status --porcelain | wc -l                  -> 232  (unchanged after every check above)
```

Secret checks (values never printed — length + sha256 prefix only):

```
$ contact.js hex literals: [(64, '96ef7cc2fbf5')]   .env IMAD_FORM_SECRET: len 64 sha '96ef7cc2fbf5'  EQUAL: True
$ git show HEAD:docker-compose.yml -> WORDPRESS_DB_PASSWORD len=48 hex=True (line 14)
                                      MYSQL_PASSWORD        len=48 hex=True (line 45)
                                      MYSQL_ROOT_PASSWORD   len=48 hex=True (line 46)
  and none of the three equals the password inside .env IMAD_DATABASE_URL (sha '246278399669')
$ git ls-files .env .env.imad -> 0 matches (correctly untracked; .gitignore:5,:16)
$ git check-ignore -v build/ -> .gitignore:7:build/     (build theme = MALACHY_THEME_VERSION '1.2.1', 0 tracked files)
```

Delegated read-only inventories (full digests on disk, not committed):

- `/home/zubbyik/.hermes/cache/scratch/hermes-discovery/A-ho-inventory.md` — 65 documents,
  per-doc entries + phases/open items/locked decisions/19 conflicts/hierarchy (~647 lines)
- `/home/zubbyik/.hermes/cache/scratch/hermes-discovery/B-code-map.md` — theme+backend map,
  7 forms with a field-parity table, dead-code inventory
- `/home/zubbyik/.hermes/cache/scratch/hermes-discovery/C-tooling-security.md` — build/test/
  deploy/security inventory with raw command output

**Corrections applied to delegated claims during verification** (kept visible on purpose):

- C digest said the committed DB passwords are "64-hex"; my own measurement is **48-char hex**.
- C digest said `php -l` passed on 43 files; my own run over the **27 tracked** theme PHP files
  gives 27 OK (the larger number likely included untracked/duplicate copies).
- `npm run test`'s exit code must be read without a pipe — piped through `tail`, the shell
  reports tail's status (0), not npm's (1).

---

**Application code modified: NONE.**

Files created by this investigation:

| File | Role |
|---|---|
| `docs/handover/HO-054-hermes-orchestrator-discovery.md` | **the reviewable record** — project handover format, self-contained and copy-paste ready for Claude Web |
| `.agent/investigation.md` | this file — supporting detail: every claim tagged `[V]`/`[I]`, raw-output appendix |
| `.agent/implementation-plan.md` | T-01…T-09 tasks, dependencies, acceptance criteria, blocked-on-Malachy list |
| `.agent/orchestration-design.md` | the proposed orchestration model, integrated with the existing handover process |

All four are documentation only, written to disk but **not committed** (project policy: nothing
is committed or deployed without Malachy's go-ahead — `HO-050:8`, `HO-053:71`). `.agent/` is
untracked; `docs/handover/HO-054-*.md` is untracked like most of the handover history in that
directory.

**Next step in the review chain** (it ends at Claude, not here): paste
`docs/handover/HO-054-hermes-orchestrator-discovery.md` into Claude Web for review. Because Claude
has no GitHub access, the handover carries its evidence inline rather than pointing at commits —
which is also why the three `.agent/` companions are supporting detail, never a substitute for it.
A unit of work is not closed until that review has come back and been acted on.
