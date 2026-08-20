# CI/CD e Automação — api-community

## Visão geral

O `api-community` usa GitHub Actions para automação de CI/CD, orquestração de agents e documentação. Os workflows estão em `.github/workflows/`; workers reutilizáveis em `.github/actions/workers/`.

## Workflows disponíveis

| Arquivo | Propósito |
|---------|-----------|
| `deploy.yml` | Deploy automático (master → produção) |
| `deploy-api-lavego-master.yml` | Deploy específico para ambiente Lavego (master) |
| `deploy-apinew-lavego-master.yml` | Deploy Lavego (apinew) a partir de master |
| `manager-worker.yml` | Orquestrador Manager: resolve/cria issue e invoca workers (QA, Security, technical-documenter, gates) |
| `pull-request-checks.yml` | Validações em pull requests (lint, testes, análise estática) |

Workers compostos (actions locais):

| Action | Propósito |
|--------|-----------|
| `.github/actions/workers/manager` | Plano de workers + resolução de issue |
| `.github/actions/workers/qa` | Execução do agent QA |
| `.github/actions/workers/security` | Execução do agent Security |
| `.github/actions/workers/technical-documenter` | Documentação técnica automática |

---

## Manager Worker {#manager-worker}

### Objetivo

Disparado em push para `master`, `dev` ou `staging`. O Manager resolve ou cria a issue de origem e decide quais workers invocar (`run_qa`, `run_security`, `run_docs`, `run_gates`).

### Fluxo (resumo)

```
push → master|dev|staging
    │
    ▼
Manager (action local)
    │ resolve/cria issue
    │ outputs: issue_number, run_qa, run_security, run_docs, run_gates
    │
    ├─ [run_qa]        → worker QA
    ├─ [run_security]  → worker Security
    ├─ [run_docs]      → worker technical-documenter
    └─ [run_gates]     → gates / labels
```

### Permissões

| Permissão | Nível |
|-----------|-------|
| `contents` | read |
| `issues` | write |
| `pull-requests` | write |

Secret `GH_TOKEN` com escopo para issues/PRs e atribuição de assignees quando necessário.

---

## Technical Documenter {#technical-documenter}

### Objetivo

Quando o Manager define `run_docs=true` (tipicamente após push em `master` sem documentação prévia ou com label `agent:technical-documenter`), o worker **technical-documenter** atua via Copilot/agent para criar ou atualizar a documentação técnica do repositório.

### Fluxo de labels

| Label | Significado |
|-------|-------------|
| `agent:technical-documenter` | Issue marcada para documentação técnica |
| `agent:technical-documenter:done` | Documentação concluída pelo agent |
| `qa:accepted` / `security:accepted` | Podem ser aplicadas em issues criadas automaticamente pelo fluxo de docs (sem entrega de produto) |

### Referência

- Action: `.github/actions/workers/technical-documenter/action.yml`
- Role canônico: [agents-mcp/agents/roles/technical-documenter/agent.md](https://raw.githubusercontent.com/ControleOnline/agents-mcp/master/agents/roles/technical-documenter/agent.md)

---

## Deploy {#deploy}

### `deploy.yml` — Deploy contínuo (master → produção)

Disparado automaticamente a cada push em `master`. Realiza o deploy da API para o ambiente de produção.

### `deploy-api-lavego-master.yml` / `deploy-apinew-lavego-master.yml`

Deploys específicos para ambientes Lavego a partir de `master`.

### `pull-request-checks.yml` — Validações de PR

Executa verificações automáticas em pull requests (lint, testes, análise estática).

---

## Links relacionados

- [Arquitetura geral](./architecture.md)
- [Home da documentação](../wiki/Home.md)
- [Wiki técnica (GitHub)](https://github.com/ControleOnline/api-community/wiki)
- [agents-mcp](https://github.com/ControleOnline/agents-mcp) — fonte canônica dos agents
