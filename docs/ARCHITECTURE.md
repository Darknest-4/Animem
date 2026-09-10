# Architecture

Why the Yume backend is shaped the way it is, and which decisions are load-bearing.

---

## 1. Modular monolith, not microservices

One deployable unit, with module boundaries drawn as if it were several.

The alternative — splitting an anime index into fifteen services on day one —
buys distributed tracing, eventual consistency and a deployment pipeline before
there is a single working feature. The alternative in the other direction is
what the legacy site became: a 3,967-line `index.php` with nineteen `if` blocks
and no boundary anywhere.

The boundary that matters is `src/Domain/<Module>`. Each module owns its
entities, value objects and repository *contracts*. Nothing crosses a module
boundary except through an interface. When `Media` or `Notification` eventually
justifies its own service, the seam is already cut: replace the repository
implementation with an HTTP client and the rest of the code does not notice.

---

## 2. The dependency rule

```
Presentation ──▶ Application ──▶ Domain ◀── Infrastructure
```

Arrows are compile-time dependencies. `Domain` imports nothing outward — not
PDO, not HTTP, not the container, not even `Yume\Database`. `Infrastructure`
depends on `Domain` because it implements `Domain`'s interfaces.

This is enforced, not documented: `tests/Contract/LayerDependencyTest.php`
scans every `use` statement and fails the build on a violation. It also fails on
`$_GET`/`$_SERVER`/`time()` inside `Domain`, because global state is what makes
business rules untestable.

The practical payoff: `PasswordPolicy`, `RiskEngine` and `FeatureFlag` are
tested with no database, no HTTP and no container — they are plain objects.

---

## 3. CQRS-shaped use cases

Each use case is a `Command` (state-changing) or `Query` (read-only) plus a
handler:

```
HTTP Request → Controller → Command → Handler → Domain → Repository interface
                                                              ↓
                                                        Infrastructure → PostgreSQL
```

Not full CQRS: there is one database, one model and no event sourcing. What is
borrowed is the *shape* — a use case is a named class with explicit inputs,
which means:

- a controller cannot accumulate business logic, because it has nowhere to put it;
- the same use case is reachable from HTTP, the CLI and the worker without
  duplication (`bin/console user:create` runs the same `PasswordPolicy` the HTTP
  endpoint does);
- the handler map in `bootstrap/providers.php` is a readable inventory of
  everything the application can do.

The bus map is explicit rather than convention-based. An unregistered command
throws at dispatch, which is loud, rather than being silently discovered.

---

## 4. Access control: deny by default

The legacy site's `perm()` function was called from templates. Templates control
what is *displayed*; typing the URL bypassed them entirely. Combined with an
`.htaccess` whose auth block was commented out, `/admin` was effectively open.

Here a route declares its own access rule, and `Router::compile()` — which runs
at boot — throws if any route omits it:

```php
$router->get('/api/v1/admin/users', [AdminUserController::class, 'index'])
    ->can('admin.access', 'user.view');
```

So the failure mode inverts. Forgetting a permission check no longer produces a
quietly open endpoint; it produces a container that will not start.

`AuthorizeMiddleware` is the only place that answers "may this actor do this?",
and it runs last in the pipeline, immediately before the controller. Controllers
contain no permission checks — a second check would be duplication that can
drift out of sync with the route declaration.

Permission slugs are `resource.action`. A role may be granted `anime.*` or `*`;
a route may never *require* a wildcard (`PermissionSlug::required()` refuses
one), so a route cannot accidentally ask for something every role satisfies.

---

## 5. Sessions

Server-side, with the client holding an opaque token:

- 32 random bytes, base64url; the plaintext is returned exactly once
- only its SHA-256 digest is stored, so a database dump yields no usable sessions
- absolute lifetime (30 days) and a sliding idle window (14 days)
- the token is rotated hourly while the session row — and its audit trail — survives
- a wholesale user-agent change revokes the session and records a
  `session.hijack_suspected` event; an IP change alone does not, because mobile
  networks change IP constantly
