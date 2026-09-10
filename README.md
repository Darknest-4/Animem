# Yume

PHP 8.4 backend for the Animem/Yume anime index. Modular monolith, clean
architecture, CQRS use cases, PostgreSQL, Docker.

It lives alongside the original site (`html/`, `private/`, `Config/`), which is
still the thing serving traffic. Pages move over one module at a time, behind
feature flags. The review that motivated this work is in
[`docs/FEJLESZTESI-TERV.md`](docs/FEJLESZTESI-TERV.md); the design is in
[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

---

## Getting started

```bash
make up
```

That writes `.env` with generated secrets, builds the images, starts the stack,
waits for health, and applies migrations. Then:

```bash
make seed-admin USER=me EMAIL=me@example.org PASS='a long passphrase'
```

| Service | URL |
|---|---|
| API | http://localhost:8080/health |
| Adminer | http://localhost:8081 |
| Mailpit | http://localhost:8025 |

`make help` lists everything else. `make fresh` throws the database away and
starts over.

Requirements: Docker with Compose v2, GNU Make, `openssl`. Nothing else — no
local PHP, no local PostgreSQL.

### Production

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

Needs `DOMAIN` and `ACME_EMAIL` in `.env`; Caddy obtains and renews the
certificate. The production overlay adds resource limits, drops all
capabilities, mounts the root filesystem read-only, and publishes nothing but
80 and 443.

---

## Layout

```
apps/api/
  public/index.php     the only file below the web root
  bootstrap/           composition root: container, providers, routes
  src/
    Domain/            business rules — no PDO, no HTTP, no container
    Application/       use cases as Command / Query / Handler / DTO
    Infrastructure/    PDO repositories, queue, cache, rate limiter
    Presentation/      router, middleware pipeline, controllers
  config/              every getenv() call in the codebase lives here
  tests/               Unit · Contract · Security · Integration · Feature
  bin/console          migrations, users, roles, feature flags, routes
  bin/worker           queue consumer and housekeeping

packages/
  Contracts/           interfaces shared across modules
  Shared/              container, config, clock, ids, bus, logging
  Database/            PDO connection and the migrator
  Security/            Argon2id, opaque tokens, CSRF signing

infrastructure/
  docker/php           multi-stage Dockerfile, php.ini, php-fpm.conf
  docker/nginx         nginx.conf
  docker/worker        worker image
  caddy/               Caddyfile (TLS)
  postgres/init        first-boot SQL
  monitoring/          Prometheus and Grafana (reserved)
```

Dependencies point inwards: `Presentation → Application → Domain ← Infrastructure`.
That is not a convention, it is a test — `tests/Contract/LayerDependencyTest.php`
fails the build if `Domain` imports anything outward.

---

## How a request is handled

```
Caddy (TLS)  →  nginx  →  PHP-FPM  →  public/index.php  →  Kernel
```

The middleware pipeline, in order:

| # | Middleware | Responsibility |
|---|---|---|
| 1 | `ErrorHandler` | Nothing escapes; no stack trace ever reaches a client |
| 2 | `Cors` | Preflight, explicit origin allowlist |
| 3 | `SecurityHeaders` | CSP, HSTS, nosniff, frame-deny |
| 4 | `ClientIdentity` | Real client IP via trusted proxies; request id |
| 5 | `RouteResolver` | Match the route and its feature flag |
| 6 | `SecurityGate` | Bans and risk scoring, before expensive work |
| 7 | `RateLimit` | Per-identity budget, tightened by the risk decision |
| 8 | `Authenticate` | Session token → user, with rotation and sliding expiry |
| 9 | `Csrf` | Cookie-authenticated writes only |
| 10 | `Authorize` | Enforces the route's declared permissions |

The order is a security control and is asserted by
`tests/Security/MiddlewareOrderTest.php`.

Then the controller runs. Controllers validate shape, dispatch a command or
query, and serialise the result — they contain no business logic and no
permission checks.

---

## Access control

A route declares who may reach it, and the router refuses to boot if it does not:

```php
$router->get('/api/v1/admin/users', [AdminUserController::class, 'index'])
    ->can('admin.access', 'user.view');

$router->post('/api/v1/auth/login', [AuthController::class, 'login'])
    ->public()
    ->rateLimit('auth.login')
    ->withoutCsrf();
```

```
$ make routes
  GET    /health                                      -              public
  POST   /api/v1/auth/register                        auth.register  public
  POST   /api/v1/auth/login                           auth.login     public
  POST   /api/v1/auth/logout                          -              authenticated
  GET    /api/v1/auth/me                              -              authenticated
  GET    /api/v1/auth/sessions                        -              authenticated
  DELETE /api/v1/auth/sessions/{id}                   -              authenticated
  GET    /api/v1/features                             -              public
  GET    /api/v1/admin/users                          -              admin.access + user.view
```

Permissions are `resource.action` slugs. A role may hold `anime.*` or `*`; a
route may never *require* a wildcard. Roles ship seeded: `guest`, `user`,
`uploader`, `moderator`, `admin`.

---

## Feature flags

```php
if ($features->enabled('new_fansub_page', ['user_id' => $userId])) { … }
```

Resolution order: environment override (`FEATURE_NEW_FANSUB_PAGE=true`) →
database row → `false`. Strategies: `off`, `on`, `percentage`, `role`,
`user_list`, `ip_list`. Percentage bucketing is deterministic per flag and
actor, so widening a rollout never moves anyone *out* of the cohort.

```bash
make features
docker compose exec app php apps/api/bin/console feature:set --key=new_fansub_page --strategy=percentage --percentage=5
```

The flags seeded by migration `0006` correspond one-to-one with the
half-finished switches found in the legacy code — the commented-out blocks, the
`$newSite` array, the hardcoded IP in `html/Newindex.php:22`.

---

## Security

What the review found in the legacy site, and what replaces it:

| Legacy | Now |
|---|---|
| `setcookie("userID", $id)` as the whole session | Opaque token, SHA-256 digest stored, hourly rotation, sliding + absolute expiry |
| `WHERE id = {$_COOKIE["userID"]}` | Prepared statements everywhere; `tests/Security/SqlInjectionSurfaceTest.php` fails the build on any interpolated SQL |
| Unsalted SHA-256, plaintext passed through `htmlspecialchars()` first | Argon2id; legacy hashes upgraded silently on the owner's next login |
| `perm()` called from templates only | `AuthorizeMiddleware`, before the controller, on a declared permission |
| No CSRF token in any of 27 forms | HMAC double-submit, bound to the session |
| `getip()` trusting `X-Forwarded-For` from the client | `TrustedProxyResolver`, honouring the header only from configured proxies |
| No audit trail | Append-only `security_events` for every auth outcome, denial, ban and flag change |
| Credentials in four tracked JSON files | `.env` only; CI fails on a committed secret |
| `display_errors=1` and failing SQL printed to the browser | Off unconditionally; RFC 9457 `application/problem+json` out, JSON logs to stderr |

There is also a risk engine: detectors (bot, automation, proxy, Tor, VPN,
datacentre) produce weighted signals, and the total maps to one of
`allow → monitor → rate_limit → challenge → restrict → block`. Bans
short-circuit it. Thresholds are configuration.

---

## Development

```bash
make test              # everything
make test-unit         # no database needed
make test-security     # regressions for the vulnerabilities above
make stan              # PHPStan level 8
make lint / make fix   # PER-CS 2.0
make check             # what CI runs
```

Five suites, each with a job: **Unit** (pure logic), **Contract**
(architectural invariants), **Security** (regressions for specific past
vulnerabilities), **Integration** (repositories against real PostgreSQL),
**Feature** (end to end through the real Kernel).

Migrations are forward-only SQL files applied inside a transaction and recorded
in `schema_migrations`:

```bash
make migrate
make migrate-status
```

Add one by dropping `NNNN_name.sql` into
`apps/api/src/Infrastructure/Persistence/Migration/`.

---

## Status

Implemented: `User`, `Auth`, `Authorization`, `Feature`, `Security`.

Scaffolded, with the migration target for each documented in
[`apps/api/src/Domain/README.md`](apps/api/src/Domain/README.md): `Profile`,
`Anime`, `Episode`, `Watch`, `Library`, `Community`, `Notification`,
`Achievement`, `Admin`, `Developer`, `Media`.

The legacy tree is untouched and still serves production. Nothing here is wired
into it yet.
