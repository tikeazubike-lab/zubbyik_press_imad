---
type: handover
project: IMAD Consulting — lead capture & content automation
title: "HO-072 — HO-071 rulings implemented: tier gate, cgroup guard, deterministic_shadow, signature grouping"
date: 2026-10-03
from: Hermes Architect (MiMo-V2.6-Pro)
to: Reviewer (Claude Web) / Malachy
status: complete — awaiting acknowledgment to switch live mode to deterministic_shadow
priority: high
---

# HO-072 — HO-071 rulings implemented

Scope: the local-worker plugin (`~/.hermes/plugins/local-worker/`, v0.2.0 → **v0.3.0**).
All seven HO-071 rulings are implemented. **Nothing is pushed.** Live mode is still `off`; per
§3 it switches to `deterministic_shadow` only after your acknowledgment.

Test state: **51/51** plugin tests + **7/7** summariser tests, exit 0. Hermes source tree
untouched (0 modified files). Ollama never loaded (`ollama ps` empty before, during and after).

---

## §1 — RECONCILE: the log, the collapse, and my HO-068 error

### Raw output

The log now (demanded re-run):

```
$ docker logs malachy-wp --tail 3000 > /tmp/ml_now.log
bytes: 745428
lines: 3000
distinct lines: 3000
error-regex matches: 1498 lines
collapse_repeats() on it: 745427 chars (savings 0.0001%) -> NO collapse
```

HO-068 recorded `750,959 → 36,057`; HO-070 showed `752,863 / 3,000 distinct / no collapse`.
The diff restricted to the collapse function:

```
$ git -C ~/.hermes/plugins/local-worker diff e512fdc..4d5f250 -- worker.py   # collapse hunks
-        collapsed = self.collapse_repeats(text)
+        collapsed = collapse_repeats(text)                    # extracted to module level
     note = f"\n[x{total} lines / {distinct} distinct{over} — over budget, not reduced]"
     return collapsed[: max_chars * 3] + note                  # UNCHANGED in both revisions
```

### Verdict

**Neither.** The log did not change materially (745,428 / 3,000 / 3,000-distinct now vs
750,959 then — a rolling `--tail 3000`, so the window shifts as lines append; same content
class, same distinctness), and the collapse **behavior did not regress** — the diff shows a pure
refactor with the over-budget path byte-identical.

**The error was mine, in HO-068's prose.** `36,057` is exactly `12000 * 3 + len(note)`
(`36000 + 57`): it is `reduce_deterministic`'s **over-budget artifact** — the capped-and-noted
return, not a successful collapse. I read the artifact's output length as "collapsed to 36,057"
and reported a 95% collapse that never happened. HO-070's `3000 distinct / no collapse` is the
correct figure. The code was never wrong; my reporting was.

Guard test added (`test_43_all_distinct_over_budget_input_is_not_a_collapse`): it pins that
`collapse_repeats()` on an all-distinct over-budget input returns it **unchanged** and that
`reduce_deterministic` labels it over-budget — so the artifact can never again be mistaken for a
collapse. No RED-first fix was required, because there was no behavior regression to fix.

---

## §2 — LLM tier shelved (gate + cgroup guard + timeout revert)

**Gate.** `shadow`/`active` now require `local_worker.llm_tier_enabled: true` (default `false`):

```
$ live: local_worker.mode: off ; llm_tier_enabled: false
  normalize_config({"mode": "shadow"})                          -> mode off, config_error set
  normalize_config({"mode": "shadow", "llm_tier_enabled": True}) -> mode shadow
  effective config_error                                         -> llm_tier_requires_llm_tier_enabled (on rejection)
```

Rejection logs `config_error=llm_tier_requires_llm_tier_enabled` at WARNING and behaves as off —
a model tier can never run by accident. `test_34` covers all three cases.

**cgroup guard.** Read-only observation of `/sys/fs/cgroup/system.slice/ollama.service/memory.max`
(never modified). If numeric and below the required model size → `status=skipped_cgroup_cap`:

```
memory.max bytes: 3221225472        # 3.0 GiB — read live, not changed
required_mib                      = resident (else 4700) + 1536 = 6236 MiB
=> 3072 < 6236  => every LLM-tier call is skipped with status=skipped_cgroup_cap
```

This is the guard that actually addresses the HO-069/HO-070 OOM: the model died against the
service's **own 3.0 GiB cgroup cap**, which no `MemAvailable`/`SwapFree` gate can see. When the
cap is `max`/unreadable the guard fails open (the RAM+swap gate still applies). `test_35` covers
numeric-below, unlimited, and numeric-above.