- concurrent sessions are capped; the oldest are revoked past the cap

Delivered both as an `HttpOnly; SameSite=Lax; Secure` cookie and in the response
body, so browsers and API clients use the same mechanism.

---

## 6. Password migration

The legacy `users.password` column holds unsalted single-round SHA-256, and the
plaintext was passed through `htmlspecialchars()` and `strip_tags()` before
hashing — silently mangling any password containing `<`, `>`, `&` or `"`.

Forcing a password reset on every account would lock out most of a ten-year user
base. Instead `user_credentials.password_algorithm` distinguishes `legacy_sha256`
from `argon2id`. On login, a legacy hash is verified once with `hash_equals()`,
then immediately replaced with Argon2id and audited as `password.upgraded`. No
user action, no reset email.

Accounts that never log in keep their legacy hash until an operator forces a
reset. That is a known, bounded exposure, recorded in the migration plan.

---

## 7. Security as a first-class domain

`Domain/Security` is a module, not a middleware folder:

```
Risk/        RiskEngine, RiskContext, RiskSignal, RiskScore, RiskAction, RiskDecision
Detection/   Bot, Automation, Proxy, Tor, Vpn detectors behind DetectorInterface
Ban/         Ban, BanScope, BanType, repository contract
RateLimit/   RateLimitPolicy, RateLimiterInterface, RateLimitResult
Audit/       SecurityEvent, SecurityEventType, audit repository contract
```

Detectors emit weighted signals; the engine sums them (capped at 100) and maps
the total onto a graded action:

```
allow → monitor → rate_limit → challenge → restrict → block
```

Three choices worth stating:

- **Additive, not multiplicative.** One noisy detector cannot on its own reach
  `block`; that takes corroboration.
- **Bans short-circuit.** An active ban is a decision already made, not another
  signal to weigh.
- **No I/O in the engine.** Detectors needing data get a repository with local
  storage. Nothing on the request path calls a third-party reputation API.

The same client is scored differently depending on target: `curl` reading
`/api/v1/features` scores 20 (monitor); the same `curl` posting to
`/api/v1/auth/login` scores 45 (rate_limit). Search crawlers are scored 5 — an
anime index wants to be indexed.

---

## 8. PostgreSQL for everything

No Redis, no Elasticsearch, no Kafka. The queue is a table with
`FOR UPDATE SKIP LOCKED`; rate-limit counters are a table with an atomic upsert;
network reputation is a table with a GiST index on `CIDR`.

At this traffic level each of those is correct and one fewer stateful service to
operate, back up and monitor. `RateLimiterInterface`, `QueueInterface` and
`CacheInterface` exist precisely so that a Redis implementation drops in when
measurements — not fashion — say it should.

Schema decisions:

- **UUIDv7 primary keys.** The leading timestamp keeps B-tree inserts
  append-only on the high-write tables. Sequential integers also leak volume and
  invite enumeration.
- **Canonical columns with unique indexes.** `username_canonical` and
  `email_canonical` are the real uniqueness keys. A `SELECT`-then-`INSERT` check
  races; the index does not. Separators are stripped from usernames, so
  `kitsune`, `Kit.Sune` and `kit-sune` cannot coexist and impersonate each other.
- **CHECK constraints on shapes.** The permission-slug regex lives in the schema
  as well as the value object, so a typo in a seed file cannot create an
  unreachable permission.
- **Partial indexes** on the hot filtered paths (`WHERE revoked_at IS NULL`,
  `WHERE lifted_at IS NULL`).

Migrations are forward-only SQL, applied inside a transaction, recorded in
`schema_migrations`. The schema is reproducible from the repository alone —
the single largest gap in the legacy project, whose ~37 tables existed only
inside the production database.

---

## 9. Feature flags as the migration mechanism

The legacy codebase contains four unfinished rewrites, gated by commented-out
code, a `$newSite` array and a hardcoded IP address. All of them are permanently
half-live because there was no way to ship one dark.

Flags make the strangler-fig migration mechanical. A rewritten page goes into
production at 0%, then 5%, then 50%, then 100% — each step a database update,
not a deploy. If it misbehaves, the rollback is a percentage change rather than
a revert.

