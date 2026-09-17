# Implementation checklist

Use this against the **current milestone**, not as a guilt list of the
whole product. Uncheck items that belong to later milestones.

## Repo / environment

- [x] `.env.example` and a documented boot command
- [x] PostgreSQL
- [ ] Redis
- [x] Laravel app at repo root (do not split `/api` until a second process exists)
- [x] `/docs` kept in sync with naming (`Team`)
- [ ] README describes Pulse, not stock Laravel

## Tenancy

- [x] users
- [x] teams
- [ ] team_members (unique pair, role, creator attached on create)
- [x] projects belong to a team
- [x] policies actually used by controllers
- [x] user A cannot read user B's team

## Monitors

- [ ] monitor CRUD
- [ ] assertions
- [ ] encrypted headers
- [ ] `next_check_at` / `last_checked_at`
- [ ] enable / disable
- [ ] interval and timeout validation

## Queue / probe

- [ ] queue connection
- [ ] job payload with `execution_id`
- [ ] timeout
- [ ] SSRF / private IP blocking
- [ ] response size limit
- [ ] error classification
- [ ] idempotent writes
- [ ] retries / backoff / dead-letter (after the first happy path)

## Dispatcher

- [ ] due-monitor query
- [ ] per-monitor lock
- [ ] disabled monitors skipped
- [ ] interval honored (including 30s if you promise 30s)

## Storage

- [ ] check_results in PostgreSQL
- [ ] indexes from the data model
- [ ] availability and latency queries
- [ ] retention command
- [ ] ClickHouse only after Postgres hurts

## Incidents / alerts

- [ ] failure and recovery thresholds
- [ ] one open incident per monitor
- [ ] acknowledge
- [ ] webhook or email
- [ ] notify on incident, not on every check

## OpenTelemetry (later)

- [ ] Laravel + worker instrumentation
- [ ] collector in compose
- [ ] `traceparent` on probes
- [ ] `trace_id` on check results

## Security

- [ ] SSRF on first outbound request
- [ ] tenant scope on every query
- [ ] encrypted secrets
- [ ] rate limits
- [ ] audit log (before multi-user deploy)

## Tests worth writing as you go

- [ ] auth
- [ ] team isolation
- [ ] monitor validation
- [ ] probe timeout
- [ ] probe SSRF (blocked IP)
- [ ] duplicate `execution_id`
- [ ] incident dedup
- [ ] dispatcher lock