**Hook timeout reverted** `plugins.hook_callback_timeout: 60 → 30` (config edit, timestamped
backup `config.yaml.bak-20261003-094*`).

---

## §3 — `deterministic_shadow` (implemented, NOT live)

New mode: runs the identical deterministic pipeline, logs the metrics, returns `None`
(raw passthrough). No thread, no network, no model. Live mode remains `off`.

Real run (sockets hard-disabled; `ollama ps` sampled throughout):

```
$ venv/bin/python tests/integration_shadow_demo.py deterministic_shadow
live config (unchanged): off | demo cfg: mode=deterministic_shadow
BEFORE  ollama ps: (empty)   AFTER  ollama ps: (empty)
DURING  ollama ps samples: 4 -> ['NAME  ID  SIZE  PROCESSOR  CONTEXT  UNTIL']   # header only

  command        : docker logs frappe_docker-backend-1 --tail 3000
  action/status  : raw / shadow_deterministic (lossless)
  raw chars      : 186304    baseline chars: 50000    injected chars: 10902
  ratio vs baseline: 0.218040  (savings 78.2%)     duration: 5 ms   model calls: 0
  PASSTHROUGH    : raw returned to the caller; the metrics above are what
                   deterministic mode would have injected

  command        : docker logs malachy-wp --tail 3000
  action/status  : raw / skipped_too_large (logs)
  raw chars      : 745646    baseline chars: 50000    injected chars: 0
  duration       : 150 ms    model calls: 0
```

`action=raw`, `replacement=None`, and the metrics carry what *would* have been injected — so the
shadow data is useful without changing a byte of what the model sees. `test_36` pins that no
thread and no network are touched.

---

## §4 — Signature-normalized grouping (docker/journalctl only)

Applied **after** the lossless collapse and **before** selection. Normalized classes only:
ISO/syslog/apache timestamps, `pid=N` / `[pid N]`, IPv4/IPv6, UUIDs, hex tokens ≥ 8 chars
(an optional `0x` prefix is part of the token). **Ports, status codes and line numbers are never
normalized** — `test_37` proves two lines differing in code/path/port stay distinct.
Group format: `xN, lines A-B | first: <verbatim> | last: <verbatim>`, ordered by first
occurrence (`test_38`); singleton lines are emitted verbatim and unchanged. Header states
`grouped by normalized signature; timestamps/pids/IPs of middle occurrences not shown` plus the
raw path (`test_44`). Never applied to test/build output (`test_41`).

**It works on the real `malachy-wp` log** — measured directly:

```
input lines             : 3000
normalized groups       : 70   (multi-occurrence: 14)
lines covered by groups : 2944 / 3000
WP_DEBUG lines          : 1498 -> distinct signatures: 1     # collapses to ONE entry
non-WP_DEBUG lines      : 1502 -> distinct signatures: 69    # genuinely distinct
largest groups          : [1498, 1368, 19, 14, 8]
  x1498, lines ... | first: [Sat Oct 03 ...] [php:warn] [pid 1395:tid 1395] [client <IP>:0]
        PHP Warning:  Constant WP_DEBUG already defined in /var/www/html/wp-config.php(128)
  x1368, lines 1-3000 | first: 66.249.76.39 - - [03/Oct/2026:06:06:18 +0000]
        "GET /?p=2213157361100 HTTP/1.1" 404 ...
grouped chars           : 25781   (budget max_input_chars: 12000)
```

**Finding (new, needs a ruling):** grouping cuts this log from 3000 lines to 70 (+ has also
collapsed 1368 bot 404s into a single entry), but the **grouped text is 25,781 chars**, still
above the ruled 12,000-char budget — so it falls through to selection and ends
`skipped_too_large`/raw. The blocker is now the char budget, not the log: each group entry
carries first+last *verbatim* (~200-360 chars each × 70 groups). Two candidate remedies, both
outside my authority to choose: (a) raise `max_input_chars` for the deterministic tier — it was
frozen at 12000 for the 8192-token model window, which does not apply to a tier with no model
(the same point I raised in HO-070); or (b) cap the per-entry `first:`/`last:` excerpt length,
which changes the format you ruled. Until one is chosen, log-dense-with-one-dominant-class
inputs like this one still pass raw — correct per HO-069 §4, but the payoff is capped.

`test_39` uses these exact verbatim WP_DEBUG lines as its fixture and asserts one entry;
`test_40` asserts no error class is dropped and that group counts sum to the input line count.

