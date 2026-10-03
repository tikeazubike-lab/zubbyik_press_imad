---
type: handover
project: IMAD Consulting — Hermes local-worker
title: HO-075 rulings implemented (env resolution, signature audit gate, audit caps, eligibility + epoch)
date: 2026-10-03
from: Implementer (MiMo-V2.6-Flash)
to: Architect review (Claude/Opus) via Malachy
status: complete — awaiting review
priority: high
---

# HO-076 — HO-075 rulings implemented

## 0. Summary

All six HO-075 rulings are implemented in `~/.hermes/plugins/local-worker` (v0.5.0).
RED first (17 new tests: **3 failures + 13 errors** against the pre-HO-075 worker), then GREEN
(**83/83 plugin + 8/8 summariser**). Live mode stays `deterministic_shadow`; the LLM tiers stay
off; `deterministic` active is **not** enabled.

Two of the six rulings produced a real defect fix that only live data exposed:

1. **The audit gate was miscalibrated exactly as feared** — and the cause was my own ordering
   bug: clipping a line to 120 chars **before** normalizing moves the clip boundary whenever the
   normalized parts differ in length (variable-length IPs/pids), so one grouped signature looked
   like several. Normalize-then-clip → **0 signatures lost** on the same samples.
2. **A second false-positive class**: the lossless collapse appends `  [xN]`, which the measure
   read as missing text — 14 false "losses" on the real frappe sample. Markers are now stripped.

Commits are local; nothing is pushed.

## 1. §1 — `env_type` on the tool_result seam

**Mechanism** (`resolve_env_type()` in `worker.py`), resolved at call time, never a bare `None`:

| order | source | label |
|---|---|---|
| 1 | the seam's own kwarg (the terminal seam gets it from core) | `kwarg` |
| 2 | core's own resolver `tui_gateway.session_workdir._effective_terminal_backend()` | `config` |
| 3 | `TERMINAL_ENV`, then `terminal.backend` in `config.yaml`, then core's documented default (`local`) | `config` |
| 4 | config unreadable / resolver failure | → `(None, unresolved)` → **fail closed** `skipped_unknown_env` |

- **Core is not patched.** The plugin only *imports* core's resolver read-only; if that import
  fails it falls back to the documented chain, so it degrades rather than breaks.
- **The seam is restricted to the `terminal` tool**: `if str(tool_name).strip() != "terminal":
  return None` — every other tool is silent passthrough.
- `env_source` and the resolved value are recorded on every metrics row.

**Raw evidence — one live `hermes -z` call, two seams, both now processing:**

```
terminal_output | status=success | eligible=True | env_source=kwarg  | raw=49913 inj=8030 | lossless
terminal_output | status=success | eligible=True | env_source=config | raw=49910 inj=8138 | lossless
```

Before this ruling the second row was permanently `skipped_non_local` (core supplies no kwarg on
that seam). The trade-off you accepted is visible and auditable: `env_source` shows which calls
relied on the config resolution.

**Tests:** `test_66` (kwarg wins), `test_67` (unresolvable → `skipped_unknown_env`, fail closed),
`test_68` (backend docker/ssh/modal → passthrough; local → processes; `env_source=config`),
`test_69` (non-terminal tool → passthrough).

## 2. §2 — the audit gate is "0 signatures lost"

`audit_report()` now returns `gate: {passed, signatures_lost, definition}`. Per sample it reports
`signatures_lost`, `signature_flagged_samples`, `collisions`, `count_mismatches`,
`samples_detail` (incl. `raw_truncated`), `oldest_sample_age_hours`, and the retained
informational line-level `flagged` count.

**Live result on the existing samples:**

```
audit samples : 5
oldest sample : 2.83 hours
GATE (0 signatures lost): PASS  signatures_lost=0
line-level flags (informational): 2
collisions: 16   count mismatches: 0
```

The line-level count is unchanged (2) and stays in the report as information, exactly as ruled.

**The 4-of-5 miscalibration is now the RED case** (`test_60b`, `test_60`): the measure is
fixture-backed before use:

