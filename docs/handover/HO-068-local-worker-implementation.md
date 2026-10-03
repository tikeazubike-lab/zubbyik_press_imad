---
type: handover
project: imad-consulting
title: "HO-068 — local-worker (Ollama qwen3:4b-8k) implemented: RED/GREEN, shadow demo, live findings"
date: 2026-10-03
from: Hermes Agent (MiMo-V2.6-Pro architect session)
to: Malachy Egbuna / Claude[Sonnet] Web (review)
status: implemented — shadow demonstrated, ACTIVE NOT ENABLED, awaiting review
priority: high
---

# HO-068 — local-worker implemented (re: HO-065 spec, HO-066 Phase 0, HO-067 approval)

## 1. Status in one paragraph

The local-worker is built, tested (33/33 stdlib-unittest tests, Ollama always mocked),
installed as an out-of-tree Hermes plugin, and exercised live through Hermes' real plugin
discovery and hook dispatch. **Active mode is NOT enabled** (HO-067 §J). The live demo
surfaced five real defects/risks, three of which I fixed; the two that remain are
environmental and need your ruling. **Headline: on this host, at the memory floor HO-067
approved (4608 MiB), the worker's model load killed the Ollama server — twice.** Every
failure degraded correctly (raw passthrough + `fallback_error` + raw handle on disk), so
Hermes never lost evidence, but the floor is not sufficient protection. I left
`local_worker.mode: off` in the live config and recommend raising the floor before shadow is
re-enabled.

Nothing was pushed. The plugin has its own git repo with one local commit (ruling 4):
`e512fdcc0b84a91bbafc66067834136b7fc590a1` (amended from `51e336dc3f4f75dfdf9133aec435dfa3df764eb3`).

## 2. HO-067's pre-check, raw output (its first instruction)

HO-067 was right: HO-066 §1 cited "§9" for the gate-compliance verification, and HO-066 has
no §9 (its last sections are §7 Status / §8 Evidence index). My error, acknowledged. Raw
output as demanded:

```
$ git -C ~/.hermes/hermes-agent status --porcelain
(no output — 0 modified files)

$ ls -la ~/.hermes/plugins
total 8
drwx------ 2 zubbyik zubbyik 4096 Sep 25 11:14 .
drwx------ 18 zubbyik zubbyik 4096 Oct  3 06:01 ..
(nothing else — this plugin is the first user plugin on this box)

$ stat -c '%y %n' ~/.hermes/config.yaml
2026-10-03 06:01:26.638814772 +0200 /home/zubbyik/.hermes/config.yaml
```

The config timestamp is *after* this task started: ruling 3 authorized the config change, and
it is backed up (see §6).

## 3. What was built

Out-of-tree Hermes plugin — **no file under `hermes-agent/` was touched** (verified above),
so it survives `hermes update`:

```
~/.hermes/plugins/local-worker/
  plugin.yaml                      manifest (kind: standalone, provides_hooks)
  __init__.py                      register(ctx): 2 hooks + 1 auxiliary task slot
  worker.py                        the library — all logic, independently testable
  README.md                        operator docs + rollback
  .gitignore
  tests/test_worker.py             33 stdlib-unittest tests, Ollama mocked
  tests/integration_shadow_demo.py opt-in live demo (discovery|shadow|active|guards|mock)
```

Integration points (all existing Hermes seams — HO-065 "reuse, do not reimplement"):

| Seam | Why |
|---|---|
| `transform_terminal_output` hook | fires on terminal/background-process output **before** the JSON envelope is built and *before* truncation, so full output is visible to the worker and its reply is still capped afterwards |
| `transform_tool_result` hook | fires **before the result enters model context**; used for the envelope case and as the idempotent second (skipped) call site |
| `auxiliary.local_worker` task slot | resolved by `agent/auxiliary_client.py` (explicit args > `auxiliary.<task>.*` > auto) — the existing Ollama route, no new provider code, no `ollama` CLI shelling, no direct HTTP client |