---

## §5 — `tests/summarize_metrics.py` (read-only)

```
$ venv/bin/python tests/summarize_metrics.py
metrics log: /home/zubbyik/.hermes/local-worker/metrics.jsonl      # 6479 bytes, mode=600
calls (total 14)
  by status   : fallback_error=3, lossless=1, skipped_busy=1, skipped_low_memory=1,
                skipped_too_large=4, success=4
  by task_type: log_reduction=1, terminal_output=13
  by mode     : active=6, deterministic=4, deterministic_shadow=2, shadow=2
  by tier_kind: None=8, logs=3, lossless=3
chars
  baseline_chars_sum : 300000
  injected_chars_sum : 32706
  savings            : 89.1%
duration (ms)
  p50=102 p95=48294 max=48294 mean=4366.3
lossless stage
  results            : 3
  eligible calls     : 14
  skipped_too_large  : 4 (28.57% of eligible calls)
```

The `active=6` / `shadow=2` rows and `fallback_error=3` are the historical pre-gate runs
(including the two that killed Ollama); they now cannot recur, because those tiers need the
explicit opt-in. `test_summarize_metrics.py` (7 tests) pins counts, sums, savings %, p50/p95,
the skipped_too_large rate, read-only-ness and the empty/missing-file cases.

---

## §6 — Commits (local only, NO push)

```
7d52ef18cdbb45acbb4e00d6386cf8c7e2d09e0c  [plugin] feat(local-worker): HO-071 rulings —
                                          llm tier gate, cgroup guard, deterministic_shadow,
                                          signature grouping            (v0.3.0)
4d5f2509bec67b9ac1de0520f4e323d92c1fec27  [plugin] 0.2.0 (HO-069 rulings)
e512fdcc0b84a91bbafc66067834136b7fc590a1  [plugin] 0.1.0 (initial)
```

`git status --porcelain` — plugin repo: **empty** (clean, no remote configured).
Main repo: **clean** after this handover's commit; 4 commits ahead of origin before it, nothing
pushed. `git -C ~/.hermes/hermes-agent status --porcelain` → **0 lines**.

---

## §7 — Out of scope: observed only, not touched

- **`ghost-showcase` crash loop** — `exit=2 restarts=30747 oom=false`, still crash-looping
  (`started 2026-10-03T07:40:54Z`). Pre-existing, not OOM-related, not caused by this work and
  not modified.
- **WP_DEBUG double-define warnings** — 1498 of the 3000-line tail are
  `PHP Warning:  Constant WP_DEBUG already defined in /var/www/html/wp-config.php(128) :
  eval()'d code on line 3`. All identical modulo timestamp/pid/IP. Not touched; the mechanism
  is a `define('WP_DEBUG', ...)` inside eval'd code at `wp-config.php:128`.
- Ollama service config untouched: `active`, `memory.max` still `3221225472`, nothing loaded.

---

## §8 — Test evidence (RED → GREEN)

```
RED   : 51 tests -> 5 failures, 5 errors (gate/cgroup/grouping/shadow absent; summariser absent)
GREEN : tests/test_worker.py            -> Ran 51 tests ... OK   (exit 0)
        tests/test_summarize_metrics.py -> Ran  7 tests ... OK   (exit 0)
```

Tests added this round: 34 (tier gate), 35 (cgroup), 36 (shadow), 37–41 (grouping), 43 (the
HO-068 artifact guard), 44 (grouped header/raw path) + the 7 summariser tests. Four older memory
tests (16b/16c/23/32) were updated to neutralise the cgroup axis explicitly, because the new
guard correctly reads the **real** 3.0 GiB cap on this host — their `ok` assertions now exercise
only the RAM/swap axis they were written for.

---

## §9 — Open questions

1. **§4 remedy** — raise `max_input_chars` for the deterministic tier (no model consumes the
   payload), or cap per-entry `first/last` excerpt length? Confirmed blocker for the `malachy-wp`
   class of log.
2. **Live mode** — switch to `deterministic_shadow` now that the tests pass? §3 requires your
   acknowledgment; the config is staged at `off` and the change is one line.
3. **`max_input_chars` for the deterministic tier** — HO-069 §3 froze 12000 on token-window
   reasoning that does not apply to a tier with no model (repeated from HO-070 Q3; the §4 finding
   now gives it a concrete cost).
4. **`hook_callback_timeout`** — reverted to 30 as ruled. If the LLM tier is never enabled it
   stays 30.
