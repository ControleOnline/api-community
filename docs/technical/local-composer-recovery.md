# Local Composer recovery — api-community#381

## Reviewed package composition

- Keep `controleonline/common` at 1.0.12. Compared with 1.0.11, it preserves
  the team's email connection support, default connection status, idempotent
  connection migrations, and per-environment cron/WebSocket handling.
- Use published `controleonline/orders` 1.0.12, commit
  `d5bf12bca7a046ff2f7c37a761b90659f988599a`. Compared with 1.0.11, only
  `composer.json` changes: common 1.0.11 becomes ^1.0.11. Queue stays 1.0.3;
  all orders source and tests are unchanged.
- Keep published `controleonline/tasks` 1.0.7. That archive declares
  `Task::setAlterDate()` twice and cannot be loaded by PHP. The install/update
  hooks apply `scripts/patch-tasks-date-setter.php` before Symfony auto-scripts.

## Temporary tasks patch

The patch removes only the six-line duplicate setter, retaining the original
public setter, getter and all AI fields. SHA-256 checks require the exact
reviewed input or already-patched output; unexpected upstream changes stop
installation rather than silently modifying another package. Repeated installs
are safe. No custom package repository, dependency alias, development branch,
or unpublished package version is required by the application.

The upstream fix is prepared locally at `.composer/tasks-release`, commit
`9d40afd`. GitHub denied publication with the connected account, which has
read-only permission on `ControleOnline/api-platform-tasks`. A maintainer must
review and publish a corrected stable tasks package. After publication, update
the API pin and remove this patch and its two Composer hooks together.

## Running locally

Run `composer install`, then `php -S localhost:8000 -t public` from the API
directory. The local lock is retained for repeatable installs but remains
ignored according to the existing repository policy. Environment files and
credentials are preserved. This recovery does not run database migrations or
deploy to remote application environments.

`php bin/console about` verifies kernel initialization. An empty JSON POST to
`/token` should return HTTP 400 for the missing username, rather than an
autoload or duplicate-method fatal error. This check does not authenticate a
real user or write application data.

The full backend suite and the waiter suite are not clean baseline checks in
this composition: the backend run terminates during an existing MCP test, and
the waiter configuration references an absent financial test. Report focused
validation separately; do not represent these suites as passing.
