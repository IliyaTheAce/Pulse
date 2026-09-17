# Features and API

All HTTP APIs below are under `/api/v1` and use Sanctum unless noted.

List endpoints should paginate. Do not return unbounded result history.

## MVP API

### Authentication

``` http
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/logout
GET  /api/v1/auth/user
```

### Teams

``` http
GET    /api/v1/teams
POST   /api/v1/teams
GET    /api/v1/teams/{team}
PATCH  /api/v1/teams/{team}
DELETE /api/v1/teams/{team}
```

Membership can wait until after basic CRUD, but a created team with no
member (not even the creator) is an authorization hole. Creating a team
should attach the current user.

### Projects

``` http
GET    /api/v1/projects
POST   /api/v1/projects
GET    /api/v1/projects/{project}
PATCH  /api/v1/projects/{project}
DELETE /api/v1/projects/{project}
```

`POST` / `PATCH` take `team_id`. The user must be a member of that team.

### Monitors

``` http
GET    /api/v1/projects/{project}/monitors
POST   /api/v1/projects/{project}/monitors
GET    /api/v1/monitors/{monitor}
PATCH  /api/v1/monitors/{monitor}
DELETE /api/v1/monitors/{monitor}
POST   /api/v1/monitors/{monitor}/run
POST   /api/v1/monitors/{monitor}/enable
POST   /api/v1/monitors/{monitor}/disable
```

`POST .../run` enqueues a job. It does not wait for the HTTP check.

### Results

``` http
GET /api/v1/monitors/{monitor}/results
GET /api/v1/monitors/{monitor}/status
GET /api/v1/monitors/{monitor}/availability
GET /api/v1/monitors/{monitor}/latency
```

`status` is the cheap dashboard call: latest result, current health,
open incident if any.

Query params for history: `from`, `to`, `cursor` or `page`.

### Incidents

``` http
GET  /api/v1/incidents
GET  /api/v1/incidents/{incident}
POST /api/v1/incidents/{incident}/acknowledge
```

Filter by `team_id`, `project_id`, `monitor_id`, `status`.

### Notification channels

``` http
GET    /api/v1/notification-channels
POST   /api/v1/notification-channels
PATCH  /api/v1/notification-channels/{channel}
DELETE /api/v1/notification-channels/{channel}
```

Owned by a team.

## Monitor configuration

``` json
{
  "name": "Production Health",
  "type": "http",
  "url": "https://api.example.com/health",
  "method": "GET",
  "interval_seconds": 30,
  "timeout_ms": 5000,
  "expected_status": 200,
  "assertions": [
    {
      "type": "json",
      "field": "status",
      "operator": "equals",
      "expected": "ok"
    }
  ]
}
```

Validate:

- `interval_seconds` minimum (do not allow 1s on MVP unless you want to
  DoS both Pulse and the target)
- `timeout_ms` less than `interval_seconds`
- URL is https (or http only in local/dev)
- method is one of GET/POST/PUT/PATCH/DELETE/HEAD

## Features by version

### MVP

- HTTP methods GET/POST/PUT/PATCH/DELETE/HEAD
- headers (encrypted at rest)
- optional request body
- status + JSON + latency assertions
- timeout
- latency history
- availability over a time range
- incidents with consecutive-failure threshold
- email or webhook (one channel type is enough to learn)
- manual run
- enable / disable
- SSRF protection on the probe
- tenant isolation (team membership)

### Version 2

- DNS / TCP / TLS certificate expiry checks
- maintenance windows
- heartbeat (target pings Pulse)
- tags
- API keys for CI
- Slack / Discord
- public status page
- SLA/SLO on a monitor
- escalation policies
- multiple probe regions

### Version 3

- browser / synthetic transactions
- distributed tracing UI
- anomaly detection
- deployment correlation
- Kubernetes service discovery
- Node (or Go) high-concurrency worker

## Security (not optional on the first worker)

A monitor that can request arbitrary URLs is an internal-network scanner
with a nicer UI.

Must exist before the probe hits the network:

- block loopback, private, link-local, and cloud metadata IPs
- resolve DNS, then check the resolved addresses
- re-validate on every redirect hop
- request and response size limits
- timeout always set
- encrypted secrets
- team isolation on every query (no `Monitor::find($id)` without a team
  scope)
- rate limits on auth and on `POST .../run`

Add later, still before any multi-user deploy:

- audit log of config changes
- API keys with scoped permissions
- DNS rebinding protection tests you can actually run
