# Project definition

## 1. Purpose

Build a backend platform that lets a team register HTTP endpoints, run
checks on a schedule, store results, detect outages, and alert someone.

The project is a learning project that should still be something you
would run against your own APIs. Those two goals only work together if
the first version is a reliable loop, not a catalogue of infrastructure.

Backend skills this forces you to practice:

- queues and workers
- scheduled dispatch
- HTTP client timeouts, retries, and error classification
- PostgreSQL modeling and authorization
- Redis for jobs, locks, and short-lived cache
- time-series / event storage
- alerting without noise
- SSRF and tenant isolation
- Docker-based local development
- OpenTelemetry on *this* platform (later)

## 2. Core problem

A user should be able to say:

> Monitor `https://api.example.com/health` every 30 seconds. Alert me if
> it fails, becomes slow, or returns an unexpected response.

The platform should answer:

- Is the endpoint reachable from the probe?
- What is its availability over a time range?
- How long do requests take (and which phase is slow)?
- What status codes come back?
- Is this one blip or an outage?
- What changed around the incident?
- If the target speaks OpenTelemetry, can this check be tied to a trace?

## 3. Vocabulary

| Term | Meaning |
| --- | --- |
| **Team** | Tenancy root. Users belong to teams. Projects live under a team. |
| **Project** | A named group of monitors (for example `Production API`). |
| **Monitor** | The definition of one check: URL, method, interval, assertions. |
| **Probe** | The worker process that executes a check. |
| **Check result** | Immutable record of one execution. Never update it in place. |
| **Incident** | One outage window for a monitor. Many failed checks, one incident. |
| **Notification channel** | Email, webhook, later Slack/Discord, owned by a team. |

Tenancy tree:

``` text
User ──< team_members >── Team
                            │
                            ├── Projects
                            │      └── Monitors
                            │             ├── Assertions
                            │             ├── Alert rules
                            │             ├── Check results
                            │             └── Incidents
                            └── Notification channels
```

## 4. Two monitoring modes

### A. Black-box (MVP)

The platform calls the target itself. No code changes on the target.

``` text
Probe worker  --HTTP request-->  Target API
Probe worker  <--HTTP response-- Target API
Probe worker  --> check result
```

Collect at least:

- DNS, TCP, TLS, TTFB, total duration
- status code, response size, redirect count
- timeout / error reason
- assertion outcomes

### B. White-box (after the probe loop works)

The target is instrumented with OpenTelemetry and sends traces/metrics
to a collector. This answers *why* something is slow.

Do not treat Pulse as a general trace/log backend in the first versions.
First use of OTel in this project is:

1. instrument Pulse itself
2. inject `traceparent` on probe requests
3. store `trace_id` on the check result

Ingesting arbitrary application telemetry is a later product, not MVP.

## 5. What "done" means for the first useful version

A monitor can be created, runs by itself, survives a worker restart,
records success and failure, shows latency over time, opens one incident
for consecutive failures, resolves it on recovery, sends one alert, and
exposes history over an HTTP API. All of that runs locally via Docker
Compose.

## 6. Non-goals for MVP

Do not start with:

- Kubernetes operators
- Kafka
- multi-region probes
- ClickHouse (Postgres is fine until volume hurts)
- a Node probe worker (Laravel worker first)
- complex SLO math
- full log management
- browser automation
- mobile apps
- ingesting other people's traces as a platform
- machine-learning anomaly detection
- a monorepo split (`/api`, `/worker`) before a second process exists

Those are good later. They are a bad first month.

## 7. Success criteria

- create a team, a project, and a monitor
- execute the monitor automatically on its interval
- survive worker and Redis restarts without duplicate-storming
- record successful and failed checks
- show latency over time
- detect consecutive failures as one incident
- send an alert, then recover the incident automatically
- expose monitor history through an API
- refuse private/internal URLs (SSRF protection on the first worker)
- run the stack with Docker Compose
