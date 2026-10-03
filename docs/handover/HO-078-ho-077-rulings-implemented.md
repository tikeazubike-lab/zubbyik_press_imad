---
type: handover
project: IMAD Consulting — Hermes local-worker
title: HO-077 rulings implemented (call identity, ruled eligibility, default env, normalizer disclosure, dev isolation, void epoch)
date: 2026-10-03
from: Implementer (MiMo-V2.6-Flash)
to: Architect review (Claude/Opus) via Malachy
status: complete — awaiting clearance before the epoch starts
priority: high
---

# HO-078 — HO-077 rulings implemented

## 0. Summary

All nine HO-077 items are implemented in `~/.hermes/plugins/local-worker` (v0.6.0).
**98/98 tests pass** (`python -m unittest discover -s tests -t tests -v`; 90 worker + 8 summariser),
RED first in every case. The 13:07 epoch is **voided** and the live metrics log is empty.

Four of the nine items turned up a real defect that only live data exposed — summarised here,
detailed in the item sections:

1. **§4 double counting was real**: one terminal call writes **two** rows and nothing named the
   seam. Rows now carry `seam` + `call_key`; the summariser counts per call.
2. **§8 dev leak was real**: the demo's final `invoke_hook` resolves the plugin manager's **own**
   copy of worker.py, so the demo wrote a row to the live log. Fixed with env overrides honoured
   wherever config is normalised — the md5 proof now passes.
3. **§2 collision triage**: my first token classifier labelled 4 real live collisions `suspect`
   that are benign (the differing token was a bracketed date). Tightened → live verdicts are now
   `benign: 16, suspect: 0`.
4. **§6**: `env_source=default` is only reachable when nothing is configured anywhere; on this host
   the gateway bridges the backend into the process env, so the gateway resolves via `config`.

Commits are local; nothing is pushed.

## 1. Evidence resubmission

**RED — per-test, name + exception type.** Tests 60–77 were written against the pre-HO-075 worker
(`HEAD~1`); tests 78–84 against the HO-075 commit (`HEAD`). Each row is a real run of the current
test file against that revision:

```
--- against HEAD~1 (pre-HO-075) ---
  test_60_grouped_sample_loses_no_signature:          ERROR: KeyError
  test_60b_collapsed_lines_are_not_counted_as_lost:   ERROR: KeyError
  test_61_dropped_signature_counts_exactly_one:       ERROR: KeyError
  test_62_collisions_are_reported_not_merged:         ERROR: KeyError
  test_63_group_counts_match_the_raw_counts:          ERROR: KeyError
  test_64_the_gate_is_zero_signature_loss:            FAIL:  AssertionError
  test_66_env_resolution_prefers_the_kwarg:           ERROR: AttributeError
  test_67_unresolvable_env_fails_closed:              ERROR: AttributeError
  test_68_tool_result_seam_resolves_the_backend…:     ERROR: AttributeError
  test_70_audit_raw_is_capped_per_sample:             ERROR: AttributeError
  test_71_cap_eviction_is_logged_as_evicted_early:    ERROR: FileNotFoundError
  test_72_audit_report_prints_the_oldest_sample_age:  FAIL:  AssertionError
  test_73_no_saving_compares_against_baseline:        FAIL:  AssertionError
  test_75_eligibility_is_explicit_and_narrow:         ERROR: FileNotFoundError
  test_76_shadow_returns_none_on_both_seams:          ERROR: AttributeError
  test_77_metrics_epoch_archives_outside_…:           ERROR: AttributeError
  test_78_absent_key_is_a_default_not_a_resolution:   ERROR: AttributeError
  test_80_collisions_are_classified_benign_or_suspect: ERROR: KeyError
  test_83_worker_rev_is_recorded_and_mixed_revs…:     ERROR: AttributeError
  test_84_ruled_eligibility_does_not_include_…:       ERROR: FileNotFoundError
  RED total: run=24 failures=3 errors=17

--- against HEAD (HO-075) + the pre-HO-077 summariser (tests 81/82) ---
  test_68_tool_result_seam_resolves_the_backend…:     FAIL:  AssertionError
  test_77_metrics_epoch_archives_outside_…:           ERROR: ModuleNotFoundError
  test_78_absent_key_is_a_default_not_a_resolution:   FAIL:  AssertionError
  test_80_collisions_are_classified_benign_or_suspect: ERROR: KeyError
  test_81_one_call_hitting_both_seams_counts_once:    ERROR: KeyError
  test_82_dev_rows_are_excluded_from_the_counts:      FAIL:  AssertionError
  test_83_worker_rev_is_recorded_and_mixed_revs…:     ERROR: AttributeError
  test_84_ruled_eligibility_does_not_include_…:       FAIL:  AssertionError
```

