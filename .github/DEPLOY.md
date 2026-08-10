# Deploy pipelines (api-community)

GitHub Actions **não carrega** workflows em subpastas — só `.github/workflows/*.yml`.
Por isso a organização é por **prefixo de nome**, não por pasta.

| Arquivo | Branch trigger | Ambiente remoto | Secrets |
|---------|----------------|-----------------|---------|
| `deploy-dev.yml` | `dev` | `/var/www/api-community-dev` | `DEV_HOST`, `DEV_USER`, `DEV_PASS` |
| `deploy-staging.yml` | `staging` | `/var/www/api-community` (s.controleonline.com) | `DEV_HOST`, `DEV_USER`, `DEV_PASS` |
| `deploy-production.yml` | `master` | `~/sistemas/controleonline/api` (api.controleonline.com) | `API_HOST`, `USER`, `CONTROLEONLINE`, `PORT` |

## Regras

1. **Cada ambiente sincroniza só o próprio branch** (repo + submodules `origin/<branch>`).
2. Staging **nunca** faz checkout de `dev`.
3. Submodules: `git reset --hard origin/<branch>` em cada um.
4. Deploys manuais LaveGo (`deploy-api-lavego-*`, `deploy-apinew-*`) são `workflow_dispatch` / branch `xxx` — não entram no fluxo padrão.

## Submodules (ex.: api-platform-common)

O deploy do **parent** `api-community` já atualiza todos os submodules.
Workflows de deploy nos repositórios-filho são opcionais e exigem os **mesmos secrets** configurados naquele repo.