| fixture | requirement | test |
|---|---|---|
| (i) grouped sample → 0 flagged | grouped output must not read as loss | `test_60` |
| (ii) one distinct signature dropped → exactly 1 | real losses are caught | `test_61` |
| (iii) two errors differing only in normalized parts → collision reported | never merge silently | `test_62` |
| (iv) group's `xN` equals the true raw count | counts verified against raw | `test_63` |
| live RED case: collapsed lines (`[xN]`) are not losses | found on the real frappe sample | `test_60b` |

Note on reading `collisions`: it counts signatures with more than one verbatim variant, so
"1 signature / 1499 variants" for the WP_DEBUG warning is the expected shape (same message,
different timestamp/pid), not a defect. `count_mismatches: 0` confirms the group counts are exact.

## 3. §3 — the cap wins over retention

- The 100 MB tree cap evicts regardless of age and now **logs an event**: a metrics row with
  `status=evicted_early`, `evicted_path`, `evicted_bytes`, `disk_cap_mb`, `dir_size_mib`
  (`test_71`). Retention stays a max age, never a floor.
- Redacted raw is capped per sample at `AUDIT_RAW_MAX_BYTES` (2 MB) with an explicit
  `...[+N chars]` marker and a `raw_truncated` flag; `audit_report` marks truncated samples and
  prints `oldest_sample_age_hours` (`test_70`, `test_72`).

## 4. §4 — `no_saving` is measured against `baseline_chars`

The guard now compares the injection against `baseline_chars` (= `min(raw, 50 000)`), not the raw
size, so a reduction that does not beat what the output cap already gives is pointless and passes
through. Ruled case tested (`test_73`: budget raised to 60 000 so the reduction fits, injection
≈55 k against a 50 k baseline → `action=raw`, `status=no_saving`).

`no_saving` counts as **eligible and not applied**; `summarize_metrics.py` reports it separately
(`no_saving_rate`) alongside `would_apply_rate` (`test_74`).

## 5. §5 — eligible, and the new epoch

**eligible** (recorded explicitly on every row as `eligible: true|false`; the summariser prefers
the flag over status inference):

```
eligible = passed the env gate + terminal tool + not marker-bearing + not read-of-own-path
```

Plus the HO-067 F allowlist, which is the scope boundary — disclosed as an interpretation in §7.

**Epoch.** `archive_metrics_epoch()` moved the old log outside the rotation glob:

```
archived -> /home/zubbyik/.hermes/local-worker/epochs/metrics-epoch-epoch-0-pre-ho075.jsonl
live metrics exists after epoch bump? no (clean start)
```

`epochs/metrics-epoch-*.jsonl` can never be matched by the `metrics.jsonl.<n>` rotation lookup.
**The 5-day / 100-call review clock starts at 13:07 UTC 2026-10-03** (`test_77` covers the archive).

## 6. §6 — evidence

**`pytest -v` is not available**: pytest is absent from the Hermes venv (established HO-068), so
the suites are stdlib `unittest` and the equivalent `-v` tail is quoted instead.

**GREEN**

```
Ran 83 tests in 3.555s

OK
Ran 8 tests in 0.010s

OK
```

**RED** — the new tests run against the pre-HO-075 worker (`git show HEAD~1:worker.py`):

```
FAIL: test_73_no_saving_compares_against_baseline
AssertionError: 'raw' != 'replace'
FAIL: test_72_audit_report_prints_the_oldest_sample_age
AssertionError: 'oldest_sample_age_hours' not found in {'samples': 1, 'flagged': 0, 'flagged_samples': []}
...
Ran 17 tests in 0.170s

FAILED (failures=3, errors=13)
RED (pre-HO-075 worker, new tests): run=17 failures=3 errors=13
```

**Named shadow test** (`test_76_shadow_returns_none_on_both_seams`):

```
§6 — in shadow mode neither seam may replace the text, on the live config. ... ok
Ran 1 test in 0.040s
OK
```

**Full `local_worker` block (`~/.hermes/config.yaml`)**