**GREEN — `python -m unittest -v`** (`--discover -s tests -t tests`, the tests dir is not a
package so this is the form that collects both suites):

```
Ran 98 tests in 3.741s

OK
```

**`pytest -v` is not available** — pytest is absent from the Hermes venv (established HO-068), so
the suites are stdlib `unittest`; the `-v` tail above is the equivalent evidence.

**Repos**

| repo | `git status --porcelain` | note |
|---|---|---|
| plugin `~/.hermes/plugins/local-worker` | 6 modified (worker.py, plugin.yaml, README.md, 3 test files) | committed in §9 below |
| main `~/wordpress_project` | `?? docs/handover/HO-077.md` | committed with this report |
| `~/.hermes/hermes-agent` | *(empty — 0 lines)* | **core untouched** |

main-repo SHA before this document: `96abf691753b63991023f5a20214ec18a5943f84`.

**Constants (file:line, `worker.py`)**

| constant | value | line |
|---|---|---|
| `DEFAULTS["deterministic_budget_chars"]` | 24000 | 64 |
| `GROUP_EXCERPT_CHARS` | 200 | 536 |
| `SINGLE_LINE_EXCERPT_CHARS` | 500 | 537 |
| `MIN_EXCERPT_CHARS` | 120 | 538 |

Diffstat of this submission: `worker.py +288`, `tests/test_worker.py +163`,
`tests/summarize_metrics.py +92`, demo `+19`, README `+18`.

## 2. §2 — secret scan, names/counts only

**`ls -la ~/.hermes/config.yaml*` — one was NOT 0600:**

```
-rw------- config.yaml                          (0600)
-rw-r----- config.yaml.bak-20260925T112817Z     (0640) ◀ group-readable — FIXED to 0600
-rw------- config.yaml.bak-20261003-060126      (0600)
-rw------- config.yaml.bak-20261003-094103      (0600)
-rw------- config.yaml.bak-20261003-101304      (0600)
```

That backup predates this work (Aug 10) and was group-readable; I chmod'd it to 0600. Disclosed
as a change, not a silent fix.

**Scan of `config.yaml*` + all of `~/.hermes/local-worker/` (samples, metrics, epochs, raw) —
202 files:**

```
config.yaml                          : assigned secret x2
config.yaml.bak-20260925T112817Z     : assigned secret x1
config.yaml.bak-20261003-060126      : assigned secret x2
config.yaml.bak-20261003-094103      : assigned secret x2
config.yaml.bak-20261003-101304      : assigned secret x2
TOTAL HITS: 9
```

All 9 are the same shape and **none is a literal secret** — every one is an environment
indirection (masked value shown, key name given):

```
config.yaml: assigned secret x2  first=key=api_key value=${OPEN…KEY}
config.yaml.bak-20260925T112817Z: assigned secret x1  first=key=api_key value=${GOOG…KEY}
```

**Zero hits anywhere under `~/.hermes/local-worker/`** (11 audit samples, the epoch files, the
live log, 193 raw handles) — the redaction the worker applies before storing is holding.

## 3. §3 — item D: the glob, and the stray artifact is gone

The rotation lookup (`tests/summarize_metrics.py:43`):

