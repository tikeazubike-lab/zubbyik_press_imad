# .agent/implementation-plan.md — proposed roadmap

**Status**: PROPOSAL ONLY. This document is **not** permission to implement anything.
**Produced by**: Hermes (orchestrator), 2026-09-25, from `.agent/investigation.md`.
**Ground rule inherited from the project**: nothing is committed, pushed or deployed without
Malachy's explicit go-ahead (`HO-050:8`, `HO-050:161`, `HO-053:71` — verified verbatim).

**Task numbering**: `T-NN` here (orchestration scope). On execution each task becomes one
`HO-NNN` handover in `docs/handover/`, per the project's existing convention — HO-054 is the
discovery run already written; execution starts at **HO-055**. Every handover is written to be
self-contained and copy-paste ready for Claude Web (the review chain terminates there, and
Claude has no repo access). No task is executable until its dependencies are satisfied.

**Provenance tags**: `[V]` = verified first-hand this session; `[I]` = from a delegated
read-only inventory. Every task below is derived from evidence in the investigation — none is
invented, and no task proposes a new framework, migration or architecture.

---

## Phase 0 — Discovery / alignment (this run)

| ID | Objective | Status |
|---|---|---|
| T-00 | Produce `.agent/investigation.md`, this plan, and `.agent/orchestration-design.md`; modify no application code | **COMPLETE** — awaiting review |

**Exit criteria**: a reviewer (Claude Web, per the standard) can confirm from the three
documents what the project is, where it stands, and what would be safe to do next — without
re-reading 56 handovers. **Not satisfied by "the orchestrator said so":** every claim is
tagged with provenance and the raw output is reproduced in `investigation.md` §17.

**Deliberately out of scope in Phase 0**: any commit, any deploy, any `.agent/` scaffolding
beyond the three required documents (rationale in `orchestration-design.md`), and any change
to `AGENTS.md` / `opencode.json` / HERD / OpenCode configuration.

---

## Phase 1 — Immediate project work (unblock the release; fix what is live-broken)

### T-01 — Commit and release theme v1.3.15 → v1.3.21
- **Objective**: get the completed HO-049→HO-053 workstream off the local disk into reviewed
  commits, then deployed to production with its own verification.
- **Reason**: 1 404 insertions exist only on one machine; a disk failure loses the whole
  workstream. Production is 7 versions behind staging. `[V]`
- **Dependencies**: **Malachy's go-ahead** (hard gate). T-07 (record consolidation) is
  recommended *before* the commit, not a gate.
- **Affected area**: `malachy-portfolio/**` (working tree), `docs/handover/**`,
  `opencode.json`, `package.json`, `.hermes/plans/**`.
- **Acceptance criteria**: (a) reviewed commits on `main` covering the working tree, split by
  concern (theme release, SEO/meta, showcase, docs) rather than one mega-commit; (b)
  `git status` shows no unexplained modifications in the theme; (c)
  `MALACHY_THEME_VERSION` and `style.css` `Version:` agree; (d) production serves the new
  `?ver=` on every enqueued asset; (e) handover written.
- **Suggested worker**: OpenCode Implementer; **review before push** by Claude Web.
- **Model class**: Flash-class for the mechanical commit/deploy; Pro-class only if the diff
  needs judgement (e.g. deciding commit boundaries).
- **Test requirements**: `php -l` over all tracked theme PHP (must stay 0 failures);
  `bash -n` clean; a live `curl` of production assets *after* deploy.
- **Expected artifact**: HO-054 (release), plus a production verification block with raw
  `curl` output.

### T-02 — Kill the two live-broken production SEO artifacts
- **Objective**: production must stop emitting `google-site-verification` =
  `YOUR_VERIFICATION_TOKEN` and `description` = `Portfolio · 2026`.
- **Reason**: both are live **right now** on `imadconsulting.co.uk`; both are already fixed in
  the uncommitted working tree (`header.php`), so this is a deploy consequence of T-01 — but
  it must be *verified live*, not assumed. `[V]`
- **Dependencies**: T-01. GSC token *value* is Malachy's (T-10).
- **Affected area**: `malachy-portfolio/header.php`, `functions.php` (the `malachy_gsc_token`
  setting).
