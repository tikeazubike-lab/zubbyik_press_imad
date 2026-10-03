---
type: handover
project: IMAD Consulting — lead capture & content automation
title: "HO-074 — HO-073 rulings implemented: deterministic budget, excerpt caps, local-only gate, audit ring, disk caps, live deterministic_shadow"
date: 2026-10-03
from: Hermes Architect (MiMo-V2.6-Pro)
to: Reviewer (Claude Web) / Malachy
status: complete — deterministic_shadow is LIVE; `deterministic` active not enabled
priority: high
---

# HO-074 — HO-073 rulings implemented

Scope: the local-worker plugin (`~/.hermes/plugins/local-worker/`, v0.3.0 → **v0.4.0**).
All eight rulings are implemented. **Live mode is now `deterministic_shadow`** (§5), the gateway
was restarted, and real hook calls have written metrics lines. **Nothing is pushed.**

Tests: **66/66** plugin + **8/8** summariser, exit 0. Hermes source untouched (0 modified files).
Ollama never loaded (`ollama ps` empty throughout). `deterministic` (which replaces text) is NOT
enabled.

Two deviations from the letter of the rulings are disclosed in §9, and three real defects were
found by the rulings themselves (§3, §4, §6).

---

## §1 — Budget: `deterministic_budget_chars`

New key, default 24000, used **only** by the deterministic tiers; `max_input_chars` (12000) stays
the LLM tier's. The tier reads its own key — proven by `test_45`, which shows the same 15,899-char
payload going `lossless` at 24000 and `selection` at 12000 with `max_input_chars` untouched.

Effect on the real log that HO-072 reported as unreducible:

```
HO-072 (budget 12000):  docker logs malachy-wp --tail 3000  -> raw, skipped_too_large
HO-074 (budget 24000):  docker logs malachy-wp --tail 3000  -> 744,791 chars -> 18,397 (grouped)
                        raw 744791  baseline 50000  injected 18397  ratio 0.367340  savings 63.27%
```

That is §1 + §2 together: the budget let the grouped payload through, and §2's excerpt caps kept
it small enough to fit.

## §2 — Excerpt caps

Groups of 2+ cap `first:`/`last:` at 200 chars; single lines are verbatim up to 500; both use an
explicit `...[+N chars]` marker. A configured cap below **120 chars is raised to 120** — the cap
can never bite before the error class/message (`test_48` asserts the error text survives and the
floor is enforced). The grouped header states the policy verbatim: `excerpts are capped: 200
chars per grouped occurrence, 500 chars for single lines; "...[+N chars]" marks elided text.`
(`test_49`). Tests 46–49, RED first.

## §3 — Local-only gate — and the defect it exposed

Only `env_type == "local"` is processed; everything else passes through logged as
`skipped_non_local` (`test_50` covers modal/ssh/singularity/docker/None). Reading commands
(`cat`/`grep`/`tail`/`ls` on `~/.hermes/local-worker`) are never reduced (`test_52`), and
marker-bearing output is never reprocessed (`test_51`).

**Deviation-type defect found by this ruling — my bug, fixed and disclosed:** the plugin's
adapter (`__init__.py`) called the worker **without forwarding `env_type`**, so every live call
was mis-gated as non-local. Evidence: the first live run produced `skipped_non_local` for calls
the harness had made with `env_type="local"`. Fixed by forwarding `env_type` through both
adapters; a re-run through the real dispatcher then produced a `success` shadow record. The
adapter was shared by both seams and had been validated against the handler's defaults, not
against the dispatcher's kwargs — the repo's recurring "one caller only" trap.

**Open consequence needing a ruling:** `transform_tool_result` **does not pass `env_type`** —
core invokes it with `tool_name/args/result/**ids.hook_kwargs()/duration_ms/status/error_type/
error_message` (`model_tools.py:859`) and no environment field. With the gate as ruled, that seam
can therefore never process. Live evidence: every live CLI call lands on the tool_result seam
and logs `skipped_non_local` (10 of 17 shadow-mode lines). Either core should pass `env_type`, or
the gate should be `env_type in (None, "local")`. I implemented the ruling as written.

