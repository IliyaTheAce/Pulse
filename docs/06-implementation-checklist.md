# Implementation checklist

Use this against the **current milestone**, not as a guilt list of the
whole product. Uncheck items that belong to later milestones.

**Current milestone: 4 — Dispatcher.** Milestone 3 (first worker) is done.

---

## Milestone 3 — First worker (done)

- [x] Redis queue connection (`QUEUE_CONNECTION=redis`, worker container)
- [x] `POST /monitors/{id}/run` only enqueues
- [x] Job payload snapshot + `execution_id` at dispatch
- [x] HTTP timeout in seconds + job `$timeout` buffer
- [x] SSRF after DNS (loopback / private / link-local / metadata)
- [x] Redirect hops re-checked
- [x] Response size cap (1 MB)
- [x] Error classification (`dns` / `tls` / `timeout` / `http` / `assertion` / `connection`)
- [x] Assertions + implicit `expected_status`
- [x] `monitor_results` write with unique `execution_id`
- [x] Idempotent `firstOrCreate` on `execution_id`
- [x] `last_checked_at` updated by the worker
- [x] Encrypted header values (not hashed)

---

## Milestone 4 — Dispatcher (current)

- [x] Due-monitor query (`enabled = true AND next_check_at <= now()`)
- [x] Disabled monitors skipped
- [x] Interval honored (`everyFifteenSeconds` + `interval_seconds`)
- [x] `next_check_at` written in one place (dispatcher)
- [x] Enable sets `next_check_at` when it is null
- [ ] Per-monitor Redis lock (two ticks / two schedulers must not double-enqueue)
- [ ] Confirm disable stops new jobs while a worker is running

---

## Still open from earlier milestones

- [ ] README describes Pulse, not stock Laravel
- [ ] `team_members.role` (`owner` | `admin` | `member`)
- [ ] Tenant scope on every monitor query (`Monitor::query()` index is unscoped)
- [ ] `monitors (enabled, next_check_at)` composite index

---

## Later — do not start yet

### History (milestone 5)

- [ ] list results with time range + pagination
- [ ] availability and latency queries
- [ ] `GET .../status`
- [ ] retention command
- [ ] ClickHouse only after Postgres hurts

### Incidents / alerts (milestones 6–7)

- [ ] failure and recovery thresholds
- [ ] one open incident per monitor
- [ ] acknowledge
- [ ] webhook or email
- [ ] notify on incident, not on every check
- [ ] retries / backoff / dead-letter

### OpenTelemetry (milestone 9)

- [ ] Laravel + worker instrumentation
- [ ] collector in compose
- [ ] `traceparent` on probes
- [ ] `trace_id` on check results

### Hardening (milestone 10)

- [ ] rate limits
- [ ] audit log (before multi-user deploy)

---

## Tests worth writing as you go

- [x] auth (Breeze feature tests)
- [x] probe SSRF (blocked IP)
- [x] duplicate `execution_id`
- [ ] team isolation
- [ ] monitor validation
- [ ] probe timeout
- [ ] dispatcher lock
- [ ] incident dedup (milestone 6)