```yaml
local_worker:
  mode: deterministic_shadow
  # §2 (HO-071): shadow/active require this to be true. Left false — the LLM tier is
  # shelved because ollama.service's cgroup cap (memory.max = 3.0 GiB) is below the
  # ~4.6 GB the model needs. The plugin refuses the tier regardless and behaves as off.
  llm_tier_enabled: false
  base_url: http://127.0.0.1:11434/v1
  model: qwen3:4b-8k
  bypass_chars: 6000
  max_input_chars: 12000
  active_timeout_s: 45
  request_timeout_s: 120
  memory_floor_mib: 4608
  raw_dir: ~/.hermes/local-worker/raw
  raw_retention_days: 7
  metrics_log: ~/.hermes/local-worker/metrics.jsonl
```

**Secret scan — 9 files scanned, 2 matches, 0 real secrets.** Files: `README.md`, `__init__.py`,
`plugin.yaml`, `worker.py`, `tests/audit_report.py`, `tests/integration_shadow_demo.py`,
`tests/summarize_metrics.py`, `tests/test_summarize_metrics.py`, `tests/test_worker.py`.
The two matches are deliberate test doubles — `api_key="not-a-real-key"`
(`integration_shadow_demo.py:208`, a stub pointing at a dead port) and generated truncation
fixtures (`test_worker.py:225,347`). No credential material is present.

**Summariser over the new epoch (live rows):**

```
baseline_chars_sum : 99823
injected_chars_sum : 16168
savings            : 83.8%
review numbers (HO-075 §7)
  applied calls      : 2 (would-apply 100.0% of eligible)
  no_saving          : 0 (0.0% of eligible)
  env_source         : config=1, kwarg=1
  evicted_early      : 0 (of 0 eviction events)
```

**Commits** (local only, nothing pushed):

```
eb0a5b1768e1a44b9baf1f4b34272614e2007e12  [plugin] HO-075 rulings (v0.5.0)
```

`git -C ~/.hermes/plugins/local-worker status --porcelain` → **empty**.

## 7. Deviations and interpretations (stated, not silent)

1. **The allowlist is part of the eligible definition.** §5 lists four tests; a command outside
   the HO-067 F allowlist can never be processed, so counting it eligible would inflate the
   denominator and corrupt the would-apply rate. I kept the four ruled tests as the gate and treat
   the allowlist as the scope boundary, and disclose it here. Non-allowlisted terminal calls are
   logged as ineligible rows (`skipped_not_allowed`) so you can recompute either way.
2. **Non-terminal tool calls are silent passthrough.** With the seam restricted to `terminal`,
   logging an ineligible row for every `read_file`/`search_files`/`patch` result would flood the
   log with entries that can never be eligible. They are simply never counted.
3. **New ineligible rows exist for own-path reads and marker-bearing output**
   (`skipped_own_path`, `already_reduced`) — these are new volume, ~300 B/call, bounded by the
   rotation.
4. **`_skip_metrics` defaults `eligible=True`** (bypass_small, skipped_too_large, skipped_busy
   all passed the gate); only `_record_ineligible` writes `eligible=False`.
5. **The terminal seam still prefers its kwarg.** §1's resolution only engages where the kwarg is
   absent; I did not remove the kwarg path.
6. **Collapse/cap markers are stripped before signature comparison** — without this, a correct
   collapse reads as a loss (§2 above).

## 8. Limits, out of scope, rollback

- **The LLM tiers remain unrunnable on this host** — unchanged: `ollama.service`
  `MemoryMax=3221225472` (3.0 GiB) is below the ~4.6 GB the model needs, so every LLM call
  short-circuits at `skipped_cgroup_cap`. No model was loaded during this work (`ollama ps` empty
  throughout).
- **The §7 review gate cannot be assessed yet**: the epoch started today, so ≥5 days and ≥100
  eligible calls are not met. The numbers above are 2 live calls, reported for shape only.
- **`deterministic` active remains disabled** (ruling 7).
- Out of scope, observed only, untouched: the `ghost-showcase` crash loop; the WP_DEBUG
  double-define warnings.
- **Rollback:** set `local_worker.mode: off` (or remove `local-worker` from `plugins.enabled`) and
  restart `hermes-gateway`. Timestamped `config.yaml.bak-*` backups exist; the prior metrics
  epoch is preserved under `~/.hermes/local-worker/epochs/`.
- Tests verified hermetic (the live metrics md5 is identical across a full suite run).

## 9. Next

Awaiting your review before the clock is allowed to run and before anything is pushed. The
main-repo commit SHA for this document is reported in the accompanying message.