## §4 — `deterministic_shadow` disk behaviour

- **No raw handle per call** — shadow writes an audit sample instead (`test_53` asserts the raw
  dir stays empty).
- **Audit ring buffer** — max 30 samples, oldest evicted first, each sample = redacted raw +
  would-be injected text + the metrics line; dir 0700 / file 0600; 3-day retention (`test_54`,
  `test_55`).
- **Tree cap** — `~/.hermes/local-worker` capped at 100 MB, audit/raw evicted oldest-first and
  never the metrics log (`test_56`).
- **Metrics rotation** — `metrics.jsonl` rotates at 5 MB keeping 3 files (`test_57`), and
  `summarize_metrics.py` reads rotations oldest-first (`test_reads_rotated_metrics_files`).

**Test-hygiene defect found while verifying this §4 — fixed:** unit tests were writing into the
**live** metrics log and audit ring buffer. Two causes: (a) `_record_non_local`/`_deterministic`
fall back to `load_config()` when a test calls a handler without `cfg`, and (b) the base test
class never set `audit_dir`, so its shadow-mode test wrote audit samples to the live dir.
Fingerprints identified them exactly — the 10 polluted metrics lines all had `raw_chars == 25199`
(`_distinct_log(200,120)`) and the 4 polluted samples' raw began with the synthetic fixture
`172.18.0.1 - - "GET /browse/unique-page-`. Both test classes now set temp `audit_dir` +
`disk_cap_mb: 0` and patch `load_config` to the test config; the live files are md5-identical
across a full suite run. The polluted lines/samples were removed (backups:
`metrics.jsonl.pre-ho073-cleanup`, `samples.jsonl.pre-ho073-cleanup`).

## §5 — Live switch, gateway restart, real hook calls

```
$ grep -A12 '^local_worker:' ~/.hermes/config.yaml
local_worker:
  mode: deterministic_shadow
  llm_tier_enabled: false
  base_url: http://127.0.0.1:11434/v1
  model: qwen3:4b-8k
  ...
$ systemctl --user restart hermes-gateway   # active; Telegram reconnected
```

Real calls in live processes, mode `deterministic_shadow`:

1. A real one-shot Hermes process (`hermes -z`, real terminal tool, live config) wrote metrics
   lines — the tool_result seam, `skipped_non_local` per §3.
2. The real dispatcher with `env_type="local"` wrote a **success** line, proving the terminal
   seam works end-to-end through the plugin loader + `hermes_cli.lifecycle.invoke_hook`:
   `raw_chars 20000, injected_chars 20166` → the negative-saving case in §9.
3. The two real reducible logs, through the live config:

```
raw 186304  baseline 50000  injected 10850  savings 78.30%   tier_kind lossless  pipeline logs  model_calls 0
raw 744791  baseline 50000  injected 18397  savings 63.27%   tier_kind grouped   pipeline logs  model_calls 0
```

Note: a hook call *inside the gateway process itself* needs an inbound platform message, which
this environment has no way to produce; the gateway was restarted onto the new config and the
same code path is exercised above from a live process.

## §6 — `tests/audit_report.py` (read-only) — and a structural conflict

Read-only over the audit ring: lists samples and, per sample, the error-regex lines present in the
raw but absent from the would-be injection, plus the flagged count.

```
$ venv/bin/python tests/audit_report.py
audit samples : 5
  sample #1  ts=2026-10-03T08:16:43+00:00
    would-be injected: 18397 chars
    error lines present in raw but missing from the injection: 1497
FLAGGED SAMPLES: 2 of 5
```

**Finding: the ruled check cannot distinguish grouping from loss, so the §7 gate
"audit_report flags 0 samples" is unreachable while grouping is on.** Both flagged samples are
the grouped `malachy-wp` log, and all 1,497 "missing" lines are the *middle occurrences* of a
single signature that the group entry represents as `x1499, lines 1502-3000 | first: … | last: …`.
Nothing is lost — the signature is present, the count is exact — but grouping deliberately does
not print middle occurrences verbatim, so a line-level diff must flag it. The lossless-collapse
samples are not flagged only because duplicate collapse keeps an identical copy.

