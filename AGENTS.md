## Configuração local para testes
- `api-community/key.local` guarda a chave de API de um usuário de teste. Use quando precisar validar autenticação real.
- Quando precisar acessar um ambiente por SSH para depurar ou publicar, consulte a tabela `servers` do banco do ambiente. Ela concentra os dados operacionais do acesso (`app_host`, `host`, `user`, `port` e `password`) e é a fonte de verdade para esse tipo de conexão.

## Qualidade
- Rodar lint e testes antes de concluir.
- Não introduzir breaking changes sem destacar.
- Preferir mudanças pequenas e isoladas.
- Corrigir deprecações no trecho tocado.

## Convenções gerais
- Preferir constantes em entidades em vez de duplicar catálogos em services.
- Toda regra de negócio deve ficar em service.
- Sempre revisar `securityFilter` das entidades afetadas.
- Evitar lógica em controllers; controller só orquestra entrada e saída.
- Funções devem ter responsabilidade única e tamanho pequeno.
- Antes de alterar fluxo persistente, revisar eventos como `postPersist` e `onEntityChanged`.
- Criar e manter `AGENTS.md` conciso em cada módulo afetado.
- Quando a regra for transversal entre módulos, o `AGENTS.md` da raiz também deve ser atualizado.
- Testes automatizados devem ficar dentro dos módulos correspondentes.
- Você deve manter o redme.md do projeto e dos submódulos sempre atualizados e se não existir, deve criar.
- Você deve manter o funding.yml do projeto e dos submódulos sempre atualizados e se não existir, deve criar.
- Você deve manter o .scrutinizer.yml do projeto e dos submódulos sempre atualizados e se não existir, deve criar.

## Regra transversal de filas e hierarquia
- `order_product_queues` e a árvore de `orderProducts` sao contratos diferentes.
- A fila de producao persiste apenas o que entrou na producao; a visibilidade de filho no pai e regra visual do consumidor.
- `showInParentQueue` nao pode criar registros sintéticos nem alterar a persistencia da fila real.
- A impressao em papel da fila deve seguir a mesma regra visual do display correspondente: item materializado nao mostra `2x`, e prefixo de quantidade so aparece acima de 1 em itens internos nao materializados.

## Regra transversal de acesso
- `people_link` é a fonte única de verdade dos papéis backend.
- O menu da home deve ser filtrado por `menu.app_type` e por `people_link.link_type`; `people_role`/`menu_role` nao devem ser usados para permissao de menu novo.
- `ROLE_SUPER` nao e gravado como vinculo de menu: ele apenas ignora o filtro de `link_type` dentro do `APP_TYPE` atual.
- Menus configuraveis usam apenas vinculos humanos (`employee`, `owner`, `director`, `manager`, `salesman`, `after-sales`, `courier`). `client`, `provider` e `franchisee` sao vinculos comerciais e nao devem aparecer como perfis de menu.
- `User` não pode devolver roles estáticos; token e sessão devem refletir os vínculos resolvidos.
- Roles humanas explícitas: `employee`, `owner`, `director`, `manager`, `salesman`, `after-sales`, `courier`.
- Roles comerciais explícitas: `client`, `provider`, `franchisee`.
- `ROLE_SUPER` existe apenas para `owner` da empresa principal.
- `ROLE_HUMAN` é apenas um agregador de entrada da API; ele não deve ser persistido nem salvo no token.
- O recorte real de dados deve acontecer no `securityFilter` dos services.
- A pessoa física só ganha permissão operacional na empresa do vínculo direto.
- A cadeia entre empresas acima do vínculo direto só valida acesso comercial ao painel até a empresa principal.
- `client` não concede papel humano operacional; ele apenas habilita o contexto comercial da empresa.
- `family` e `sellers-client` não são roles humanas oficiais da API.

## Regra transversal de seletor de empresa
- `/people/companies/my` deve listar todos os vínculos humanos diretos ativos da pessoa com empresas ativas.
- O seletor de empresa não deve esconder empresas só porque a cadeia comercial do `app-domain` atual não permite entrar nelas.
- A empresa principal deve aparecer no seletor quando houver vínculo humano direto, inclusive `owner`.
- A resposta de `/people/companies/my` deve separar os estados:
- `enabled`: empresa ativa no cadastro.
- `commercial_enabled`: empresa tem cadeia comercial válida até a principal no domínio atual.
- `panel_enabled`: empresa pode ser selecionada e usada no painel do domínio atual.
- `permission` em `/people/companies/my` deve refletir a permissão efetiva no domínio atual. Quando a cadeia comercial do domínio atual não for válida, a empresa continua visível no seletor, mas pode retornar `guest`.

## Regra transversal de Food99
- O financeiro de `Food99` deve ler somente o snapshot persistido em `order.otherInformations.Food99`.
- `Food99Service` nao deve criar invoices inline quando o pedido nasce; a geracao e o backfill ficam centralizados em `MarketplaceOrderFinancialGenerationService`.
- Em invoices de `Food99`, `iFood` so pode existir como contexto legado de estado do pedido, nunca como nome de conta, pagamento ou receptor.
- Na `Food99`, a carteira de repasse da loja vem apenas da configuracao da tela de integracao e e a unica fonte valida para `provider_wallet`; nao inferir nem reaproveitar `99 Food` ou `iFood` como carteira da loja.
- Nos pedidos filhos de logistica da `Food99`, `provider` e sempre o motoboy, `payer` e `99 Food`, `client` e a empresa do pedido pai, `deliveryContact` e o cliente do pedido pai, `addressOrigin` precisa estar sempre preenchido e o filho nao deve duplicar `otherInformations`.
- Backfill de `Food99` deve ser idempotente e sempre reconstruir as invoices a partir do snapshot do pedido, sem consultar fontes externas adicionais.

## Regra Food99
- Em `Food99`, apenas um código remoto pode ser considerado para vincular cliente: `receive_address.uid` do payload.
- Não inferir nem “adivinhar” `Food99.code` por telefone, e-mail ou combinações parciais de payload.
- Registros legados sem `uid` podem ser reconciliados por `nome + endereço completo` quando houver correspondência exata no banco.
- Quando existir mais de um candidato ou o payload não trouxer `uid`, o fluxo deve tratar o caso como legado e exigir validação explícita, nunca fallback heurístico.
- Invoices de repasse e cobrança da `Food99` devem sempre usar `receiver = 99 Food`, nunca `iFood` nem contexto legado reutilizado.
- Pedidos de segunda a domingo entram na mesma invoice semanal da `Food99`, com vencimento na quarta-feira seguinte.

## Retorno de API
- Toda resposta customizada interna deve seguir o padrão do `HydratorService`, com `@type: Error`, `hydra:title` e `hydra:description`.
- Controllers nao devem devolver `{"error": ...}` em paralelo quando a resposta interna puder usar o envelope do `HydratorService`.
- Exceções só são aceitáveis quando houver integração externa que imponha outro contrato.
- Totais de listagens devem ser expostos pelo mecanismo de `summary` do backend, usando `CollectionSummary` ou resolver especifico. O frontend nao deve precisar somar a pagina carregada para exibir totais filtrados.
- Quando uma listagem for consumida por `DefaultTable` React, o contrato de busca e ordenacao precisa existir no backend: `CustomOrFilter` ou equivalente para `search`, `OrderFilter` para os campos usados pelo store e `DateFilter` para periodos. Datas ordenam pelo valor persistido, nao por string formatada.
