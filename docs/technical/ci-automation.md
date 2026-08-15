# CI/CD e Automação — api-community

## Visão geral

O `api-community` usa GitHub Actions para automação de CI/CD e documentação. Os workflows estão em `.github/workflows/`.

## Workflows disponíveis

| Arquivo | Propósito |
|---------|-----------|
| `deploy.yml` | Deploy automático (master → produção) |
| `deploy-api-lavego-master.yml` | Deploy específico para ambiente Lavego (master) |
| `deploy-apinew-lavego-staging-manual.yml` | Deploy manual para staging Lavego |
| `pull-request-checks.yml` | Validações em pull requests |
| `technical-documenter.yml` | Documentação técnica automática a cada push em master |

---

## Technical Documenter {#technical-documenter}

### Objetivo

A cada push no branch `master`, o workflow `technical-documenter.yml` dispara o agent **Copilot como technical-documenter** para criar ou atualizar a documentação técnica do repositório. Isso garante que a wiki técnica reflita o estado atual do código após cada entrega.

### Fluxo de execução

```
push → master
    │
    ▼
Detecta issue de origem no commit (grep por #NNN ou owner/repo#NNN)
    │
    ├─ [issue encontrada] ──► Adiciona label agent:technical-documenter
    │                          Atribui Copilot como technical-documenter
    │
    └─ [sem issue]        ──► Cria nova issue automática com:
                               - título: "docs: documentação técnica automática (push master <sha>)"
                               - labels: agent:technical-documenter
                               - body: SHA + mensagem do commit + instruções ao Copilot
                               - assignee: copilot-swe-agent[bot]
    │
    ▼
Finaliza labels:
    - Remove: agent:technical-documenter
    - Adiciona: agent:technical-documenter:done
    - (se issue criada) adiciona: qa:accepted, security:accepted
    - Posta comentário na issue
```

### Permissões necessárias

| Permissão | Nível |
|-----------|-------|
| `contents` | read |
| `issues` | write |
| `pull-requests` | write |

O secret `GH_TOKEN` deve ter permissão para criar/editar issues e atribuir Copilot.

### Labels do fluxo

| Label | Significado |
|-------|-------------|
| `agent:technical-documenter` | Issue marcada para documentação técnica |
| `agent:technical-documenter:done` | Documentação já concluída pelo agent |
| `qa:accepted` | Issue aceita pelo QA (aplicada automaticamente em issues criadas pelo workflow) |
| `security:accepted` | Issue aceita por security (aplicada automaticamente em issues criadas pelo workflow) |

### Instruções enviadas ao Copilot

O Copilot recebe as seguintes instruções customizadas ao ser atribuído:

> Atue 100% como o papel technical-documenter do ControleOnline. Leia e siga OBRIGATORIAMENTE https://raw.githubusercontent.com/ControleOnline/agents-mcp/master/agents/roles/technical-documenter/agent.md e as skills referenciadas. Wiki técnica é fonte primária. Use labels agent:technical-documenter e agent:technical-documenter:done. NÃO implemente código de produto. Ao terminar aplique agent:technical-documenter:done.

### Referência de arquivo

- `.github/workflows/technical-documenter.yml`
- Role canônico: [agents-mcp/agents/roles/technical-documenter/agent.md](https://raw.githubusercontent.com/ControleOnline/agents-mcp/master/agents/roles/technical-documenter/agent.md)

---

## Deploy {#deploy}

### `deploy.yml` — Deploy contínuo (master → produção)

Disparado automaticamente a cada push em `master`. Realiza o deploy da API para o ambiente de produção.

### `deploy-api-lavego-master.yml` — Lavego master

Deploy específico para o ambiente Lavego (branch master).

### `deploy-apinew-lavego-staging-manual.yml` — Lavego staging (manual)

Deploy manual para o ambiente de staging Lavego. Requer disparo manual (`workflow_dispatch`).

### `pull-request-checks.yml` — Validações de PR

Executa verificações automáticas em pull requests (lint, testes, análise estática).

---

## Links relacionados

- [Arquitetura geral](./architecture.md)
- [Home da documentação](../wiki/Home.md)
- [Wiki técnica (GitHub)](https://github.com/ControleOnline/api-community/wiki)
- [agents-mcp](https://github.com/ControleOnline/agents-mcp) — fonte canônica dos agents
