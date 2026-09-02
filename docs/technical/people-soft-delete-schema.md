# People soft-delete schema (api-community#83)

## Incidente

`POST /webhook/ifood` retornou HTTP 500 com
`SQLSTATE[42S22] Unknown column 't0.deleted' in 'SELECT'`.
O mapeamento ORM de `people.deleted` / `people.deleted_at` existia no código;
a migration `DoctrineMigrations\People\Version20260820030000` não rodou no
tenant porque arquivos em `people/migrations` com namespace
`ControleOnline\Migrations` ficam invisíveis para
`config/packages/doctrine_migrations.yaml` (paths só em `DoctrineMigrations\*`),
e o comparador lexicográfico de FQCN ordenava `ControleOnline\*` antes de
`DoctrineMigrations\People\*`.

## Correção

1. Manter `Version20260820030000` no namespace `DoctrineMigrations\People`.
2. `ModuleVersionComparator` ordena por timestamp `VersionYYYYMMDDHHMMSS`
   antes do namespace.
3. Pin do submódulo `modules/controleonline/people` na task-83.

## Runbook pré/pós deploy

```bash
php bin/console app:schema:assert-people-soft-delete
```

- Exit 0: colunas presentes.
- Exit 1: aplicar `doctrine:migrations:migrate` no tenant e repetir.
- Smoke webhook iFood com assinatura inválida deve responder 401, nunca 500.