Deliberate module choice: the plugin registers both hooks, but the **deterministic gate
lives in the shared wrapper**, so a terminal call cannot be processed twice (§H): the second
hook sees the `LOCAL-WORKER REDUCTION` marker and returns `None`.

## 4. RED → GREEN

RED (worker.py absent — every test fails for the right reason):

```
$ ~/.hermes/hermes-agent/venv/bin/python tests/test_worker.py
Ran 29 tests ... FAILED (errors=29)
FileNotFoundError: .../local-worker/worker.py
```

GREEN, final:

```
$ PYTHONPATH=~/.hermes/hermes-agent ~/.hermes/hermes-agent/venv/bin/python tests/test_worker.py
Ran 33 tests in 0.476s

OK
```

Two test-side facts worth knowing: **`pytest` is not installed in the Hermes venv**, so the
suite is stdlib `unittest` + `unittest.mock` (zero new dependencies, no mutation of the live
venv). And the two tests HO-067 called "required" are present and pass by name:
`test_11_envelope_preserved_...` (asserts Hermes' own
`agent.context_compressor._sum_terminal` still reads `exit_code` from the transformed
envelope) and `test_12_terminal_call_is_not_processed_twice`.

## 5. Measured results (HO-065 §15 deliverable)

Real input used for the numbers: `docker logs frappe_docker-backend-1 --tail 3000`
(a real allow-listed command, 186,282 chars / 3000 lines, mostly repeated MySQL
`ConnectionRefusedError` tracebacks).

| Metric | Value |
|---|---|
| raw input | 186,282 chars |
| after deterministic reduction (lossless, collapse-with-counts) | 10,654 chars — 0.0572 of raw |
| final injected text (mocked model, see below) | **844 chars — 0.0045 of raw (0.5%)** |
| worker latency (mocked 2.0 s) | 2,002 ms |
| shadow turn-path latency, post-fix | **0.89 ms** (pre-fix live runs: 118 ms and 1445 ms — see §7.2) |
| worker thread body while turn path idle | 5,133 ms (mocked 5.0 s) |

The exact final text the cloud model receives (verbatim, model mocked — see finding 3):

```
LOCAL-WORKER REDUCTION (machine-generated, may be incomplete) — raw output at /home/zubbyik/.hermes/local-worker/raw/raw-20261003-062534-1669830-34639.txt
Verify against the raw file before relying on this reduction.

observed facts:
- type: log_output
- summary: Repeated MySQLdb/pymysql connection failures: every traceback ends in MySQLdb.connections.Connection.__init__ -> ConnectionRefusedError. Identical tracebacks repeat; no other error class present.
- failure: MySQLdb.connections.Connection.__init__ — ConnectionRefusedError: [Errno 111] Connection refused @ MySQLdb/connections.py:200
- warnings: identical tracebacks collapsed into one cluster
- cluster: MySQL connection refused (DB host unreachable) (x1)
- note: facts above are extracted from the raw output; counts are a regex cross-check of the raw text, not a second opinion.
```

Health of the machine across the two live attempts (`free -m` / `ollama ps`, real):

```
shadow stage  BEFORE : Mem avail 2037 MiB, Swap 2045/2047 used (2 free), model RESIDENT 4.6 GB
              AFTER  : Mem avail 3205 MiB, Swap 1336 used, "ollama server not responding"
active stage  BEFORE : Mem avail 5027 MiB, Swap 1283 used (764 free), model NOT resident
              AFTER  : Mem avail 1972 MiB, Swap 2028 used (19 free), "ollama server not responding"
                       (worker ran 48,294 ms, then APIConnectionError)
```

## 6. Ruling-by-ruling compliance

