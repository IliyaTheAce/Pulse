# System architecture

## 1. High-level picture

``` text
                    +-------------------+
                    |     Frontend      |
                    +---------+---------+
                              |
                              v
                    +-------------------+
                    |   Laravel API     |
                    |   Control plane   |
                    +----+---------+----+
                         |         |
                    PostgreSQL   Redis
                         |         |
                         |    queue / cache / locks
                         |         |
                         |         v
                         |   +-----------+
                         |   |  Workers  |
                         |   +-----+-----+
                         |         |
                         |         | HTTP checks
                         |         v
                         |   +-----------+
                         |   | Target API|
                         |   +-----------+
                         |
                         v
                  check results (Postgres first)
```

Later, when volume is real:

``` text
check results  -->  ClickHouse (or similar)
Pulse + targets -->  OTel Collector --> traces / metrics
```

## 2. Hard rule: two kinds of data

**Control plane (PostgreSQL)** answers: *what should the system do?*

Users, teams, projects, monitors, alert rules, incidents, API keys.

**Telemetry (events)** answers: *what happened?*

Check results, timings, assertion failures.

Do not dump millions of timestamped HTTP rows into the same tables that
store monitor configuration. For the first version, check results can
still live in PostgreSQL as their own table. Move them out when queries
on history start to fight writes on configuration.

## 3. Services

### Laravel API (control plane)

- REST API under `/api/v1`
- auth (Sanctum)
- team / project / monitor CRUD
- authorization
- dashboard queries
- dispatching "run now"
- scheduler process (find due monitors, enqueue jobs)

The API must not perform the outbound HTTP check itself on the request
path. `POST /monitors/{id}/run` enqueues a job and returns.

### Scheduler / dispatcher

Use Laravel's scheduler loop (`schedule:work` in development), not a
once-per-minute cron that also does HTTP.

Job of the dispatcher:

1. find monitors where `enabled = true` and `next_check_at <= now()`
2. take a per-monitor lock in Redis
3. enqueue one probe job
4. bump `next_check_at` (or let the worker do it — pick one place and
   document it in code)

Laravel's default cron (every minute) cannot honor a 30-second interval.
If monitors can be 30s, the dispatcher has to run every few seconds.

Never let the dispatcher issue the HTTP request.

### Probe worker (first implementation: Laravel)

A queued job that:

- loads the monitor snapshot from the job payload (not a stale in-memory copy)
- enforces timeout and response size limits
- blocks private/link-local/metadata IPs (SSRF)
- measures request phases
- evaluates assertions
- writes one immutable check result
- notifies the incident engine

A Node.js worker is a later optimization for high concurrency. It is not
required to learn the domain. If you add it, you cannot consume Laravel's
serialized PHP jobs. You need a language-agnostic payload on Redis
(JSON list, streams, or a dedicated queue). Design that contract before
writing Node.

### OpenTelemetry Collector (after the probe loop works)

Gateway only. Pulse API and workers send OTLP here. Optionally a target
app does too.

``` text
Laravel API  --\
Laravel worker --+--> OTel Collector --> backend
Target app  ---/
```

The collector is receivers → processors → exporters. It is not a
database. You still need a place to store traces.

## 4. Storage

| Data | Store |
| --- | --- |
| Users, teams, membership | PostgreSQL |
| Projects, monitors, assertions | PostgreSQL |
| Alert rules, notification channels | PostgreSQL |
| Incidents | PostgreSQL |
| Check results (MVP) | PostgreSQL |
| Check results (later, high volume) | ClickHouse |
| Recent status cache | Redis |
| Queued jobs | Redis (or database queue while learning) |
| Dispatcher locks, dedup keys | Redis |
| Traces | dedicated backend later |
| Metrics | Prometheus-compatible later |
| Raw response archives | object storage much later |

Do not use Redis as the source of monitoring history.

## 5. Probe job payload

Even for a PHP worker, treat the job as a snapshot so a config edit mid-flight
does not change an in-flight check:

``` json
{
  "execution_id": "uuid",
  "monitor_id": 1,
  "team_id": 1,
  "url": "https://api.example.com/health",
  "method": "GET",
  "timeout_ms": 5000,
  "headers": [],
  "body": null,
  "expected_status": 200,
  "assertions": [],
  "traceparent": "00-..."
}
```

`execution_id` is the idempotency key. If the same job is delivered twice,
do not insert two check results.

## 6. Incident engine (belongs next to the worker, not in the HTTP controller)

After each result:

- if consecutive failures >= threshold and no open incident → open one
- if consecutive successes >= recovery threshold and an open incident exists → resolve it
- never open a new incident for every failed request

Alerting fires on incident open / acknowledge / resolve, not on every
failed check.

## 7. Security boundary

The probe is an HTTP client that users can point at any URL they type.
That is the most dangerous component in the system.

Before the first real outbound request:

- deny private, loopback, link-local, and cloud metadata ranges
- resolve DNS and check the *resolved* IPs (not just the hostname)
- cap redirects and re-check each hop
- cap request and response size
- encrypt header secrets at rest

SSRF protection is part of the worker, not a later hardening sprint.
