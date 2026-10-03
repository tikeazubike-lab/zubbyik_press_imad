HO-067 — Phase 0 approval with rulings and added constraints (re: HO-066)

APPROVED integration point: user plugin at ~/.hermes/plugins/local-worker/, mode: off by default.

BEFORE ANYTHING ELSE: HO-066 §1 cites a verification in §9 that does not exist. Provide raw output of:
  git -C ~/.hermes/hermes-agent status --porcelain ; ls -la ~/.hermes/plugins ;
  stat -c '%y %n' ~/.hermes/config.yaml

RULINGS
1. Redaction: call redact_sensitive_text() on the worker input, gated on _redact_enabled().
   Raw-handle files contain the same redaction state Hermes sends to the provider. Dir 0700,
   files 0600, age-pruned (configurable, default 7 days).
2. Model tag: qwen3:4b-8k. Pin an input budget in plugin config (default 12000 chars).
3. Config: ~/.hermes/config.yaml. Back it up first (timestamped cp -a). Unit tests with mocked
   Ollama come BEFORE any live config change. Missing/invalid plugin config => mode off.
4. Commits: git init inside the plugin dir; local commits authorized; NO push/merge without
   approval. Report full 40-char SHAs.

ADDED CONSTRAINTS
F. Default-deny by command pattern. Process only: pytest, npm/vite/tsc/eslint/ruff/composer,
   docker/journalctl logs, background-process output. Never reduce read_file, file-edit, cat/sed/
   head/tail, or any output not matching the allowlist.
G. Preserve the tool result's JSON envelope (exit_code etc.). Replace only the output field.
   Required test: Hermes compaction's _sum_terminal still reads the exit code.
H. Idempotent: exactly one processing point per call; skip anything already carrying the worker
   marker. Required test proving a terminal call is not processed twice.
I. Thresholds: bypass < 6000 chars; send 6000-12000 to the model; > 12000 apply deterministic
   reduction first, send only if the result fits, otherwise raw. All three values configurable.
J. Concurrency: non-blocking lock; busy => raw, log status=skipped_busy. Shadow mode runs the
   worker in a background thread and returns raw immediately (no added turn latency).
   Active-mode timeout default 45s.
K. Memory preflight: query GET /api/ps; if the model is resident skip the check; otherwise require
   MemAvailable >= 4608 MiB (configurable) and report SwapFree. keep_alive cannot be set via
   /v1; Ollama's OLLAMA_KEEP_ALIVE (operator-applied) governs it. Do not modify Ollama service config.
L. v1 task scope: test output, build/lint output, logs. Diffs and search compression deferred.
M. Label injected text "LOCAL-WORKER REDUCTION", distinct from compaction summaries.
N. Extra tests: envelope preserved; no double processing; busy => raw; redaction runs before the
   Ollama call; raw file perms 0600/0700; allowlist miss => untouched passthrough.

Proceed to implementation: RED (tests first, show failing output), then GREEN, then shadow-mode demo
with `ollama ps` and `free -m` captured before/during/after. Do not enable active mode.