- **Acceptance criteria**: live HTML contains no `YOUR_VERIFICATION_TOKEN`; the
  `google-site-verification` tag is **absent** when the option is empty; the description
  equals the curated string (not `blogdescription`).
- **Suggested worker**: Implementer (verification step); model class: Flash.
- **Test requirements**: `curl -s https://imadconsulting.co.uk/ | grep` for both tags — raw
  output in the handover. No placeholder string anywhere in the rendered head.
- **Expected artifact**: verification section inside HO-054 (or its own short HO if the deploy
  is staged separately).

### T-03 — Close the form field-parity gaps (shared-code trap)
- **Objective**: converge the Contact form and Discovery form onto one field contract, and fix
  the mismatches found.
- **Reason**: this project has been burned **twice** by shared code validated against one
  caller (honeypot name, nonce name). Current state still relies on dual-name `??` fallbacks
  rather than agreement: `source_campaign` is posted and never read; `malachy_service` never
  reaches the JSON payload so Telegram leads are unlabelled; `malachy_phone` /
  `malachy_whatsapp` / `malachy_twitter` have no `register_setting` and are silently discarded;
  two admin pages share one option group so submitting one can blank the other's fields.
  `[V]` (handler read in full, `register_setting` calls counted) + `[I]`
- **Dependencies**: none technical; must follow the project's standing rule — **check every
  caller of every shared handler/template/JS file before editing**, including the stale
  `build/malachy-portfolio/` copy that still carries the old honeypot logic. `[V]`
- **Affected area**: `inc/contact-handler.php`, `template-parts/section-contact.php`,
  `template-parts/section-hero.php`, `assets/js/contact.js`, `functions.php`,
  `inc/meta-boxes.php`, and the `imad-automation` lead schema if `malachy_service` is to be
  captured.
- **Acceptance criteria**: one documented field table (form → JS → handler → backend) with
  every name matching or an explicit reason it does not; no unreferenced posted field;
  settings inputs either work or are removed; both forms verified on staging **and** production.
- **Suggested worker**: Pro-class (this is the class of bug that has beaten this project twice).
- **Test requirements**: submit each form on staging and confirm the handler's raw response
  plus the resulting Telegram message/email content; if a local WP harness exists, unit-test
  `malachy_process_contact()` directly with both field-name variants.
- **Expected artifact**: HO-055 with the full parity table and raw submission evidence.

---

## Phase 2 — Supporting work

### T-04 — Credential hygiene: un-hardcode the tracked DB passwords (the form secret is NOT a target)
- **Objective**: no live credential remains hardcoded in a tracked file — and the public-by-design
  bot-filter value is documented as such instead of being "fixed".
