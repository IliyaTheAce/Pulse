# Implementation checklist

Use this against the **current milestone**, not as a guilt list of the
whole product. Uncheck items that belong to later milestones.

**Current milestone: 5 — History you can query.**
Milestones 1–4 (CRUD, worker, dispatcher) are done.

---

## Milestone 5 — History (current)

- [x] list results with time range + pagination
- [x] availability over `from`/`to`
- [ ] latency aggregation (avg / p95)
- [ ] `GET .../status` for the latest result
- [x] retention command (e.g. keep 14 days)
- [ ] indexes from the data model doc (stay on Postgres)

**Done when:** you can chart latency from the API without loading every
row in PHP. ClickHouse is out of scope until Postgres hurts.

---

## Later — do not start yet

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

- [ ] team isolation
- [ ] monitor validation
- [ ] probe timeout
- [ ] incident dedup (milestone 6)
