## ponto de entrada

- A documentação técnica e de regras da `api-community` vive em https://github.com/ControleOnline/api-community/wiki
- Ponte local (somente URL): `docs/wiki.md` → `https://github.com/ControleOnline/api-community/wiki` (**não** usar submódulo `docs/wiki`)
- Cada módulo deve documentar os detalhes na wiki do próprio repositório
- Regras transversais de qualidade, modularização e limites de componente: https://github.com/ControleOnline/agents-mcp/blob/master/agents/skills/shared/quality/code-quality.md
- Catálogo oficial de fluxos de smoke: https://github.com/ControleOnline/agents-mcp/blob/master/agents/skills/shared/quality/smoke-test-flows.md
- Papel developer (fonte canônica): https://github.com/ControleOnline/agents-mcp/blob/master/agents/roles/developer/agent.md
- Quando houver detalhe específico de implementação, prefira comentar no código em inglês perto da regra
- Este arquivo deve ficar curto e servir apenas como **ponte** para as fontes oficiais

## Documentação (navegação humana)

| Categoria | Destino |
| --- | --- |
| Home do módulo | https://github.com/ControleOnline/api-community/wiki |
| Fluxos de Smoke (API) | https://github.com/ControleOnline/api-community/wiki/Fluxos-de-Smoke |
| Smoke-Test-Flows (alias catálogo) | https://github.com/ControleOnline/api-community/wiki/Smoke-Test-Flows |
| Arquitetura (repo) | https://github.com/ControleOnline/api-community/blob/master/docs/technical/architecture.md |
| CI/CD (repo) | https://github.com/ControleOnline/api-community/blob/master/docs/technical/ci-automation.md |

### Módulos relacionados

| Módulo | Entrada |
| --- | --- |
| app-community | https://github.com/ControleOnline/app-community/wiki |
| api-whatsapp | https://github.com/ControleOnline/api-whatsapp |
| agents-mcp | https://github.com/ControleOnline/agents-mcp |

## Fluxo de trabalho (resumo)

- Branch a partir de `master`: `task-{id}` (id = número da issue no GitHub)
- Se a tarefa não existir, criar seguindo padrões em https://github.com/ControleOnline/agents-mcp
- Entrega: documentar na issue e seguir o fluxo de merge oficial (Developer → `dev`; DevOps → staging/master)
