# Pulse

HTTP uptime and latency monitoring API built with Laravel.

Pulse lets a team register endpoints, run scheduled black-box checks, store immutable results, and (soon) open incidents and send alerts. A dashboard UI is planned; today the product surface is a Sanctum-authenticated REST API under `/api/v1`.

## Purpose and goal

**Purpose:** give teams a reliable probe loop for their own APIs — create a monitor, have workers check it on an interval, record success/failure and timings, and expose history over HTTP.

**Goal for v1:** a trustworthy local stack where you can:

- create a team, project, and monitor (with headers and assertions)
- run checks automatically via the scheduler + queue
- survive worker restarts without duplicate storms
- list results through the API
- keep tenants isolated (team membership + monitor tenant scope)
- refuse private/internal probe URLs (SSRF guard)

More product detail lives in [`docs/`](docs/).

## Stack

- PHP 8.3+ / Laravel 13
- PostgreSQL (control plane + check results for MVP)
- Redis (queues / locks)
- Laravel Sanctum (API tokens)
- Spatie Laravel Permission (global roles; team roles live on `team_members`)

## Tenancy and roles

```text
User ──< team_members.role >── Team
                                 └── Project
                                       └── Monitor
                                             ├── Headers
                                             ├── Assertions
                                             └── Results
```

Team membership roles on `team_members.role`:

| Role | Read | Change team / projects / monitors |
| --- | --- | --- |
| `owner` | yes | yes (only owners can delete the team) |
| `admin` | yes | yes |
| `visitor` | yes | no |

Monitor queries are tenant-scoped: authenticated `Monitor::query()` only returns monitors for teams the user belongs to. The scheduler and queue workers run without a user, so they stay unscoped.

## Install

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` for Postgres and Redis (defaults target Docker-style hostnames `postgres` / `redis`). Then:

```bash
php artisan migrate
php artisan db:seed
```

Optional demo users from the seeders:

| Email | Password | Notes |
| --- | --- | --- |
| `i2007f2007@gmail.com` | `password` | Owner of **Pulse Demo** |
| `admin@pulse.test` | `password` | Admin on **Pulse Demo** |
| `visitor@pulse.test` | `password` | Visitor (read-only) on **Pulse Demo** |

## Run locally

API + queue (and Vite if you add a frontend later):

```bash
composer run dev
```

Or separately:

```bash
php artisan serve
php artisan queue:work
php artisan schedule:work
```

The scheduler dispatches due monitors every 15 seconds (`enabled = true` and `next_check_at <= now()`). The composite index `(enabled, next_check_at)` backs that query.

## Use the API

Base path: `/api/v1`

1. Register or log in:

```http
POST /api/v1/auth/register
POST /api/v1/auth/login
```

2. Create a team (caller becomes `owner`), then a project, then a monitor:

```http
POST /api/v1/teams
POST /api/v1/projects
POST /api/v1/projects/{project}/monitors
```

Monitors accept optional `headers` and `assertions` arrays.

3. List / inspect (visitors allowed):

```http
GET /api/v1/monitors
GET /api/v1/monitors/{monitor}
GET /api/v1/monitors/{monitor}/results
```

4. Mutate (owners/admins only; visitors get 403):

```http
PUT    /api/v1/teams/{team}
PUT    /api/v1/projects/{project}
PUT    /api/v1/monitors/{monitor}
POST   /api/v1/monitors/{monitor}/run
POST   /api/v1/monitors/{monitor}/enable
POST   /api/v1/monitors/{monitor}/disable
DELETE /api/v1/monitors/{monitor}
```

`POST .../run` enqueues a probe job; it does not wait for the HTTP check.

Full endpoint notes: [`docs/03-api-and-features.md`](docs/03-api-and-features.md).

## Factories and seeders

```bash
php artisan db:seed
# or individually:
php artisan db:seed --class=TeamSeeder
php artisan db:seed --class=ProjectSeeder
php artisan db:seed --class=MonitorSeeder
```

Factories:

- `TeamFactory` — creates an owner membership
- `ProjectFactory`
- `MonitorFactory` — `->withHeaders()`, `->withAssertions()`, `->fullyConfigured()`
- `MonitorHeaderFactory` / `MonitorAssertionFactory`

## Tests

```bash
composer test
# or
php artisan test
```

## Roadmap (short)

- Incident open/resolve + notifications
- Dashboard UI (planned)
- OpenTelemetry on Pulse itself and `traceparent` on probes
- Multi-region probes / colder result storage later

See [`docs/05-roadmap.md`](docs/05-roadmap.md).

## License

MIT
