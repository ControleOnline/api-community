## Configuração local para testes
- api-community\key.local tem a informação da chave de API de um usuário de testes. Esse usuário pode ser utilizado para realizar chamadas à API e confirmar informações sobre o funcionamento. Use quando precisar.

## Qualidade
- Rodar lint e testes antes de concluir.
- Não introduzir breaking changes sem destacar.
- Preferir mudanças pequenas e isoladas.


## Estilo de implementação
- Seguir padrão dos módulos existentes.
- Reaproveitar utilitários antes de criar novos.
- Nomear arquivos e classes de forma consistente com os módulos atuais.

## Convenções
- Preferir constantes em entidades em vez de serviços.
- Não acessar banco direto fora de repository.
- Toda regra de negócio deve ficar em service.
- DTOs devem validar entrada.
- Evitar lógica em controllers.
- Não adicinhar ou criar métodos para pesquisar várias opções.
- Comente todo o código
- Antes de começar qualquer análise, pense em eventos como o postPersist, existe vários deles implementados no sistema
- Use onEntityChanged para escutar eventos de outroas entidades e prefira ele ao postPersist principalmente para evitar referências circulares
- Sempre verifique o securityFilter de todas as entidades, isso é um ponto de segurança muito importante.
- Funções devem ter responsabilidade única
- Funções devem ser pequenas e caso necessário, separadas em mais de uma função para garantir que sejam legíveis
- Não há diversos nomes ou diversos jeitos de fazer alguma coisa. Se houver dois arquivos diferentes tratando a mesma coisa, pergunte qual deve manter e ejuste para que apenas um componente tenha a responsabilidade por aquela função.
- Se houver erros de grafia, ou diversos nomes para encontrar algo como um array de palavras por conta de dúvidas do que é o correto, simplesmente pergunte qual o correto. Exemplo: [order, orders] num campo de tipos provavelmente haverá uma grafia correta e outra que age como um fallback, porém isso não deve existir de forma alguma.
- Crie e mantenha atualizado de forma concisa, AGENTS.md em cada módulo. Eles devem ter regras claras sobre o funcionamento daquele módulo, principalmente regras de negócio pedidas em prompt. Se uma regra mudar, apague e reescreva. Não mexa em AGENTS.md da raiz, apenas de módulos.
- Crie testes automatizados sempre que possível e os mantenha atualizados. Crie os testes dentro dos módulos correspondententes e não na raiz.
- Sempre corrija o retorno do que for customizado, a API deve responder sempre no padrão do HydratorService e nunca de outra forma. Só é permitido retornos personalizados em casos excepcionais, como um retorno para um módulo terceiro que não possa seguir nosso padrão. Internamente, para nós, seguiremos um único padrão de resposta
- Controllers só são usadas em casos que realmente sejam necessárias. Prefira entidades, a API foi feita pra isso.