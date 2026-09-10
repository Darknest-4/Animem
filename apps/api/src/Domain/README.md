# Domain layer

Pure business rules. Nothing in this directory may reference PDO, HTTP, PHP
sessions, the container, or any `Yume\Api\Infrastructure` / `Yume\Api\Presentation`
class. Dependencies point inwards only; that rule is enforced in CI by
`tests/Contract/LayerDependencyTest.php`.

## Implemented modules

| Module | Owns |
|---|---|
| `Shared` | Cross-module value objects (`IpAddress`, `UserAgent`) and the `DomainException` base |
| `User` | The user aggregate, identity value objects, user repository contract |
| `Auth` | Credentials, sessions, password and session policy |
| `Authorization` | Roles, permission slugs, the RBAC decision service |
| `Feature` | Feature flags and their rollout strategies |
| `Security` | Risk engine, detectors, bans, rate-limit policy, audit trail |

## Reserved modules

The directories below are scaffolded but empty. They are the migration targets
for the legacy `html/` code and are listed here so the module boundaries are
agreed before anyone writes the first line inside them.

| Module | Migrates from | Planned aggregate |
|---|---|---|
| `Profile` | `html/index.php` `siteProfil()` | `Profile` |
| `Anime` | `datasheet`, `mal__*` tables | `Anime`, `Genre`, `Studio` |
| `Episode` | `episodelist`, `ep_links` | `Episode`, `EpisodeLink` |
| `Watch` | `statistic__links` | `WatchSession`, `Progress` |
| `Library` | — (new) | `Watchlist`, `LibraryEntry` |
| `Community` | `mininews`, `uploaders` | `Fansub`, `Post`, `Comment` |
| `Notification` | — (new) | `Notification`, `Subscription` |
| `Achievement` | — (new) | `Achievement`, `Award` |
| `Admin` | `html/admin/` | Moderation actions |
| `Developer` | `html/api.php`, `zapi.php` | `ApiClient`, `ApiKey` |
| `Media` | `html/Assets/uploads` | `MediaAsset`, `Thumbnail` |

Each one gets its own migration, repository contract and handlers, behind a
feature flag, one module at a time.