| Ruling | State | Evidence |
|---|---|---|
| 1 redaction gated on `_redact_enabled()`, before the call and before the raw handle | done | `_execute()` redacts first; raw handle stores the redacted text; `test_07`+`test_14`; perms 0600/0700 asserted in `test_08` |
| 2 model tag `qwen3:4b-8k`, pinned input budget 12000 chars | done | `test_17`; live `ollama ps` confirms the tag and CONTEXT 8192 |
| 3 config in `~/.hermes/config.yaml`, backup first, tests before the change | done | backup `~/.hermes/config.yaml.bak-20261003-060126`; tests green before any config edit; missing/invalid config ⇒ `off` (`test_09b`) |
| 4 local commits only, report 40-char SHA | done | `e512fdcc0b84a91bbafc66067834136b7fc590a1`, no remotes configured |
| F default-deny allowlist | done | `test_15`, `test_15b`; live: `cat` ⇒ untouched, `docker logs` ⇒ processed |
| G preserve JSON envelope | done | `test_11`, incl. Hermes' `_sum_terminal` reading `exit_code` |
| H idempotent, one processing point | done | `test_12` (terminal call processed once) |
| I thresholds 6000 / 12000 / reduce / else raw | done | `test_17`, `test_17b`, `test_17c`; **see finding 4 for the operational consequence** |
| J non-blocking lock, `skipped_busy`, shadow in a thread, active timeout 45 s | done | `test_13`, `test_18`, `test_24` |
| K memory preflight via `/api/ps`, resident skip, floor 4608, report SwapFree; no Ollama config change | done as ruled | `test_16`, `test_16b`; live resident detection works — **see finding 3: as ruled, this floor is not safe** |
| L scope: test/build/lint/log output; diffs + search deferred | done | allowlist covers pytest/npm/vite/tsc/eslint/ruff/composer/docker/journalctl; no diff/search handling exists |
| M label `LOCAL-WORKER REDUCTION`, distinct from compaction summaries | done | marker constant + tests |
| N extra tests | done | envelope, no-double-process, busy, redaction-before-call, perms, allowlist-miss — all present and passing |

Config applied (masked diff against the backup — `auxiliary.vision` verified intact):

```diff
 auxiliary:
   vision:
     ...
     context_length: 1000000
+  local_worker:                      # aux task slot (the model route)
+    provider: custom
+    model: qwen3:4b-8k
+    base_url: http://127.0.0.1:11434/v1
+    timeout: 120
+local_worker:                        # worker policy
+  mode: off                          # off | shadow | active   (returned to off, see §8)
+  base_url: http://127.0.0.1:11434/v1
+  model: qwen3:4b-8k
+  bypass_chars: 6000
+  max_input_chars: 12000
+  active_timeout_s: 45
+  request_timeout_s: 120
+  memory_floor_mib: 4608
+  raw_dir: ~/.hermes/local-worker/raw
+  raw_retention_days: 7
+  metrics_log: ~/.hermes/local-worker/metrics.jsonl
 plugins:
-  enabled: []
+  enabled:
+    - local-worker
+  hook_callback_timeout: 60
```

`plugins.hook_callback_timeout` was raised 30 → 60 because Hermes' bounded-hook dispatch
otherwise abandons a callback after 30 s, which is shorter than the 45 s active budget HO-067
set. Flagging it as a change beyond the ruling's letter, required by its intent.

## 7. LIVE FINDINGS (the important section)

**1. `_read_meminfo()` key mismatch — live memory always read 0 MiB. FIXED.**
The reader returned `/proc/meminfo` names (`MemAvailable`), the preflight looked up
`MemAvailable_kb`. Unit tests passed because they injected dicts in the `_kb` form. Live
proof: `mem_available_before_mib: 2027.1` in an early run where `free -m` said 2037 MiB —
i.e. it read 0 and the floor check would have been meaningless for any non-resident model.
Fixed in `worker.py` (reader now exposes both spellings) and pinned by `test_23`, which
exercises the real reader against a fake `/proc/meminfo`.

