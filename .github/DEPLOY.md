# Deploy (api-community)

**Um único workflow:** `.github/workflows/deploy.yml`

| Step | Job | Função |
|------|-----|--------|
| 1 | `configure` | Mapeia branch → path, domínio, auth, bootstrap |
| 2 | `deploy` | SSH + git/submodules/composer/migrations (usa outputs do step 1) |
| 3 | `tests` | PHPUnit após deploy |

## Triggers

- `push` em `dev` | `staging` | `master`
- `workflow_dispatch` com escolha do target

## Config por ambiente (step 1)

| env | branch | remote path | messenger domain | auth | secrets |
|-----|--------|-------------|------------------|------|---------|
| dev | dev | `/var/www/api-community-dev` | d.controleonline.com | password | `DEV_HOST`, `DEV_USER`, `DEV_PASS` |
| staging | staging | `/var/www/api-community` | s.controleonline.com | SSH key + passphrase | `STAGING_HOST`, `STAGING_USER`, `STAGING_KEY`, `STAGING_PASS` |
| master | master | `~/sistemas/controleonline/api` | api.controleonline.com | key | `API_HOST`, `USER`, `CONTROLEONLINE`, `PORT` |

GitHub **Environments** usados: `dev`, `staging`, `production` (protection rules opcionais).

## Regras

1. Staging **não** usa secrets `DEV_*`.
2. Cada deploy sincroniza **só** `origin/<branch>` no parent e nos submodules.
3. **LaveGo** (whitelabel) fica fora deste fluxo — ver `deploy-api-lavego-*` / `deploy-apinew-*` (`workflow_dispatch`).

## Composer modules

- `composer.json` pins every `controleonline/*` production package to an exact stable version; `composer.lock` records the exact registry artifact and commit.
- `scripts/validate-composer-release-pins.php` checks that each requirement, lock entry, and API submodule gitlink names the same version and commit. CI and deploy fail on a missing or mismatched pin; deploy never runs `composer update` to repair a stale lock.
- `dev` installs the locked packages and then redirects Composer autoloading to the local `modules/controleonline` checkouts for source development.
- `staging` and `master` install with `--no-dev --no-scripts`; autoloading stays under `vendor/controleonline` and the local-source rewrite is skipped.
- To publish a module, merge its task changes into `dev`, have Security review them, tag the approved commit with its stable `vX.Y.Z` release, then update the API requirement, lock entry, and module gitlink together.

## Secrets

Settings → Secrets and variables → Actions (repo ou org):

- Dev: `DEV_HOST`, `DEV_USER`, `DEV_PASS`
- Staging: `STAGING_HOST`, `STAGING_USER`, `STAGING_KEY` (private key), `STAGING_PASS` (key passphrase)
- Production: `API_HOST`, `USER`, `CONTROLEONLINE`, `PORT`
