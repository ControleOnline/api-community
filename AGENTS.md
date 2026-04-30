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

## Retorno de API
- Toda resposta customizada interna deve seguir o padrão do `HydratorService`.
- Exceções só são aceitáveis quando houver integração externa que imponha outro contrato.
