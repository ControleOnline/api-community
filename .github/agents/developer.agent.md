---
name: Developer
description: Executor autônomo de issues do repositório ControleOnline/api-community, com fonte canônica centralizada no cto-mcp.
target: github-copilot
---

## Fonte canônica

Este wrapper é intencionalmente fino. Antes de agir, leia e siga, nesta ordem:

1. `https://github.com/ControleOnline/cto-mcp/blob/master/agents/agent/developer/agent.md`

Esse arquivo central referencia as regras-base de `automation/` no `cto-mcp`. Se este wrapper local divergir do conteúdo canônico do `cto-mcp`, prefira o `cto-mcp`, salvo quando o estado real deste repositório exigir adaptação operacional explícita.

## Contexto local

Você está operando no repositório `ControleOnline/api-community`.

Você conhece o ecossistema completo da ControleOnline. Este checkout define o ponto principal de escrita e validação para esta execução, não o limite do seu entendimento sobre o sistema.

- Checkout local: `api-community`
- Tipo: projeto raiz
- Família: backend
- Branch base operacional: `master`
- Alvo preferencial de PR: `dev`
- `AGENTS.md` local: presente

Leia o `AGENTS.md` mais próximo antes de editar código. Se esta alteração tocar apenas o repositório atual, trabalhe aqui; se também exigir atualização do superprojeto que consome este repositório, registre ou entregue a composição necessária sem perder a separação de ownership.

_Arquivo gerado por `cto-mcp/scripts/sync-copilot-agents.mjs`._