**2. Shadow mode added 1.4 s to the turn path, contradicting §J. FIXED.**
The preflight HTTP call and the raw-handle write were happening inline before the thread was
spawned. Live pre-fix measurements: `hook returned None ... in 1445 ms` in one run and
`... in 118 ms` in another — the inline cost was dominated by the `GET /api/ps` probe, so it
varied with the model's state (a resident model answers fast; a cold/unresponsive one costs
the full socket timeout). All work now happens inside the spawned thread; post-fix turn path
is 0.89 ms while a 5 s worker runs, and `test_24` asserts that before the thread body runs,
nothing has been preflighted, written or called.

**3. The approved memory floor lets the worker kill Ollama. NEEDS YOUR RULING (blocking).**
Twice, a real call ended with `ollama server not responding` while swap went to exhaustion:

- shadow attempt: started at 2037 MiB available / 2 MiB swap free. The model was *resident*,
  so §K's resident short-circuit skipped the floor check — and the call still killed Ollama.
- active attempt: started at 5027 MiB available (above the 4608 floor, model not resident),
  loaded the model, ran 48.3 s, drove swap to 19 MiB free, and Ollama died.

So the floor is below what `qwen3:4b-8k` actually needs on this 7.9 GiB box alongside
MariaDB/n8n/FastAPI/Hermes: the model alone is 4.6 GB (confirmed by `ollama ps`), leaving
~2.0 MiB-class swap headroom for the KV cache and everything co-resident. Two separate
mechanisms are unsafe as specified: (a) "resident ⇒ skip the check" ignores that *inference*
needs headroom beyond the loaded weights, and (b) 4608 MiB is simply too low. Recommended
fix, your call: require `max(memory_floor_mib, model_bytes + 2048 MiB)` **and** a minimum
`SwapFree` (e.g. ≥512 MiB) even when resident, with `memory_floor_mib` raised to ~6500. Note
this also means **shadow is not risk-free** — it triggers the same model load.
No kernel OOM line was readable from here, so the *cause* is correlation-plus-timing, not a
proven OOM kill; the memory numbers, the 48 s duration and the server death are all measured.

**4. Real large logs are usually SKIPPED, not reduced. NEEDS YOUR RULING (design).**
Per §I as written, deterministic reduction is lossless collapse-with-counts and if the result
still exceeds 12000 chars the worker is skipped. Measured: `docker logs malachy-wp --tail 3000`
= 750,959 chars → collapses to 36,057 → **`skipped_too_large`**, no call. Access logs are
mostly *unique* lines (bot URLs), so collapse can never reach the budget — the worker will
not fire on the very output class the brief targets. The §A pair "keep head/tail and every
ERROR/FAIL/Traceback line with context" would make big logs fit but *is* selective; §17's
"never truncate silently" and HO-067's test-17b expectation ("cannot be reduced ⇒ raw")
pull the other way. I implemented the strict reading (tests win) and am surfacing the
consequence instead of silently choosing. Options: raise `max_input_chars` to ~24000
(`qwen3:4b-8k` has an 8192-token context ≈ 28–32k chars, so 12000 is conservative), and/or
authorize an explicit-selection reduction that states how many lines it omitted.

**5. `mode: off` in YAML arrives as boolean `False`. FIXED (fail-safe was already correct).**
YAML 1.1 parses bare `off`/`on` as booleans. The old code fell through to `off` by accident;
now handled explicitly and pinned by `test_25`.

Non-findings worth recording as *working as designed*: the closed-port failure path produced
`fallback_error` in 1.8 s with the raw output intact; the busy lock produced `skipped_busy`;
`memory_preflight` correctly detected a resident model via a real `GET /api/ps`; the raw
handle was written at 0600 into a 0700 directory on every path; and the live hook dispatch
returned `None` (raw passthrough) in every degraded case, so **no path ever lost the original
tool output**.

## 8. Rollback (HO-065 §E)

The plugin is inert by default and the live config is back to `off`, so Hermes' current
behaviour is already the previous behaviour. To remove it entirely:

