# Deploy pipelines (api-community)

GitHub Actions **não carrega** workflows em subpastas — só `.github/workflows/*.yml`.

| Arquivo | Branch | Ambiente remoto | Secrets |
|---------|--------|-----------------|---------|
| `deploy-dev.yml` | `dev` | `/var/www/api-community-dev` | `DEV_HOST`, `DEV_USER`, `DEV_PASS` |
| `deploy-staging.yml` | `staging` | `/var/www/api-community` (s.controleonline.com) | `STAGING_HOST`, `STAGING_USER`, `STAGING_PASS` |
| `deploy-production.yml` | `master` | `~/sistemas/controleonline/api` (api.controleonline.com) | `API_HOST`, `USER`, `CONTROLEONLINE`, `PORT` |

## Regras

1. **Staging não usa secrets `DEV_*`.** Dev e staging são credenciais separadas.
2. Cada ambiente sincroniza só o próprio branch (repo + submodules `origin/<branch>`).
3. Staging nunca faz checkout de `dev`.
4. Deploys manuais LaveGo permanecem `workflow_dispatch` / branch `xxx`.

## Secrets

Settings → Secrets and variables → Actions:

| Ambiente | Secrets |
|----------|---------|
| Dev | `DEV_HOST`, `DEV_USER`, `DEV_PASS` |
| Staging | `STAGING_HOST`, `STAGING_USER`, `STAGING_PASS` |
| Production | `API_HOST`, `USER`, `CONTROLEONLINE`, `PORT` |

Se os valores de staging estavam em `DEV_*`, copiar para `STAGING_*` e reservar `DEV_*` só para dev.
