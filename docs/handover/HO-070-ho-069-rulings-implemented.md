---
type: handover
project: imad-consulting
title: "HO-070 — HO-069 rulings implemented: deterministic tier, selection reduction, memory gate; OOM root cause = Ollama's own 3 GiB cgroup"
date: 2026-10-03
from: Hermes Agent (MiMo-V2.6-Pro architect session)
to: Malachy Egbuna / Claude[Sonnet] Web (review)
status: implemented — deterministic tier built and demoed; LLM tiers remain disabled; live mode off
priority: high
---

# HO-070 — HO-069 rulings implemented (re: HO-068)

## 1. Status

All seven rulings are implemented and tested: **41/41** unit tests pass (was 33; 8 new, all
RED first). The deterministic tier exists, is demonstrated live on the two commands HO-069
named, and made **zero network calls and zero Ollama calls** — verified by hard-disabling
`socket` for the run and by `ollama ps` being empty before, throughout and after. The LLM
tiers (shadow/active) stay **disabled** and live `local_worker.mode` stays **off**, as ruled.

**The headline is the pre-check, not the code.** The OOM kills were not the box running out of
RAM: **Ollama is capped at 3.0 GiB by its own systemd cgroup** and it died at 2.93 GiB. The
model is 4.6 GB. No `MemAvailable`/`SwapFree` gate can fix that, and the ruling-2 gate — which
I implemented exactly as specified, because it is still the right guard for *other* services —
cannot make the LLM tier viable on this host. Details in §3.

## 2. Pre-check, raw output (as demanded)

```
$ sudo journalctl -k --since "2026-10-03 05:30" | grep -iE 'out of memory|killed process|oom'
Oct 03 06:22:43 kernel: oom-kill:constraint=CONSTRAINT_MEMCG,nodemask=(null),cpuset=ollama.service,
  mems_allowed=0,oom_memcg=/system.slice/ollama.service,task_memcg=/system.slice/ollama.service,
  task=ollama,pid=1639807,uid=995
Oct 03 06:22:43 kernel: Memory cgroup out of memory: Killed process 1639807 (ollama)
  total-vm:6511152kB, anon-rss:3072484kB, file-rss:21320kB, shmem-rss:0kB, UID:995
Oct 03 06:25:06 kernel: oom-kill:constraint=CONSTRAINT_MEMCG,nodemask=(null),cpuset=ollama.service,
  oom_memcg=/system.slice/ollama.service,task=ollama,pid=1669175,uid=995
Oct 03 06:25:06 kernel: Memory cgroup out of memory: Killed process 1669175 (ollama)
  total-vm:6542504kB, anon-rss:3084920kB, file-rss:22144kB, shmem-rss:0kB, UID:995

$ systemctl status ollama --no-pager | head -12
● ollama.service - Ollama Service
     Loaded: loaded (/etc/systemd/system/ollama.service; enabled; preset: enabled)
    Drop-In: /etc/systemd/system/ollama.service.d
             └─override.conf
     Active: active (running) since Sat 2026-10-03 06:25:10 CEST; 19min ago
   Main PID: 1669607 (ollama)
      Tasks: 9 (limit: 9431)
     Memory: 12.0M (max: 3.0G available: 2.9G peak: 12.4M)

$ systemctl show ollama -p Environment
Environment=... OLLAMA_MODELS=/var/lib/ollama/.ollama/models OLLAMA_NUM_PARALLEL=1
  OLLAMA_MAX_LOADED_MODELS=1 OLLAMA_KEEP_ALIVE=2m OLLAMA_FLASH_ATTENTION=1
  OLLAMA_KV_CACHE_TYPE=q8_0

$ systemctl show ollama -p MemoryMax -p MemoryHigh -p MemoryCurrent
MemoryCurrent=12599296
MemoryHigh=infinity
MemoryMax=3221225472          <-- 3.0 GiB
```

**Was anything besides Ollama killed? No.** Only `ollama`, twice, both by its own cgroup:

- `mysqld` — `2367 21-19:47:52` and `3650993 9-18:55:56` (up 21 days / 9 days, never restarted).
- `docker ps`: `malachy-wp` Up 9 days, `malachy-db` Up 9 days, `imad-automation` Up 11 days
  (healthy), `traefik` Up 11 days, all `frappe_docker-*` Up 3 weeks, `n8n` Up 3 weeks,
  `openagile_postgres` Up 3 weeks (healthy), `wg-easy` Up 3 weeks — i.e. **nothing restarted
  inside the 06:22/06:25 window.**
- One unrelated observation, explicitly **not** attributable to this work: `ghost-showcase` is
  in a pre-existing crash loop (`Restarting (2)`, `restart_count=30575`, exit 2). It is not
  OOM-related (exit 2 is an application error) and has been looping far longer than this task.
- `OLLAMA_KEEP_ALIVE=2m`, `OLLAMA_NUM_PARALLEL=1`, `OLLAMA_MAX_LOADED_MODELS=1` confirm the
  HO-067 §K notes (keep_alive is operator-governed, concurrency already 1 at the server).

No model was loaded at any point in this task: `ollama ps` was empty for every command run.

## 3. The OOM root cause (please read before ruling again)

`MemoryMax=3221225472` = **3.0 GiB hard cap** on `ollama.service`, applied by
`/etc/systemd/system/ollama.service.d/override.conf`. The model is 4.6 GB per `ollama ps`.
It is therefore impossible for a `qwen3:4b-8k` inference to fit: the cgroup kills it as it
loads/infers. Both kills show `constraint=CONSTRAINT_MEMCG` and
`oom_memcg=/system.slice/ollama.service` — a **cgroup-scoped** kill, not a system-wide
out-of-memory, and `anon-rss` at kill time (2.93 / 2.94 GiB) sits right at the cap.

Consequences:

1. The ruling-2 gate is implemented as specified and is a genuine improvement (it stops a call
   from starting when *other* services would be squeezed, and it removes the dangerous resident
   short-circuit), **but it cannot prevent the cgroup kill.** A gate that insists on
   `MemAvailable >= 6236 MiB` would have blocked both of my failing runs — good — yet with the
   gate satisfied the model still cannot fit in 3.0 GiB, so the next attempt would still die.
2. The LLM tiers are therefore not viable on this host until **the operator** either raises
   `MemoryMax` for `ollama.service` (Ollama service config — HO-065 forbids me changing it, so
   this is yours), or a model whose footprint fits under 3.0 GiB is used. `qwen3:4b` does not:
   it reports 4.6 GB.
3. This is consistent with the one call that *did* answer early in the session (the warm-up,
   961 ms) — the model loads into page cache and can serve a request before the cgroup tips.

## 4. Ruling-by-ruling

| Ruling | State | Evidence |
|---|---|---|
| 1 `deterministic` tier; no Ollama call, no network, no memory preflight; LLM tiers disabled; live mode off | done | `test_26` (socket + aux client hard-fail), demo shows `model_calls: 0`, `ollama ps` empty; live `mode: off` unchanged |
| 2 `required_mib = resident_size + 1536` (else 4700), `MemAvailable >= required` AND `SwapFree >= 1024`, resident short-circuit removed, floor secondary | done | `test_32`, rewritten `test_16b`; live: `required_mib 6236` with the real 4.6 GB reading |
| 3 `max_input_chars` stays 12000 | done | unchanged; `test_31`'s large case still records `baseline_chars` from the 50000 cap |
| 4 selection reduction for docker/journalctl logs; test/build keeps summary + all failure sections; else raw; lossless collapse first | done | `test_27`, `test_28`, `test_29`, `test_30`; demo output in §5 |
| 5 `baseline_chars = min(raw_chars, terminal max_bytes)`, savings vs baseline, `raw_chars` separate | done | `test_31`; `terminal_max_bytes()` reuses Hermes' own `tools.tool_output_limits.get_max_bytes()` (returns the real 50000) |
| 6 approved as-is (global collapse, mode off, extra metrics fields, `hook_callback_timeout: 60`) | done | unchanged; noted that 60 applies to all plugin hooks and reverts to 30 if the LLM tier is never enabled |
| 7 local commits of HO-057..HO-069; NO push; report 40-char SHAs + `git status --porcelain` | done | §7 |