Every flag carries an `expires_at`. A flag past its deadline logs a warning on
every evaluation but **keeps evaluating** — flipping behaviour under live traffic
because someone forgot to clean up would be worse than the stale flag. Once a
flag reaches 100% and settles, the old branch is deleted, not commented out.

---

## 10. Container topology

```
                        Internet
                           │
                    ┌──────▼──────┐
                    │    Caddy    │  TLS, ACME, HTTP/3
                    └──────┬──────┘        network: frontend
                    ┌──────▼──────┐
                    │    nginx    │  static, edge rate limit, FastCGI
                    └──────┬──────┘        networks: frontend + backend
                    ┌──────▼──────┐
                    │  PHP-FPM    │  non-root, read-only rootfs
                    └──────┬──────┘        network: backend (internal)
          ┌────────────────┼────────────────┐
   ┌──────▼──────┐  ┌──────▼──────┐  ┌──────▼──────┐
   │ PostgreSQL  │  │   worker    │  │   migrate   │
   └─────────────┘  └─────────────┘  └─────────────┘
```

`backend` is declared `internal: true`, so PostgreSQL, PHP-FPM and the worker
have no route to or from the internet. Only `nginx` bridges the two networks and
only Caddy publishes a port.

The worker shares the production image and changes only the entrypoint, so the
code that runs a job is byte-identical to the code that served the request that
enqueued it.

`migrate` runs once and exits. `app` and `worker` are deliberately **not** gated
on it: a failed migration should surface as a failed job and a degraded
readiness probe, not as a stack that never starts.

Liveness and readiness are separate. `/health` never touches the database — an
orchestrator that restarts every API container because PostgreSQL blipped turns
a degraded system into an outage. `/health/ready` does check dependencies, and
is what decides whether a container receives traffic.

---

## 11. Testing strategy

| Suite | Needs a database | Answers |
|---|---|---|
| `Unit` | no | Do the business rules hold? |
| `Contract` | no | Do the layer boundaries hold? |
| `Security` | no | Have the legacy vulnerabilities stayed fixed? |
| `Integration` | yes | Do the schema guarantees hold? |
| `Feature` | yes | Does the whole pipeline hold? |

The `Security` suite is the unusual one. Each test names a specific defect from
the review and asserts it cannot return: no interpolated SQL anywhere in `src/`
or `packages/`, no `mysqli_query`, a forged `userID` cookie granting nothing, a
client-supplied `X-Forwarded-For` being ignored, wrong-password and unknown-user
returning byte-identical responses.

`Integration` uses a real PostgreSQL and creates its schema by running the
*actual* migrations, so a migration that works in the test suite is one that
works in production.

---

## 12. Deliberate omissions

- **GraphQL and WebSocket** — directories exist, both are empty. Adding either
  before a client needs it would be speculative. The `graphql_api` flag is
  seeded at `off`.
- **Redis** — see §8.
- **An ORM** — repositories are hand-written SQL against `ConnectionInterface`.
  At this schema size a mapper costs more in surprise than it saves in typing,
  and hand-written SQL keeps the query plan visible.
- **A DI framework** — `Shared\Container` is ~150 lines of constructor
  autowiring. The composition root is two readable files.
- **Kubernetes** — Compose is sufficient for one host. The images are
  orchestrator-agnostic when that changes.

---

## 13. Migrating the legacy site

The strangler-fig sequence, one module at a time:

1. Dump the production schema, translate it into a migration, land it here.
2. Build the module's `Domain` + `Application` + repositories against it.
3. Expose the endpoints behind a flag at 0%.
4. Point the front end at them for the flagged cohort; widen the percentage.
5. At 100% and stable, delete the legacy path — delete, not comment out.

Order: `Anime` → `Episode` → `Community` (fansubs) → `Watch` → `Profile` →
`Admin`. Catalogue reads first, because they are the highest-traffic and
lowest-risk surface; admin last, because it is the highest-risk one.
