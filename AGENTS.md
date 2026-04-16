
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
- Queries devem ficar em repositórios.
- Não adicinhar ou criar métodos para pesquisar várias opções.
- Preferir usar o getter reload no store em vez de criar funções de reload
- Usar o loading único do sistema e melhorá-lo se precisar, assim como o módulo de exibição de erros (state store)
- Em máscaras, calculos e todos os tipos de helpers, usar um repositório do sistema e mentê-lo sempre organizado e em arquivos pequenos
- Manter as telas sempre componentezadas, reaproveitando tudo o que é possível, e mantendo tudo pequeno e organizado.
- Separar CSS dos arquivos de JS.