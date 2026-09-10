# Monitoring

Reserved. Nothing is wired up yet, and adding a Prometheus stack before there is
traffic to observe would be scaffolding for its own sake.

What already exists and should be consumed first:

| Signal | Source |
|---|---|
| Application logs | JSON lines on the container's stderr (`Yume\Shared\Logging\JsonLogger`) |
| Access logs | nginx `json` log format, container stdout |
| Slow requests | PHP-FPM `request_slowlog_timeout = 5s` |
| Slow queries | PostgreSQL `log_min_duration_statement = 500` (production overlay) |
| Readiness | `GET /health/ready` — reports per-dependency health and latency |
| Security events | `security_events` table, queryable by severity |

When metrics are added:

- `prometheus/` — scrape config; the API would expose `/metrics` behind
  `stats.view`, never publicly.
- `grafana/` — dashboards as provisioned JSON, in version control.

First four alerts worth having, in order of value:

1. `security_events` with `severity = 'critical'` appearing at all
2. `/health/ready` reporting `degraded` for more than 60 seconds
3. `jobs` rows with `failed_at IS NOT NULL` growing
4. Login failure rate rising sharply against the same subnet