```python
for index in range(9, 0, -1):                       # oldest rotation first
    candidate = path.with_suffix(path.suffix + f".{index}")
    if candidate.exists():
        rotated.append(candidate)
```

Only `metrics.jsonl.<1..9>` is ever read. `metrics.jsonl.pre-ho073-cleanup` (10,131 B) — which I
left in the plugin tree during the HO-073 cleanup — is now moved **out** of both the plugin and
the rotation namespace:

```
$ ls -la ~/.hermes/local-worker-archive/
drwx------  2 zubbyik zubbyik 4096  .
-rw-------  1 zubbyik zubbyik 10131 metrics.jsonl.pre-ho073-cleanup

$ ls -la ~/.hermes/local-worker/
drwx------  audit  epochs  raw
-rw-------  1079  metrics.jsonl          ← now empty (epoch voided in §9)

$ ls -la ~/.hermes/local-worker/epochs/
-rw------- 11651 metrics-epoch-epoch-0-pre-ho075.jsonl
-rw-------  3949 metrics-epoch-VOID-20261003T1307Z-ho075-start.jsonl
```

Verified unreachable by the lookup: `rotation candidates` → all 9 absent; the archive dir is
0700 and sits outside the plugin directory entirely.

## 4. §4 — one call, two rows: identity added

**Before (raw, live):** one terminal call produced two rows with nothing naming the seam:

```
row 0: raw_chars=49913 env_source=kwarg    (seam: absent)
row 1: raw_chars=49910 env_source=config   (seam: absent)
```

**After (raw, live — one `hermes -z` call):**

```
7 live rows; 3 with the resolved value
  ts=2026-10-03T11:43:12 seam=terminal_output call_key=h:1df4d477ab450d55 env_source=kwarg  resolved=local source=live rev=dc6b4f6fb828 status=no_saving
  ts=2026-10-03T11:43:26 seam=terminal_output call_key=h:b93bb1ee2e76cd9c env_source=kwarg  resolved=local source=live rev=dc6b4f6fb828 status=success
  ts=2026-10-03T11:43:26 seam=tool_result     call_key=h:b93bb1ee2e76cd9c env_source=config resolved=local source=live rev=dc6b4f6fb828 status=success
```

Two rows, **one `call_key`**, two seams — so yes: a terminal call yields 2 rows today, and `seam`
is the field that names which hook wrote each one (`terminal_output` = pre-cap output hook,
`tool_result` = post-cap envelope hook).

`call_key` uses **core's own ids** where they exist: `transform_terminal_output` is invoked with
`tool_call_id` (`tools/terminal_tool_result.py:137`) and `transform_tool_result` with
`_CallIds.hook_kwargs()` = task/session/tool_call/turn/api_request ids (`model_tools.py:859`).
When no id is present the key falls back to `hash(session, command, 10s bucket)` as ruled.

`summarize_metrics.py` now groups rows by `call_key`; a call's **canonical row** is its
`tool_result` row (the post-cap text is what actually enters context), it is eligible/applied if
that row is, and its latency is the **sum across seams**. Real data, the voided epoch:

```
  rows / calls       : 6 rows -> 4 calls (seams: terminal_output=2, tool_result=2, unknown=2)
  would-apply        : vs ruled eligible 100.0% | vs allowlisted 100.0% (informational)
```

(The two `unknown` are the pre-change legacy rows; they carry no `call_key` and therefore stand
alone — disclosed, and moot once the epoch starts fresh.) Test: `test_81`.

## 5. §5 — the ruled eligible denominator

Eligibility is now the ruled definition only:

```
eligible = passed the env gate + terminal tool + not marker-bearing + not read-of-own-path
```

The allowlist is **not** part of it. A terminal command outside it is logged `eligible=True,
allowlisted=False, status=skipped_not_allowed`, and the summariser prints all three numbers:

```
  would-apply        : vs ruled eligible 100.0% | vs allowlisted 100.0% (informational)
  not_allowed share  : 0.0% of terminal calls
```

