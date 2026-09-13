# Hotfix #687 — validação de contatos inativos

`fluxo: cliente-cadastro`

## Escopo

O `api-community/dev` deve consumir `ControleOnline/api-platform-people@e8d1fc7c6bbdca4bf1d2d6b677ed61b3a55f9e94`, publicado na branch remota `task-687`.

O delta de task usa o pin remoto `ControleOnline/api-platform-people` (branch `task-687`), que inclui o `PeopleItemProvider` nas operações `Put`/`Delete` e a resolução de escopo que considera vínculos inativos, permitindo editar um contato inativo sem o falso `404`. O `PeopleCompanyScopeGuard` agora exige `people_link.enable = true` nas relações de escopo, impedindo autorização por vínculo revogado; o teste regressivo é `PeopleCompanyScopeGuardTest::testDoesNotAuthorizeThroughDisabledCallerCompanyLink`.

## Manifesto do smoke visual

Jornada: `My Company Details → Contatos → adicionar colaborador → preencher → Salvar → item listado → editar contato inativo → retornar à lista`.

Etapas que devem possuir captura sanitizada no ambiente de staging:

1. tela inicial de `My Company Details`;
2. aba `Contatos` aberta;
3. formulário de colaborador preenchido;
4. ação `Salvar` e feedback de sucesso sem `403`/`404`;
5. colaborador novo visível na lista;
6. edição do contato com estado inativo;
7. retorno à lista com o item atualizado.

As capturas devem omitir tokens, cookies, e-mails reais e identificadores pessoais. O resultado remoto do smoke deve registrar ambiente, data, SHA do superprojeto e SHA do submódulo `people`.

## Check obrigatório corrigido

O workflow `Deploy` teve a indentação do `export PATH` restaurada dentro do bloco `appleboy/ssh-action` em `dev`; isso evita um workflow sem jobs e permite que o job `tests` seja criado. A correção será verificada no merge remoto em `dev`.

## Critério automatizado focado

O check obrigatório do backend é a execução do conjunto PHPUnit focado em `People`/`people_link`, incluindo o caminho `PUT /people/{id}` para um registro `enable=false`. A saída do runner deve ser anexada pela validação, sem substituir as capturas da jornada visual.