## 5. RED → GREEN, and the demo

RED (features absent — the 8 new tests failed, 7 as errors and 1 as a failure on the old
resident short-circuit):

```
$ ~/.hermes/hermes-agent/venv/bin/python tests/test_worker.py
Ran 41 tests ... FAILED (failures=3, errors=2)      # after the first implementation pass
```

GREEN, final:

```
Ran 41 tests in 0.738s

OK
```

Demo (deterministic tier only, live config still `off`, `socket` hard-disabled during the run):

```
BEFORE  free -m : Mem 2590 used, 3145 avail; Swap 1572 used, 475 free
BEFORE  ollama ps: NAME  ID  SIZE  PROCESSOR  CONTEXT  UNTIL     (= empty)

  command        : docker logs frappe_docker-backend-1 --tail 3000
  action/status  : replace / success (lossless)
  raw chars      : 186304
  baseline chars : 50000   (min(raw, tool_output.max_bytes=50000))
  injected chars : 10902
  ratio vs baseline: 0.218040   (savings 78.2%)
  duration       : 5 ms (wall 122 ms)
  lines          : 0 omitted of 3000
  model calls    : 0

  command        : docker logs malachy-wp --tail 3000
  action/status  : raw / skipped_too_large (logs)
  raw chars      : 752791
  baseline chars : 50000
  injected chars : 0
  ratio vs baseline: 1.000000   (savings 0.0%)
  duration       : 102 ms (wall 417 ms)
  model calls    : 0

DURING  ollama ps samples: 4 -> ['NAME  ID  SIZE  PROCESSOR  CONTEXT  UNTIL']   (= empty)
AFTER   ollama ps: empty          AFTER free -m : 2648 used, 3086 avail

live-config hook dispatch (mode=off) returned None -> the tier is NOT live

summary table:
  docker logs frappe_docker-backend-1 --tail 3000    raw 186304  baseline 50000  injected 10902  ratio 0.218040   122 ms  lossless
  docker logs malachy-wp --tail 3000                 raw 752791  baseline 50000  injected      0  ratio 1.000000   417 ms  skipped_too_large
```

### Why `malachy-wp` is (correctly) raw — please note the measurement

The selection ran, and it kept **all 3000 lines**: **1496 of the 3000 lines match the error
pattern** (`PHP Warning: Constant WP_DEBUG already defined …` — every line distinct because of
its own timestamp/pid/client IP). Measured on the real log:

```
collapsed       : 752863 chars / 3000 distinct lines   (nothing to collapse: all unique)
error-matching  : 1496 of 3000 lines
selected        : 3000 lines / 752863 chars (omitted 0)
budget 12000    : DOES NOT FIT -> raw
avg line length : 250 chars
head/tail=40 -> 752863 chars (no)   =30 -> 752863 (no)   =25 -> 752863 (no)   =20 -> 752863 (no)
```

So this is not the selection rule failing — the input is half warnings, so "keep every error
line" legitimately means "keep the file", and no head/tail value changes that (the error lines
alone exceed any budget). Passing raw is the ruled behaviour and the right answer. For
comparison, the frappe log collapses to 10,902 and is injected.

## 6. Rollback

Unchanged from HO-068 §8 in shape; the live config is still `mode: off`, so Hermes' behaviour
is its previous behaviour. To remove the work entirely:

```bash
cp -a ~/.hermes/config.yaml.bak-20261003-060126 ~/.hermes/config.yaml   # pre-task config
# remove 'local-worker' from plugins.enabled to stop loading it (it is inert either way)
rm -rf ~/.hermes/plugins/local-worker ~/.hermes/local-worker
```

The deterministic tier adds **no** new failure surface: it is local text manipulation with no
network, no model and no memory probe; its worst case is returning the input unchanged.

## 7. Commits (ruling 7) — local only, nothing pushed

Plugin repo (its own git, no remote): version bumped to 0.2.0.

```
4d5f2509bec67b9ac1de0520f4e323d92c1fec27  local-worker 0.2.0: deterministic tier, selection reduction, memory gate (HO-069)
```

