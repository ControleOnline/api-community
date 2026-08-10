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
| staging | staging | `/var/www/api-community` | s.controleonline.com | password | `STAGING_HOST`, `STAGING_USER`, `STAGING_PASS` |
| master | master | `~/sistemas/controleonline/api` | api.controleonline.com | key | `API_HOST`, `USER`, `CONTROLEONLINE`, `PORT` |

GitHub **Environments** usados: `dev`, `staging`, `production` (protection rules opcionais).

## Regras

1. Staging **não** usa secrets `DEV_*`.
2. Cada deploy sincroniza **só** `origin/<branch>` no parent e nos submodules.
3. **LaveGo** (whitelabel) fica fora deste fluxo — ver `deploy-api-lavego-*` / `deploy-apinew-*` (`workflow_dispatch`).

## Secrets

Settings → Secrets and variables → Actions (repo ou org):

- Dev: `DEV_HOST`, `DEV_USER`, `DEV_PASS`
- Staging: `STAGING_HOST`, `STAGING_USER`, `STAGING_PASS`
- Production: `API_HOST`, `USER`, `CONTROLEONLINE`, `PORT`
