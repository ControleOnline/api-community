# Hotfix #687 — validação de contatos inativos

`fluxo: cliente-cadastro`

## Escopo

O `api-community/dev` deve consumir `ControleOnline/people@9cf1bda434765badfb3a10fbe6c2c5c3966475f5`, publicado em `api-community/master` no commit `6d7e62d144e1beb82e6bf7c4e97d1855fe60c253`.

Esse pin inclui o `PeopleItemProvider` nas operações `Put`/`Delete` e a resolução de escopo que considera vínculos inativos, permitindo editar um contato inativo sem o falso `404`.

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

## Critério automatizado focado

O check obrigatório do backend é a execução do conjunto PHPUnit focado em `People`/`people_link`, incluindo o caminho `PUT /people/{id}` para um registro `enable=false`. A saída do runner deve ser anexada pela validação, sem substituir as capturas da jornada visual.