- **Reason**: **HO-055 §2.2 accepted.** The `IMAD_FORM_SECRET` in `contact.js` is a documented,
  deliberate design decision, not an exposure: `HO-026:129` ("Intentional per README design
  (bot filter, not auth)"), `HO-036:54-55` ("intentional by design… visible in page source"),
  `docs/manual/imad-automation-workflow.md:180` (listed as a knob). Rotating a value whose purpose
  is public visibility restores nothing. The real item is the **tracked DB passwords**: at HEAD,
  `docker-compose.yml:14/45/46` hold literal 48-char hex for `WORDPRESS_DB_PASSWORD` /
  `MYSQL_PASSWORD` / `MYSQL_ROOT_PASSWORD`, and the `41dd54e` diff shows those replaced the strings
  `wordpress_pass`/`root_pass` — so the **current** credential is committed, not only the retired
  weak one. The project's own current context doc already records this as an open decision
  (`Imad-project-context.md:101-104`); its stale twin contradicts it (`imad-project-context.md:96`).
  `[V]` + HO-055 §2.2
- **Dependencies**: a decision from the owner on history rewriting (the retired weak values stay in
  history), and **B-05** (whether the form secret stays public-by-design, and whether `/api/leads`
  needs rate-limiting or an origin check).
- **Affected area**: `docker-compose.yml`, the VPS `.env`, `docs/handover/HO-026` (reproduces values
  in prose); documentation of the form-secret design in the manual.
- **Acceptance criteria**: the three values are `${VAR}` refs — the same pattern
  `IMAD_DATABASE_URL` and `IMAD_FORM_SECRET` already use at HEAD lines 55/59; the DB container is
  recreated with rotated values and comes back healthy; `git grep` finds no literal password;
  the history-rewrite decision is recorded (yes/no + reason); the form secret is documented as
  public-by-design **with its residual exposure stated plainly** (anyone can POST `/api/leads`)
  and handled by B-05, not by rotation.
- **Suggested worker**: Implementer; independent verification required (Co-reviewer class).
- **Model class**: Flash-class; Pro-class only if the history decision is taken.
- **Test requirements**: `docker compose config` still resolves after the change; recreated
  container healthy (`docker ps`); `git grep -E 'PASSWORD: '` returns nothing; raw output of all
  three in the handover.
- **Expected artifact**: HO-057.

### T-05 — Make the follow-up reminder real, or delete it
- **Objective**: stop advertising a feature that cannot fire.
- **Reason**: `db.py:70` selects `WHERE status='contacted'` and **nothing in the codebase ever
  writes `leads.status`** (`[V]` — grep over `app/*.py` and migrations) — while
  `docs/manual/imad-automation-workflow.md` §4 tells the owner to mark leads contacted.
- **Dependencies**: the owner's decision — fix or delete (product call).
- **Affected area**: `imad-automation/app/{db.py,scheduler.py,notify.py,main.py}`, the workflow
  manual.
- **Acceptance criteria**: either (a) an endpoint/admin path sets `status`+`last_touch` and a
  demonstrated reminder fires for a dated test lead, or (b) scheduler + notify path deleted and
  the manual corrected. No third state.
- **Suggested worker**: Flash-class; Pro-class if the fix is designed rather than deleted.
- **Test requirements**: insert a test lead with an old `last_touch`, run the job, capture raw
  output + the Telegram message; or show the code path removed and `dead_letters` unaffected.
- **Expected artifact**: HO-057 + corrected manual section.

### T-06 — One canonical release procedure
- **Objective**: make "is production current, and what is the deploy?" a single command with a
  single documented answer.
- **Reason**: two competing methods exist in-repo (`HO-039:155` `git pull` vs theme
  `README.md:20-23` FTP/cPanel zip) `[V]`; production-vs-staging version checks were done by
  hand in this investigation and should not need to be.
- **Dependencies**: T-01 (needs one completed real deploy to document).
- **Affected area**: a new `scripts/` entry (read-only check) + `docs/manual/`.
- **Acceptance criteria**: one script prints local version, staging version, production version
  and the drift, exit code non-zero on drift; the deploy procedure documented once, with the
  competing method explicitly retired or annotated as historical.
- **Suggested worker**: Flash-class.
- **Test requirements**: run the script; raw output showing drift detected pre-deploy and clear
  post-deploy.
- **Expected artifact**: script + HO-058 + updated manual.

---

## Phase 3 — Testing / hardening

### T-07 — Repo hygiene and record consolidation
- **Objective**: make the record trustworthy and greppable.
- **Reason**: 213 untracked entries; 9 stray `.md` files at repo root including byte-identical
  `HANDOVER.md`/`Handover.md`; `HO-013`/`HO-014`/`HO-016-checkwebsite` outside the handover dir
  (so they escape any `HO-*.md` glob); two case-variant context docs; `HO-016` used twice;
  `HO-051` referenced but never written; empty `docs/adr/`; a stale `build/` snapshot whose
  pre-HO-041 honeypot code poisons repo-wide greps. `[V]`
- **Dependencies**: none. Recommended **before** T-01's commit so the commit carries a clean
  tree.
- **Affected area**: `docs/`, repo root, `.gitignore`, `build/` (retire).
- **Acceptance criteria**: one context doc (no case-variant twin); every `HO-*` discoverable
  under `docs/handover/`; `HO-051` resolved (written or explicitly voided); no duplicate
  handover files; `build/` deleted or clearly marked stale; a raw `git status` in the handover
  showing what remains untracked **and why**.
- **Suggested worker**: Flash-class.
- **Test requirements**: `git status` before/after; a glob count proving no handover is
  stranded outside `docs/handover/`.
- **Expected artifact**: HO-059.

### T-08 — Give the theme a real regression net (without inventing a framework)
- **Objective**: make "did that change break the front page?" answerable without screenshots of
  production.
- **Reason**: the only automated check today is a Playwright suite that **defaults to
  `https://imadconsulting.co.uk`** (`visual.config.ts:19`, `VISUAL_BASE_URL` unset and absent
  from the repo) and writes inside the repo; `npm run test` is a stub exiting 1 while
  `AGENTS.md` calls it the verification command. `[V]`
- **Dependencies**: T-06 (needs a known-good target), and staging reachable.
- **Affected area**: `tests/visual/config/`, `package.json` scripts, a small lint script.
- **Acceptance criteria**: a staging profile that cannot target production by accident (safe
  default or hard fail when the var is unset); screenshots written outside tracked dirs; a lint
  step that runs `php -l` + `bash -n` + `py_compile` and exits non-zero on failure; `npm run
  test` either does something real or is removed and `AGENTS.md` corrected.
- **Suggested worker**: Flash-class; Pro-class only if the safety mechanism needs design.
- **Test requirements**: run the suite against staging and capture raw output; prove the
  production guard fires when the var is unset; show the lint step failing on a deliberately
  broken file then passing.
- **Expected artifact**: HO-060 + updated `AGENTS.md` verification line.

---

## Phase 4 — Review / integration

### T-09 — Reviewer pass over T-01…T-08
- **Objective**: independent verification of every claim, per the project's existing standard —
  Claude Web reviews **every** handover without exception; ChatGPT co-reviews critical changes.
- **Reason**: the project's own history shows the reviewer catching a security regression, a
  Traefik credential limitation and a form that never worked. This plan's riskiest items
  (T-03, T-04) are exactly that class.
- **Dependencies**: T-01…T-08 complete.
- **Affected area**: n/a (review only).
- **Acceptance criteria**: every handover reviewed against raw evidence; any claim not backed by
  output flagged; shared-code callers independently checked.
- **Suggested worker**: Claude Web (handover text via copy-paste — no GitHub access) +
  ChatGPT as co-reviewer.
- **Test requirements**: n/a.
- **Expected artifact**: review notes appended to each HO, plus a consolidated sign-off.

---

## Blocked on Malachy (not tasks for an agent — do not guess, wait)

| ID | Item | Why it is his |
|---|---|---|
| B-01 | **Go-ahead to commit/push/deploy** | Explicit standing policy. Everything in Phase 1 is stonewalled behind it. `[V]` |
| B-02 | Google Search Console: token paste + "Request Indexing" | Dashboard access. The placeholder sitting in production is the visible symptom. `[V]` |
| B-03 | SEO Phase 1 decisions (SEO plugin choice, per-page meta) | Product/purchase decision. `[I]` |
| B-04 | Which instruction source is authoritative (`AGENTS.md` vs `opencode.json` vs onboarding doc) | Governance decision; resolving it wrong makes staleness worse. `[V]` |
| B-05 | Whether to keep the "form secret in public JS" design | Accepted-risk decision, already documented as intentional once (`HO-026:129`). `[I]` |
| B-06 | Production DB state: test rows in `leads`/`dead_letters`; production project reseed + experience dedupe | Requires VPS/DB access and a data-change decision. `[I]` |
| B-07 | Whether the daily-follow-up feature (T-05) is wanted at all | Product call. `[V]` |

## Explicitly NOT planned (recorded permanent non-goals)

No n8n · no WhatsApp Status automation (official or unofficial) · no AI lead scoring ·
no paid SaaS · no second Postgres instance · no framework migration · no new orchestration
framework · no parallel worktrees while only one `main` exists and drift is the top risk.
`[V]`/`[I]` (locked decisions).

---

## How this plan stays honest

1. Every task names the evidence that justifies it, and every task has an acceptance criterion
   that a reviewer can check from raw output.
2. Nothing here requires changing the project's architecture, schema, framework or hosting.
3. The plan's critical path is **one human decision** (B-01), not more engineering. That is
   stated plainly rather than hidden behind a parallel workstream.
