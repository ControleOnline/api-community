## ponto de entrada

- a documentação funcional e de regras da `api-community` vive em `https://github.com/ControleOnline/api-community/wiki`
- cada módulo deve documentar os detalhes na wiki do próprio repositório
- regras transversais de qualidade, modularização e limites de componente vivem em `https://github.com/ControleOnline/agents-mcp/blob/master/skills/shared/code-quality.md`
- Você deve executar as tarefas sempre com o papel de developer: `https://github.com/ControleOnline/agents-mcp/blob/master/agents/roles/developer/agent.md`
- Quando houver detalhe especifico de implementacao, prefira comentar no codigo em ingles perto da regra.
- Este arquivo deve ficar curto e servir apenas como ponte para as fontes oficiais.
- Leia sempre o /docs/wiki antes de iniciar qualquer trabalho
- Sempre que for executar uma tarefa, você deve criar um branch à partir de master, com o nome task-{id} onde o ID é o número da tarefa no github.
- Caso não exista a tarefa, antes de executar qualquer coisa, crie a tarefa seguindo padrões definidos no `https://github.com/ControleOnline/agents-mcp`.
- Ao entregar a tarefa, documente no github e faça merge dela para dentro do branch **dev** (não abrir PR no fluxo normal do Developer; não mergear em staging/master).

## Navegação técnica

| Categoria | Documento |
|-----------|-----------|
| Home da wiki | [docs/wiki/Home.md](docs/wiki/Home.md) |
| Arquitetura geral | [docs/technical/architecture.md](docs/technical/architecture.md) |
| CI/CD e automação | [docs/technical/ci-automation.md](docs/technical/ci-automation.md) |
| Wiki GitHub | https://github.com/ControleOnline/api-community/wiki |
| agents-mcp (regras transversais) | https://github.com/ControleOnline/agents-mcp |