Tests: `test_84` (a non-allowlisted terminal command is ruled-eligible with `allowlisted=False`),
`test_75`. This reverses my HO-076 §7 interpretation 1, as directed.

## 6. §6 — `env_source` gains `default`

Four values now: `kwarg` | `config` | `default` | `unresolved`. `default` means nothing was
configured anywhere and the documented default was used — an absent key is not a resolution.
Every row also records `env_type_resolved` so the value is visible, not just its provenance.

**Gateway vs CLI, same resolver** (finding C):

```
CLI: TERMINAL_ENV='local'
  CLI resolver -> ('local', 'config')
gateway (MainPID 1876592) environ: TERMINAL_ENV count=0   ← not set in the unit's environment
gateway live row -> env_source=config resolved=local       ← yet it resolves via config at call time
```

So on this host the gateway still resolves through `config`: core bridges the configured backend
into the process environment for the call (the unit's own environ is empty). `default` is
therefore only reachable when nothing is configured at all — which is what `test_78` pins
(`TERMINAL_ENV` unset + no `terminal.backend` → `('local', 'default')`; env set → `config`).

While verifying this I found that importing core's resolver directly
(`tui_gateway.session_workdir._effective_terminal_backend`) raises `NameError: name 'os' is not
defined`, because that module gets its globals injected by the gateway's `bind_module` rather than
importing `os`. It works in-process and fails standalone. **Not a core bug and not patched** — it
is why my resolver treats that function as advisory, wrapped in `try/except`, and resolves the
chain itself.

## 7. §7 — normalizer disclosure and collision triage

**Exactly which token classes are replaced** (all in `_TS_PATTERNS`, `_PID_PATTERNS`, `_IPV4_RE`,
`_IPV6_RE`, `_UUID_RE`, `_HEX_RE`):

| class | replaced forms |
|---|---|
| timestamp | ISO `2026-10-03T11:43:26` / `2026-10-03 11:43:26`, apache `29/Sep/2026:04:39:15`, syslog `Sat Oct 03 06:26:04`, with optional fractional seconds |
| pid | `pid=1234`, `pid:1234`, `[pid 1234]`, `[pid 1234:tid 1234]` |
| ip | IPv4 (`10.0.0.1`, with optional `:port`), IPv6 |
| uuid | 8-4-4-4-12 |
| hex | `[0-9a-fA-F]{8,}`, optional `0x` prefix |

**Never normalized** — and this is enforced by tests, not by convention: HTTP status codes, exit
codes, ports, line numbers, and every other number. `test_79` proves six lines differing only in
status/exit-code/port land in **six separate groups**, each value still visible in the render.
The existing grouping tests (`test_37`, `test_40`) continue to pin distinct messages and error
lines.

**`audit_report` collision triage** — for each collision it now prints both variants, the
differing class, and a verdict; `scope` states the measure's boundary:

```
collision verdicts: {'benign': 16, 'suspect': 0}
scope: error-regex lines only | gate: {'passed': True, 'signatures_lost': 0, ...}
  benign   class=timestamp  variants=2 sig=[<TS> +0000] [53] [ERROR] Error handling request /api/v1/.en
```

`benign` = the variants differ only inside a replaced class (same message at a different
timestamp/pid/IP). `suspect` = they differ in text the normalizer was never meant to touch —
typically beyond the 120-char signature window — and is reported loudly rather than merged
silently. My first classifier called 4 live collisions `suspect` because the differing token was
a bracketed date (`[2026-09-29` vs `[2026-10-02`); brackets are now stripped and dates recognised,
so those 4 are correctly benign. Tests: `test_80` (benign + suspect fixtures), `test_62`, `test_63`.

## 8. §8 — dev traffic never touches the live state

Two layers: the demo points its own cfg at a temp root, **and** sets env overrides the plugin
honours wherever config is normalised — necessary because the plugin manager loads its **own**
copy of `worker.py`, so patching an import in the demo does not reach it.

Found live: the demo's final `invoke_hook` dispatch wrote a `no_saving` row (raw 20000) into the
**live** metrics log through the manager's instance. Both leaked rows were removed with backups
kept in `~/.hermes/local-worker-archive/`.

**md5 proof (before/after a full demo run):**

```
BEFORE  metrics=2a497684d1981d16266f1ddee79ad582 audit=82ff0735e46799fea910c0ef7658661f raw=193
AFTER   metrics=2a497684d1981d16266f1ddee79ad582 audit=82ff0735e46799fea910c0ef7658661f raw=193
§8 PROOF: live state byte-identical across the demo run
demo temp root -> 2 rows; sources: ['dev']
```

`summarize_metrics` excludes `source=dev` rows from every count and reports
`dev_rows_excluded` (test: `test_82`).

A second, independent datapoint later completed for the bare `discovery` stage (which makes no
write calls at all — verified: the stage contains no `process_text`/`invoke_hook`/`record_metrics`
call):

```
live metrics md5 BEFORE: 63342eeea6302b7e0e50310c77e3dd52
live metrics md5 AFTER : 63342eeea6302b7e0e50310c77e3dd52
live audit   md5 BEFORE: 5c877dc20a51a46597e518765fa408ec
live audit   md5 AFTER : 5c877dc20a51a46597e518765fa408ec
live raw files BEFORE  : 193
live raw files AFTER   : 193
PROOF: live state UNCHANGED by the demo
```

## 9. §9 — the 13:07 epoch is voided

```
voided -> /home/zubbyik/.hermes/local-worker/epochs/metrics-epoch-VOID-20261003T1307Z-ho075-start.jsonl
live log exists after void: False
```

The clock has **not** started. It starts when you clear items 1–8 and I archive again.

The restart rule is now mechanical rather than a promise: every row carries `worker_rev`
(sha256 of `worker.py`, 12 hex) and the summariser reports the epoch window plus any revision it
has overtaken. Real output from the voided epoch, which contained two revisions:

```
epoch : 2026-10-03T11:40:48+00:00 .. 2026-10-03T11:43:26+00:00
worker revs : ['330a175de0b3', 'dc6b4f6fb828']  OVERTAKEN: ['330a175de0b3'] (epoch must restart)
```

This submission's `worker.py` revision is **`f87b33877314`** — the epoch will start at that
revision. A later behavioural change to `worker.py` changes the rev and the summariser will say so;
changes to the summariser or this report do not.

## 10. Deviations, observations, limits

- **Observation (harness), corrected:** `integration_shadow_demo.py` with no stage argument runs
  `discovery` only. I initially reported that the process "does not exit" — that was wrong, and I
  am correcting it rather than leaving it standing: the run completed normally with **exit 0** and
  printed its own md5 proof. It simply takes minutes between the last printed line and process
  exit (the plugin-manager load outlives the printing). My attempt to stop it with
  `pkill -f integration_shadow_demo` matched its own shell command string and SIGTERM'd **my
  shell**, not the demo — the same `-f` self-match trap I hit once before. The demo made **no
  model call**; `ollama ps` was `{"models":[]}` before, during and after.
- **Observation (core, not patched):** `_effective_terminal_backend` relies on `bind_module`
  injection (see §6).
- **Interpretation:** legacy rows written before this change carry no `seam`/`call_key` and stand
  alone in the per-call aggregation; they are confined to the voided epoch.
- **Unchanged and still true:** the LLM tiers cannot run here (`ollama.service` cgroup cap
  3.0 GiB vs the model's ~4.6 GB) — no model was loaded during this work; `deterministic` active
  remains disabled; the `ghost-showcase` crash loop and the WP_DEBUG double-define warnings are
  untouched and out of scope.
- **Not assessable yet:** the §7 review criteria (≥5 days, ≥100 calls, p95 < 250 ms, …). The epoch
  starts at zero calls.

## 11. Commits

Local only, nothing pushed. Plugin: v0.6.0. Full SHAs and `git status --porcelain` for both repos
are reported in the accompanying message.
