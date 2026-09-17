# Data model

PostgreSQL holds the control plane. Check results start in PostgreSQL
too. Events are immutable; incidents and monitors are mutable state.

## Tenancy

``` text
users
  └── team_members (user_id, team_id, role)
        └── teams
              ├── projects
              │     └── monitors
              └── notification_channels
```

A user may belong to many teams. Every project, monitor, incident, and
channel is reached through a team. Authorization always starts with
"is this user a member of this team?"

Spatie Laravel Permission has an optional teams mode that uses `team_id`.
That is a later wiring decision. Do not assume it is on until you
enable it in `config/permission.php` *before* running that migration.

## PostgreSQL tables

### users

- id
- name
- email
- password_hash
- created_at
- updated_at

### teams

- id
- name
- owner_user_id
- created_at
- updated_at
- deleted_at (optional soft delete)

`owner_user_id` is the user who created the team, or who currently owns
it. Membership is not enough to know who is allowed to delete the team.

### team_members

- team_id
- user_id
- role (`owner` | `admin` | `member`)
- created_at

Unique `(team_id, user_id)`.

Foreign keys to `teams` and `users`. Cascade or restrict on delete —
pick one and keep it consistent.

### projects

- id
- team_id
- name
- description
- created_at
- updated_at

Unique `(team_id, name)` is a reasonable default.

### monitors

- id
- project_id
- name
- type (`http` for MVP)
- url
- method
- interval_seconds
- timeout_ms
- enabled
- expected_status
- last_checked_at (nullable)
- next_check_at (nullable, indexed)
- created_at
- updated_at

`next_check_at` is how the dispatcher finds due work:

``` sql
SELECT * FROM monitors
WHERE enabled = true
  AND next_check_at <= now()
ORDER BY next_check_at
LIMIT ...
```

Index: `(enabled, next_check_at)`.

`expected_status` is a shortcut for the common case (HTTP 200). The
worker should treat it as an implicit status assertion. Extra rules live
in `monitor_assertions` so you do not invent a second source of truth
for JSON/body checks.

### monitor_headers

- id
- monitor_id
- key
- encrypted_value

Do not store Authorization headers in plaintext.

### monitor_assertions

- id
- monitor_id
- type (`status` | `json` | `contains` | `latency`)
- field (nullable)
- operator (`equals` | `not_equals` | `contains` | `lt` | `gt`)
- expected_value

Examples:

- status equals 200
- JSON field `status` equals `ok`
- body contains `healthy`
- duration_ms less than 500

### alert_rules

- id
- monitor_id
- condition (`consecutive_failures` | `latency_above` | `availability_below`)
- threshold
- evaluation_window (nullable, for windowed conditions)
- severity
- enabled

MVP can start with consecutive-failure rules only.

### notification_channels

- id
- team_id
- type (`email` | `webhook`)
- configuration_encrypted
- enabled

Slack / Discord later, same table, new `type`.

### incidents

- id
- team_id (denormalized for listing/authorization)
- monitor_id
- status (`open` | `acknowledged` | `resolved`)
- started_at
- resolved_at
- failure_count
- last_error
- opened_by_result_id (nullable)
- resolved_by_result_id (nullable)

At most one non-resolved incident per monitor. Enforce that in the
application, and with a unique partial index if the database allows it:

``` text
unique (monitor_id) where status in ('open', 'acknowledged')
```

### check_results (MVP, PostgreSQL)

- id
- execution_id (unique)
- team_id
- monitor_id
- incident_id (nullable)
- checked_at
- status (`success` | `failure` | `error`)
- http_status (nullable)
- duration_ms
- dns_ms, connect_ms, tls_ms, ttfb_ms (nullable)
- response_bytes (nullable)
- error_type (nullable)
- assertion_failures (json, nullable)
- trace_id, span_id (nullable)

Index: `(monitor_id, checked_at desc)`.

Do not store full response bodies by default. If you need a snippet for
debugging, cap it (for example 2 KB) and keep it off the hot list query.

When this table becomes large, copy the same grain into ClickHouse and
stop writing history here. Keep a short hot window in Postgres or Redis
if the dashboard needs it.

## Check event (same grain after the move to ClickHouse)

Immutable. If a result was wrong, write another event.

``` json
{
  "monitor_id": 1,
  "execution_id": "…",
  "timestamp": "2026-09-16T12:00:00Z",
  "status": "success",
  "http_status": 200,
  "duration_ms": 183,
  "dns_ms": 8,
  "connect_ms": 22,
  "tls_ms": 31,
  "ttfb_ms": 96,
  "response_bytes": 842,
  "error_type": null,
  "trace_id": null
}
```

## Health vs incident status

These are different fields. Do not overload one enum.

**Monitor health** (derived from recent results, not stored as workflow):

- `healthy` — recent checks pass
- `degraded` — checks pass but latency/error rate is bad
- `down` — an open incident exists, or consecutive failures crossed the threshold

**Incident status** (stored, workflow):

``` text
HEALTHY (no incident)
   |
   | consecutive failures >= threshold
   v
OPEN
   |
   | user acknowledges
   v
ACKNOWLEDGED
   |
   | consecutive successes >= recovery threshold
   v
RESOLVED
```

Acknowledge is optional. Recovery still resolves automatically.

Example policy (tune later, pick numbers and put them on the alert rule):

- 1 failed check: write a failure result, do not open an incident
- 3 consecutive failures: open one incident, send one alert
- 2 consecutive successes: resolve the incident, send recovery

## Suggested indexes (MVP)

- `team_members (team_id, user_id)` unique
- `projects (team_id, name)` unique
- `monitors (enabled, next_check_at)`
- `monitors (project_id)`
- `check_results (monitor_id, checked_at desc)`
- `check_results (execution_id)` unique
- `incidents (monitor_id, status)`