I prototyped a signature-aware companion measure (compare normalized error signatures, expanding
`first:`/`last:` excerpts and honouring the 120-char cap floor) and **reverted it rather than ship
it unverified** — its first run flagged 4 of 5 samples, i.e. it was mis-calibrated, and I would
rather report that than present a metric that looks clean without being right. Recommendation:
either (a) add a signature-aware measure as the gate's criterion (report both), or (b) exempt
grouped samples from the line-level gate, or (c) rule that the gate means "no signature lost" and
accept that the line-level number will be non-zero for grouped logs. Your call.

## §7 — Review criteria for `deterministic` active (NOT enabled)

Live shadow dataset (`mode=deterministic_shadow`, 17 calls):

```
eligible calls          17
applied (success)        6      -> would-apply rate 35.3%   (criterion >= 20%  PASS)
median saving applied    70.74%                              (criterion >= 40%  PASS)
p95 added duration       122 ms                              (criterion < 250 ms PASS)
fallback_error / exceptions in shadow mode   0               (criterion 0     PASS)
audit_report flags       2 of 5                              (criterion 0     FAIL — §6)
elapsed                  1 day                               (criterion >= 5 days NOT MET)
```

The dataset is far short of "≥ 5 days / ≥ 100 eligible calls", so the gate cannot be assessed yet
regardless. Run `summarize_metrics.py` + `audit_report.py` after the observation window; their
output is the review input.

## §8 — Commits (local only, NO push)

```
938ad16fcbc531a6657c8623e1d37a99eb297fc4  [plugin] HO-073 rulings implemented  (v0.4.0)
7d52ef18cdbb45acbb4e00d6386cf8c7e2d09e0c  [plugin] HO-071 rulings
4d5f2509bec67b9ac1de0520f4e323d92c1fec27  [plugin] 0.2.0
e512fdcc0b84a91bbafc66067834136b7fc590a1  [plugin] 0.1.0
```

`git status --porcelain` — plugin: **empty** (clean, no remote). Main repo: **clean** after this
handover; 5 ahead of origin before it; **nothing pushed**.
`git -C ~/.hermes/hermes-agent status --porcelain` → **0 lines**.

## §9 — Disclosed deviations

1. **`no_saving` guard (new, unruled).** Found live: a 20,000-char incompressible payload
   produced a 20,166-char injection — header overhead made an "applied" reduction *larger* than
   the raw text (savings −0.83%). Injecting a larger string is strictly worse, and it would
   corrupt the §7 "median saving on applied calls" metric, so a reduction that is not smaller
   than the raw text is now passed through with `status=no_saving`. `test_45b` pins it. Note this
   also means a `lossless` outcome that collapsed nothing is never injected (it would only add a
   header) — correct, but it does mean `lossless` is recorded for the tier decision while the
   call passes through.
2. **`tier_kind` clarified.** It now names the reduction tier (`lossless`/`grouped`/`selection`/
   `skipped_too_large`) with the source pipeline (`logs`/`testbuild`) carried in a new `pipeline`
   field; previously a selection was reported as `tier_kind: logs`. No test pinned the old value;
   it makes the §7 review data readable.

Also fixed en route: `_as_int` returns the *default* for a value below its minimum rather than
clamping up, so the 120-char excerpt floor had to be applied explicitly.

## §10 — Open questions

1. **§3** — should `transform_tool_result` receive `env_type`, or should the gate accept
   `None` as "unknown but local"? As ruled, the envelope seam is inert.
2. **§6** — how should the audit gate treat grouping (signature-aware measure, exempt grouped
   samples, or accept a non-zero line-level count)?
3. **§4** — the 100 MB tree cap evicts raw handles before 7-day retention can apply once the tree
   fills; acceptable, or should retention win?
4. **§9.1** — confirm the `no_saving` guard is acceptable as written.
