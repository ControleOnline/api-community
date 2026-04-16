
## Qualidade
- Rodar lint e testes antes de concluir.
- Não introduzir breaking changes sem destacar.
- Preferir mudanças pequenas e isoladas.


## Estilo de implementação
- Seguir padrão dos módulos existentes.
- Reaproveitar utilitários antes de criar novos.
- Nomear arquivos e classes de forma consistente com os módulos atuais.

## Convenções
- Não acessar banco direto fora de repository.
- Toda regra de negócio deve ficar em service.
- DTOs devem validar entrada.
- Evitar lógica em controllers.
- Não adicinhar ou criar métodos para pesquisar várias opções.
- Comente todo o código
- Antes de começar qualquer análise, pense em eventos como o postPersist, existe vários deles implementados no sistema
- Sempre verifique o securityFilter de todas as entidades, isso é um ponto de segurança muito importante.