## Configuração local para testes
- `api-community/key.local` guarda a chave de API de um usuário de teste. Use quando precisar validar autenticação real.

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

## Regra transversal de acesso
- `people_link` é a fonte única de verdade dos papéis backend.
- `User` não pode devolver roles estáticos; token e sessão devem refletir os vínculos resolvidos.
- Roles humanas explícitas: `employee`, `owner`, `director`, `manager`, `salesman`, `after-sales`.
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

## Retorno de API
- Toda resposta customizada interna deve seguir o padrão do `HydratorService`.
- Exceções só são aceitáveis quando houver integração externa que imponha outro contrato.
