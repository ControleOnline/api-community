# API #268 — comparação das falhas com a base

## Resultado

Em 19/10/2026, a execução completa com os ajustes locais reproduziu os **51 erros e 15 falhas** da auditoria anterior. Todos os 66 testes também falham na base, com o mesmo tipo de resultado e diagnóstico. **Nenhuma regressão foi detectada nesta suíte.** Não foi necessário alterar código de produção nesta rodada.

| Execução | Testes | Assertions | Erros | Falhas | Saída |
| --- | ---: | ---: | ---: | ---: | ---: |
| Código atual | 986 | 7702 | 51 | 15 | 2 |
| Base com pacotes publicados | 977 | 7578 | 52 | 18 | 2 |

A base executa também os cinco testes locais de `tests/Metadata`, como controles: quatro deles falham na base e passam no atual (snapshot de metadata, restrição aos dois GETs, projeção de PeopleLink e precedência de `/translates/overview`). Esses controles foram escritos para os novos comportamentos; não são quatro testes históricos da base. Os nove testes adicionais dos módulos compartilhados também passaram no atual.

## Identidade e isolamento

- HEAD da API e `master` remoto conferido nesta rodada: `8e047ccc9413b3e254a7694a971a56c995379942`.
- Duas cópias independentes em `/tmp/co-api268-baseline-audit`; nenhum arquivo de ambiente real nem cache em uso foi copiado. Sem hard links com o código servido.
- Ambiente sintético idêntico nas duas execuções: banco `127.0.0.1:1`, mailer nulo e transporte em memória. Nenhuma credencial real foi usada para a suíte.
- A base restaura os arquivos versionados alterados para HEAD e remove as configurações novas de CSRF e da rota de traduções. `common` e `people`, que contêm as alterações em dependências, foram restaurados integralmente dos arquivos publicados nas referências exatas instaladas; o restante das dependências é idêntico entre as cópias.
- `controleonline/common v1.0.5`: `1ed215c1c2270575fe7264c0b6d4fd9a705a4dd0`.
- `controleonline/people v1.0.3`: `3f2b2854208d5dbb038548834b9eeb1abd022bce`.
- Uma comparação preliminar com os snapshots anteriores aos overlays também não encontrou novas falhas. Esses snapshots contêm ajustes locais anteriores e, por isso, não substituíram a comparação final com os pacotes publicados.

As primeiras cópias isoladas estavam sem alguns arquivos versionados de fixtures e variáveis sintéticas exigidas pelo container. Isso produziu falhas adicionais de preparação, não contadas como resultado final. Os arquivos necessários foram copiados e o ambiente sintético completado igualmente nas duas cópias antes das execuções finais. A execução final atual voltou exatamente aos 986 testes / 7702 assertions / 51 erros / 15 falhas anteriores.

## Classificação das 66 falhas anteriores

| Origem | Testes com erro/falha |
| --- | ---: |
| orders | 31 |
| financial | 10 |
| accounting | 9 |
| products | 5 |
| common | 4 |
| integration | 4 |
| queue | 1 |
| report | 1 |
| app (workflow de deploy) | 1 |

Os diagnósticos incluem contratos antigos dos testes com PHPUnit/Doctrine/Symfony, classes ou métodos ausentes e expectativas funcionais divergentes. A comparação comprova que essas ocorrências antecedem os ajustes avaliados; não determina, sozinha, se cada ocorrência é defeito de teste ou de produção. Os nomes e a primeira linha de cada diagnóstico estão em `baseline-comparison.json`.

## Validação e limites

- Comparação JUnit por identidade de teste, tipo de resultado e diagnóstico completo. Apenas os caminhos das cópias temporárias e os identificadores aleatórios de mocks foram normalizados: zero testes que passam na base e falham no atual; zero diagnósticos alterados entre os 66 problemas comuns.
- Três testes Python dos overlays passaram, saída 0. Hashes dos 12 registros de arquivos dos dois manifests conferidos, respeitando a sobreposição intencional de ExtraDataService.
- Logs/JUnit completos locais: `/tmp/co-api268-baseline-audit/{current,stock}-final.{log,xml}`. Resultados resumidos, identidades e checksum do Composer lock estão em `baseline-comparison.json`.
- A suíte completa continua com saída 2; as falhas não foram ignoradas, reclassificadas como aprovação nem corrigidas fora do escopo autorizado.
- Não houve mudança visual, gravação de dados comerciais, commit, push ou publicação. A classificação encerra a dúvida sobre regressões desta suíte; não encerra as outras validações de publicação nem homologa todos os fluxos do ERP.
