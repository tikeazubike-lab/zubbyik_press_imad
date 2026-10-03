HO-069 — Rulings on HO-068 (tiering, memory, selection reduction, metrics)

PRE-CHECK (raw output required): run
  sudo journalctl -k --since "2026-10-03 05:30" | grep -iE 'out of memory|killed process|oom'
  systemctl status ollama --no-pager | head -12 ; systemctl show ollama -p Environment
  ps -o pid,etime,cmd -C mysqld ; docker ps --format '{{.Names}}\t{{.Status}}'
Report whether anything besides Ollama was killed. Do NOT load the model again until I reply.

RULINGS
1. Tiering: add mode `deterministic` (no Ollama call, no network, no memory preflight). The
   LLM tiers (shadow/active) stay DISABLED. Keep live mode: off until I approve deterministic.
2. LLM tier memory gate (implement + test, even though the tier stays disabled):
   required_mib = resident_size_mib(from /api/ps, else 4700) + 1536; require MemAvailable >=
   required_mib AND SwapFree >= 1024 MiB, checked even when the model is resident. Remove the
   resident short-circuit. Keep memory_floor_mib as a secondary minimum.
3. max_input_chars stays 12000. Do not raise it (log chars/token is ~2.5-3; 8192-token window).
4. Selection reduction (deterministic tier, log commands only: docker logs, journalctl):
   keep head N + tail N lines (configurable, default 40 each) plus every ERROR/FAIL/Traceback/
   Exception/WARN line with +-2 lines of context. Output MUST state "N lines omitted of M",
   "selection, not full output", and the raw path. Test/build output: keep the summary line and
   ALL failure sections whole; if that does not fit the budget, pass raw. Lossless collapse
   with counts runs first.
5. Metrics: add baseline_chars = min(raw_chars, terminal max_bytes) and report savings against
   it (raw_chars retained as a separate field). Update the demo to show both.
6. Approved as-is: global line collapse, mode returned to off, extra metrics fields,
   hook_callback_timeout 60 (note: applies to all plugin hooks; revert to 30 if the LLM tier
   is never enabled).
7. Commits: local commits of HO-057..HO-069 in the main repo and the plugin repo are authorized.
   NO push. Report full 40-char SHAs and `git status --porcelain` output.

TESTS (RED first, show failing output)
  - deterministic mode makes zero network calls (patch the socket / aux client to raise)
  - selection reduction keeps every error line, reports the correct omitted count, includes marker
    and raw path; test output keeps all failure sections; oversize test output => raw
  - baseline_chars computed against the 50,000 cap
  - memory gate blocks when resident-but-low-swap; no short-circuit
  - counts in any injected text come from the regex pass, never from model output

DEMO (deterministic mode only): docker logs frappe_docker-backend-1 --tail 3000 and
docker logs malachy-wp --tail 3000, showing raw / baseline / injected chars, ratio vs baseline,
duration, and confirm zero Ollama calls and `ollama ps` empty throughout. Do not enable active.
