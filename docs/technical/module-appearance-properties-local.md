# Preparação local: cores adicionais de categorias e rotas

O código da API foi preparado na fonte do pacote compartilhado `api-platform-common`, em checkout local separado, compatível com a versão `controleonline/common` 1.0.9 instalada. A migração `DoctrineMigrations\Common\Version20261110220000` acompanha o pacote e será descoberta pelo caminho já existente em `config/packages/doctrine_migrations.yaml` quando uma versão estável contendo a alteração for instalada.

A documentação detalhada está em `api-platform-common/docs/module-appearance-properties-local.md`. Inclui as cinco colunas nullable, contrato JSON, validação, permissões preservadas e testes sem banco.

A API instalada não foi substituída pelo checkout local: sem aplicar a migração, o mapeamento/SELECT novos fariam referência a colunas ausentes. Por isso o vendor e os pins Composer continuam inalterados, e o app mantém os campos como prévia quando a API não expõe a capacidade de salvá-los.

A ativação futura exige autorização separada para pacote estável, atualização da dependência, migração nos tenants corretos e publicação. Não executar migração/schema:update durante esta preparação. Os testes isolados usam somente mocks e não iniciam o kernel ou conexão real.

As alterações preexistentes em `config/reference.php` e `.env.dev` não fazem parte deste trabalho.

## Referência local da task-1496

O código e a migração estão no commit local `504a5437465c7eacb809ce27812781862467bef4` de `api-platform-common`, branch `task-1496`. Essa referência não foi publicada e não substitui uma versão estável no Composer. Este commit da API contém somente a documentação de integração; o vendor, os pins e o banco permanecem inalterados. A próxima promoção a dev/staging requer autorização própria, preservando a ordem pacote/API/migração/app descrita acima.

## Integração de fonte em dev/staging — task #1496

Após a preparação local descrita acima, foi autorizada a publicação das branches e integração do código em dev/staging, mantendo a mesma task. Isso não autoriza ativar o novo mapeamento nos servidores, executar a migração ou gravar dados. Os merges de integração deste pacote/API devem conter `[skip ci] [skip-db-migrations]` para não disparar deploy, bootstrap ou migrações automáticas. O app permanece compatível com a API anterior, mostrando como prévia os campos ainda não suportados.

Não criar tag estável nem atualizar o pin `controleonline/common` da API nesta etapa. A liberação efetiva das novas propriedades depende da autorização posterior da migração e da ativação coordenada do pacote, com o tráfego suspenso até o schema estar pronto. Nenhum aceite desta integração de fonte equivale a homologação de persistência em banco.
