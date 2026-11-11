# Preparação local: cores adicionais de categorias e rotas

O código da API foi preparado na fonte do pacote compartilhado `api-platform-common`, em checkout local separado, compatível com a versão `controleonline/common` 1.0.9 instalada. A migração `DoctrineMigrations\Common\Version20261110220000` acompanha o pacote e será descoberta pelo caminho já existente em `config/packages/doctrine_migrations.yaml` quando uma versão estável contendo a alteração for instalada.

A documentação detalhada está em `api-platform-common/docs/module-appearance-properties-local.md`. Inclui as cinco colunas nullable, contrato JSON, validação, permissões preservadas e testes sem banco.

A API instalada não foi substituída pelo checkout local: sem aplicar a migração, o mapeamento/SELECT novos fariam referência a colunas ausentes. Por isso o vendor e os pins Composer continuam inalterados, e o app mantém os campos como prévia quando a API não expõe a capacidade de salvá-los.

A ativação futura exige autorização separada para pacote estável, atualização da dependência, migração nos tenants corretos e publicação. Não executar migração/schema:update durante esta preparação. Os testes isolados usam somente mocks e não iniciam o kernel ou conexão real.

As alterações preexistentes em `config/reference.php` e `.env.dev` não fazem parte deste trabalho.

## Referência local da task-1496

O código e a migração estão no commit local `504a5437465c7eacb809ce27812781862467bef4` de `api-platform-common`, branch `task-1496`. Essa referência não foi publicada e não substitui uma versão estável no Composer. Este commit da API contém somente a documentação de integração; o vendor, os pins e o banco permanecem inalterados. A próxima promoção a dev/staging requer autorização própria, preservando a ordem pacote/API/migração/app descrita acima.