```bash
# 1. disable (already done): local_worker.mode -> off in ~/.hermes/config.yaml
#    or restore the pre-task config verbatim:
cp -a ~/.hermes/config.yaml.bak-20261003-060126 ~/.hermes/config.yaml
# 2. stop loading the plugin (it can stay on disk, inert):
#    remove 'local-worker' from plugins.enabled in ~/.hermes/config.yaml
# 3. delete the plugin and its state:
rm -rf ~/.hermes/plugins/local-worker ~/.hermes/local-worker
```

`plugins.hook_callback_timeout: 60` can revert to the 30 s default if step 2 is taken.
Raw handles live only under `~/.hermes/local-worker/raw` (outside git), 0600, pruned after 7
days; metrics are character-based JSONL only — no payload text is ever logged.

## 9. Limitations (intentionally unsupported)

- **Diffs and search compression are not implemented** — deferred by §L, not by oversight.
- No lossy "head/tail + important lines" selection (see finding 4) — by strict reading of §A/§I.
- Only the `terminal` tool is processed; `read_file`, `write_file`, `search_files`, browser
  tools etc. are untouched by design (§F).
- No new provider code, no `ollama` CLI shelling, no second HTTP client — the aux task slot
  and `agent.redact` are reused as-is.
- Thinking blocks are stripped and `/no_think` is appended, but no tokens/temperature tuning
  beyond `temperature 0.1` is attempted.
- Metrics are **character**-based; token counts are not claimed.
- The plugin does not implement its own Ollama availability check beyond the preflight — if
  Ollama is down, the call fails fast and raw is used (demonstrated).

## 10. Deviations and interpretations (stated, not silent)

1. **Global** collapse of repeated lines (not merely consecutive) — §A says "collapse repeated
   lines with counts"; consecutive-only collapse cannot reduce a cycling log. Nothing is
   dropped, so this stays within "never truncate silently".
2. Strict `skipped_too_large` instead of selective truncation (finding 4).
3. `plugins.hook_callback_timeout` 30 → 60 (§6).
4. `local_worker.mode` returned to `off` after the demo, although HO-067 only said not to
   enable *active*: with finding 3 unfixed, leaving shadow enabled in the live gateway would
   expose the box to repeated model loads that kill Ollama. I judged that negligent and will
   re-enable shadow on your word.
5. Metadata beyond §D (`resident`, `wall_ms`, `char_based`) added to the metrics line.

## 11. Outstanding / needs a decision

1. **Ruling on finding 3** (memory floor + resident short-circuit) — blocking for shadow/active.
2. **Ruling on finding 4** (raise `max_input_chars`, and/or authorize explicit selection).
3. Confirm `mode: off` staying the live default until 1 and 2 are settled.
4. Whether to push the plugin repo anywhere (none configured; no push performed).
5. The main repo's `docs/handover/HO-068-*.md` is **uncommitted**, like HO-057…HO-067 — say the
   word and I will commit them together.

## 12. Evidence index

| What | Where |
|---|---|
| plugin source + tests (committed) | `~/.hermes/plugins/local-worker/`, `e512fdcc0b84a91bbafc66067834136b7fc590a1` |
| GREEN suite output | `/tmp/g3.txt` (`Ran 33 tests ... OK`) |
| discovery + guards stage | `venv/bin/python tests/integration_shadow_demo.py discovery\|guards` |
| shadow stage (real, pre-latency-fix) | `/tmp/shadow-stage.txt` |
| active stage (real, Ollama died) | `/tmp/active-stage.txt` |
| mocked-model full chain | `... integration_shadow_demo.py mock` |
| config backup | `~/.hermes/config.yaml.bak-20261003-060126` |
| config check/apply script | `~/.hermes/cache/scratch/apply-local-worker-config.py` |
| raw handles | `~/.hermes/local-worker/raw/*.txt` (0600, dir 0700) |
| metrics | `~/.hermes/local-worker/metrics.jsonl` |
| phase 0 recon (re: §9 citation error) | `docs/handover/HO-066-ollama-worker-phase0-recon.md` |
