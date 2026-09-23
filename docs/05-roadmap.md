# Build roadmap

Work top to bottom. A milestone is not done because the files exist. It
is done when the definition of done is true.

The Laravel app already lives at the repo root. Keep it there until you
actually have a second process. Do not spend a milestone on a monorepo
move.

---

## Milestone 0 — Local environment

**Goal:** `docker compose up` (or an equivalent documented command)
starts the things you need this week.

**Build:**

- repository and `.env.example`
- PostgreSQL
- Redis
- Laravel app container or a documented PHP + compose database setup
- health endpoint (`/up` is already there)

**Do not build yet:** Node worker, OTel collector, frontend container,
ClickHouse.

**Done when:** a new clone can boot the API and talk to Postgres and
Redis without tribal knowledge.

---

## Milestone 1 — Auth, teams, projects

**Goal:** a logged-in user can own a team and put a project in it.

**Build:**

- register / login / current user
- `teams` and `team_members`
- `projects` belong to a team
- policies: you only see teams you belong to
- creating a team attaches the creator as owner
- seed a user + team for local use

**Design notes:**

- Tenancy name is **Team**. Spatie Permission's `teams` flag is a
  separate switch. Leave it off until you decide to scope roles by team,
  and if you turn it on, do it before that migration (or write a new
  one).
- `owner_user_id` on `teams` plus a row in `team_members` is clearer
  than membership-only ownership.

**Done when:** user A cannot GET/PATCH user B's team or project.

**Out of scope:** API keys, invites, billing, roles beyond owner/admin/visitor.

---

## Milestone 2 — Monitor CRUD

**Goal:** a user can describe an HTTP check completely, without running it.

**Build:**

- `monitors` table including `interval_seconds`, `timeout_ms`,
  `enabled`, `expected_status`, `next_check_at`
- headers (encrypted) and assertions
- validation (interval floor, timeout < interval, method allow-list)
- enable / disable endpoints
- nested routes under the project

**Done when:** you can create, edit, disable, and fetch a monitor via
the API, and secrets are not stored in plaintext.

**Out of scope:** actually hitting the URL.

---

## Milestone 3 — First worker

**Goal:** a manually dispatched monitor produces one check result.

**Build:**

- queue connection (Redis preferred, database queue acceptable to learn)
- job payload with `execution_id`
- HTTP client with a hard timeout
- error classification (dns / tls / timeout / http / assertion)
- write `check_results`
- `POST /monitors/{id}/run` only enqueues

**Must ship in this milestone (not later):**

- SSRF: block private / loopback / link-local / metadata IPs after DNS
- response size cap
- timeout always set

**Worker language:** Laravel job first. Node is a later milestone if
concurrency becomes the lesson you want.

**Done when:** `POST .../run` plus a worker process yields a row in
`check_results` for both a healthy URL and a timeout URL.

**Out of scope:** scheduler, incidents, ClickHouse, phase timing
perfection. Recording `duration_ms` is enough; DNS/TLS/TTFB can be
null until the client can measure them.

---

## Milestone 4 — Dispatcher

**Goal:** enabled monitors run themselves on their interval.

**Build:**

- a scheduled command that selects `enabled = true AND next_check_at <= now()`
- Redis lock per monitor so two dispatchers cannot double-enqueue
- skip disabled monitors
- set `next_check_at` in exactly one place (dispatcher *or* worker)
- run the scheduler loop often enough for 30s intervals
  (`schedule:work`, not "cron every minute" if 30s is a real requirement)

**Done when:** a monitor with `interval_seconds = 30` produces results
while you do nothing, and disabling it stops new jobs.

**Out of scope:** multi-region, priority queues.

---

## Milestone 5 — History you can query

**Goal:** the API can answer "uptime and latency for this monitor."

**Build:**

- list results with time range + pagination
- availability over `from`/`to`
- latency aggregation (avg / p95 is enough)
- `GET .../status` for the latest result
- a retention idea (even "keep 14 days" as a command)

Stay on PostgreSQL. Add indexes from the data model doc.

**Done when:** you can chart latency from the API without loading every
row in PHP.

**Out of scope:** ClickHouse. Move only when this table is actually
painful.

---

## Milestone 6 — Incident engine

**Goal:** one outage equals one incident, not one incident per check.

**Build:**

- consecutive failure threshold
- consecutive success recovery threshold
- open / acknowledge / resolve
- at most one open incident per monitor
- incident list API scoped by team

Put this next to the worker (a domain service called after each result),
not inside the HTTP controller.

**Done when:** 50 failed checks in a row create a single open incident;
two successes resolve it.

**Out of scope:** escalation policies, on-call rotations.

---

## Milestone 7 — Notifications

**Goal:** opening and resolving an incident notifies a human.

**Build:**

- one channel type that you can actually test (webhook is easier than
  email locally)
- encrypt channel config
- send on incident open and resolve
- retry with backoff; do not retry forever
- do not notify on every failed check

**Done when:** a webhook endpoint you control receives open + resolve
once per outage.

**Out of scope:** Slack, Discord, fancy templates, SMS.

---

## Milestone 8 — Dashboard

**Goal:** you can see current status without using curl.

**Show:**

- monitor list with health
- uptime and latency for one monitor
- open incidents
- recent failures
- a response-time chart
- the monitor's config

Pick one UI stack and keep it thin. This milestone is to prove the API,
not to design a design system.

**Done when:** a stranger can tell whether a monitor is down in under
five seconds.

---

## Milestone 9 — OpenTelemetry on Pulse

**Goal:** you can follow one check through API → queue → worker →
outbound HTTP.

**Build:**

- instrument Laravel API, worker, DB, HTTP client
- run one OTel collector locally
- inject `traceparent` on probe requests
- store `trace_id` on the check result
- a link in the UI if you have a trace backend

**Done when:** a failed check in the dashboard has a trace id you can
open.

**Out of scope:** ingesting other applications' traces as a product,
a custom trace UI, log pipelines.

---

## Milestone 10 — Hardening beyond the probe

The probe already had SSRF. This milestone is everything else you
skipped while learning:

- rate limits
- audit log of monitor/team changes
- tighter DNS-rebinding tests
- tenant isolation review (every query scoped)
- secret rotation story
- max body sizes at the HTTP layer too

**Done when:** you would let a second person use a deployed instance
without cringing.

---

## Milestone 11 — Reliability drills

Break it on purpose:

- kill the worker mid-check
- restart Redis
- restart Postgres
- target timeout / DNS failure / TLS failure
- duplicate job delivery (`execution_id` must hold)
- delayed jobs
- notification endpoint down

Then add: backoff, dead-letter, health checks, idempotency if a drill
failed.

**Done when:** you have notes (even a short runbook) for each failure,
not just green tests on the happy path.

---

## Milestone 12 — Production-shaped deploy (later)

- reverse proxy + HTTPS
- separate containers for API, worker, scheduler
- backups
- resource limits
- monitor Pulse with Pulse (or a dead-simple external ping)
- only then ClickHouse / object storage / extra regions

---

## Suggested sequence reminder

``` text
env → team/project → monitor CRUD → worker+SSRF → dispatcher
    → history → incidents → one alert → dashboard
    → OTel → extra hardening → chaos
```

If you skip worker+SSRF and jump to OTel or a Node rewrite, you are
collecting infrastructure instead of building a monitoring app.
