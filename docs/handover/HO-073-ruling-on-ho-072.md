HO-073 — Rulings on HO-072

1. Budget: add local_worker.deterministic_budget_chars (default 24000), used only by the deterministic
   tiers. max_input_chars stays 12000 for the (shelved) LLM tier.
2. Group excerpts: for groups of 2+ occurrences cap first:/last: at 200 chars with an explicit
   "...[+N chars]" marker; singleton lines verbatim up to 500 chars (same marker beyond that).
   Header must state that excerpts are capped. Error-line preservation tests must still pass; add a
   RED test that capped excerpts never remove the error class/message identifying text (cap applies
   after the first 120 chars at minimum).
3. Hook gating: process only when env_type == "local"; otherwise passthrough with
   status=skipped_non_local. Test it. Also skip any output already containing the worker marker,
   and add a test that commands reading the raw-handle path (cat/grep/tail on ~/.hermes/local-worker)
   are never reduced.
4. deterministic_shadow disk behavior: do NOT write a raw handle per call. Keep an audit ring buffer:
   max 30 samples, each = redacted raw + the would-be injected text + metrics line, 0600/0700,
   retention 3 days, oldest evicted first. Cap total ~/.hermes/local-worker at 100 MB (evict raw/audit
   first) and rotate metrics.jsonl at 5 MB keeping 3 files. Add tests for the cap and eviction.
   Update summarize_metrics.py to read rotated files.
5. Switch live local_worker.mode to deterministic_shadow ONLY after the above tests pass; then show
   `grep -A12 '^local_worker:' ~/.hermes/config.yaml`, restart `hermes-gateway`, and demonstrate one
   real hook call in the live gateway writing a metrics line.
6. Add tests/audit_report.py (read-only): lists audit samples, and for each flags any line matching
   the error regex that appears in raw but not in the would-be injected text. Output a count of flagged samples.
7. Do not enable `deterministic` active. Review criteria for active: >=5 days, >=100 eligible calls,
   0 exceptions/fallback_error, p95 added latency < 250 ms, would-apply rate >= 20% of eligible,
   median saving >= 40% on applied calls, audit_report flags 0 samples. I will review results from
   summarize_metrics.py and audit_report.py output pasted by Malachy.
8. Commits: local only, NO push. Report full 40-char SHAs and `git status --porcelain` for both repos.