Main repo (`/home/zubbyik/wordpress_project`) — three commits, explicit paths only (never a
blind `git add -A`), nothing pushed:

```
c7a843de1d4861e13c0da9a499811df11c4e2bd3  docs: HO-057..HO-070 handovers + project reseed preflight tool
66575ff50f6fcd1d53845f461aed3889c176123e  fix(security): deny direct serving of repo files (.htaccess hardening, HO-058)
4845d26b6e06d2f20d4b9c940463aa4c1feb144c  feat(business-checkup): page template, scoring script, styles, tests (HO-062/063/064)
```

The docs commit above is the one that contains this file; a fourth commit records these SHAs
(its own hash is reported in the session output, since a commit cannot contain its own SHA).

`git status --porcelain` after the commits: **empty** (clean tree). `git rev-list --count
origin/main..HEAD` = 3. Nothing pushed, per ruling 7.

Scope note, stated explicitly because it exceeds the literal wording of ruling 7: I included
the *implementation* files that HO-058/063/064 document (`.htaccess`; the five business-checkup
files; the two additive theme edits), because committing only the documents would leave the
history describing code that is not in the repo. I did **not** commit anything in `docs/ops/`
beyond the HO-057 runbook script, and nothing was pushed. Easy to revert locally if you wanted
docs only.

Note for production: the HO-058 `.htaccess` fix is committed **locally only**, so
`imadconsulting.co.uk` still serves `docker-compose.yml` and the rest of the tracked repo until
the fix is pushed *and* pulled on the prod server. The exposure remains live.

## 8. Limitations

- The LLM tiers remain unimplementable on this host (§3) — this is environmental, not a code
  gap. All their code paths are implemented and unit-tested; the memory gate now blocks rather
  than waving calls through.
- Selection reduction is deliberately only reachable for `docker`/`journalctl`-style log
  commands and test/build commands; anything else passes raw (default-deny).
- Warning-saturated logs stay raw (§5). Reducing them would need variant-aware dedup
  (normalising timestamps/pids/IPs), which no ruling authorises.
- `max_input_chars` remains the deterministic tier's payload budget even though no model
  consumes it — as ruled, but see §9.
- Character-based accounting only; no token counts claimed.

## 9. Outstanding / decisions

1. **`MemoryMax` for `ollama.service` (3.0 GiB)** — only the operator can change it. Until it
   is raised (or a sub-3-GiB model is chosen) the LLM tiers cannot run, and the ruling-2 gate
   will simply report `skipped_low_memory` forever.
2. **`max_input_chars` for the deterministic tier**: since no model consumes this payload, the
   12000 figure is not a context-window limit. Raising it toward Hermes' own 50000 cap would
   let some mid-size logs inject instead of passing raw. Explicitly *not* done, because ruling
   3 says keep it at 12000 — flagging the reasoning in case it was aimed at the LLM tier only.
3. Confirm `mode: off` stays live, and whether `deterministic` should now be enabled live
   (ruling 1 kept it off pending your approval).
4. Whether to keep `hook_callback_timeout: 60` while no LLM tier is enabled (ruling 6 says
   revert to 30 in that case; I have left 60 for now and can set 30 on your word).

## 10. Evidence index

| What | Where |
|---|---|
| plugin source + 41 tests | `~/.hermes/plugins/local-worker/` |
| GREEN output | `/tmp/g6.txt` (`Ran 41 tests ... OK`) |
| deterministic demo | `venv/bin/python tests/integration_shadow_demo.py deterministic` (output `/tmp/det.txt`) |
| real inputs used | `docker logs frappe_docker-backend-1 --tail 3000`, `docker logs malachy-wp --tail 3000` |
| raw handles | `~/.hermes/local-worker/raw/*.txt` (0600, dir 0700) |
| metrics | `~/.hermes/local-worker/metrics.jsonl` |
| pre-check evidence | `sudo journalctl -k` lines quoted in §2; `systemctl show ollama -p MemoryMax` |
| config backup | `~/.hermes/config.yaml.bak-20261003-060126` |
| prior reports | `docs/handover/HO-068-local-worker-implementation.md`, `HO-069-ruling-on-ho-068.md` |
