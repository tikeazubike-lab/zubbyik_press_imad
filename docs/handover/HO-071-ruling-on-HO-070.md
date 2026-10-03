HO-071 — Rulings on HO-070

1. RECONCILE (raw output required): HO-068 recorded `docker logs malachy-wp --tail 3000` collapsing
   750,959 -> 36,057 chars; HO-070 shows 752,863 chars / 3,000 distinct lines / no collapse.
   Run it again now and report distinct-line count. Then run
   `git -C ~/.hermes/plugins/local-worker diff e512fdcc0b84a91bbafc66067834136b7fc590a1 4d5f2509bec67b9ac1de0520f4e323d92c1fec27 -- worker.py`
   restricted to the collapse function, and state whether the log changed or the collapse
   behavior changed. If behavior regressed, fix it with a RED test first.

2. LLM tier shelved. Require `local_worker.llm_tier_enabled: true` (default false) for
   shadow/active; otherwise reject at config load, log config_error, behave as off.
   Add cgroup guard: read ollama.service memory.max (cgroup v2:
   /sys/fs/cgroup/system.slice/ollama.service/memory.max); if numeric and < required_model_mib,
   skip with status=skipped_cgroup_cap. Do not modify Ollama service config.
   Revert plugins.hook_callback_timeout to 30.

3. New mode `deterministic_shadow`: run the deterministic pipeline, log metrics, return None
   (raw passthrough). No thread needed, no network, no model. Do NOT enable `deterministic` active.
   Switch live mode to deterministic_shadow only AFTER the tests below pass and I acknowledge.

4. Signature-normalized grouping, docker/journalctl commands only, applied after lossless collapse
   and before selection. Normalize ONLY: ISO/syslog/apache timestamps, pid=N / [pid N], IPv4/IPv6,
   UUIDs, hex tokens >= 8 chars. Never normalize other numbers (ports, status codes, line numbers).
   Output per group: `xN, lines A-B | first: <verbatim> | last: <verbatim>`, groups ordered by
   first occurrence. Header must state "grouped by normalized signature; timestamps/pids/IPs of
   middle occurrences not shown", plus the raw path. Never applied to test/build output.
   Tests (RED first): distinct messages stay distinct (differing error codes, paths, ports);
   counts exact; ordering by first occurrence; a fixture shaped like the malachy-wp WP_DEBUG
   warning lines groups to one entry; error lines are never dropped.

5. Add tests/summarize_metrics.py (read-only): from metrics.jsonl report calls by status and
   task_type, sum of baseline_chars vs injected_chars, savings %, p50/p95 duration, and the
   skipped_too_large rate for lossless results.

6. Commit scope accepted. Local commits of this work authorized; NO push. Report full 40-char SHAs
   and `git status --porcelain`.

7. Out of scope here, report only: ghost-showcase crash loop; WP_DEBUG double-define warnings.
   Do not touch either.
