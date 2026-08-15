# Arquitetura Geral — api-community

## Visão geral

O `api-community` é a **API REST/GraphQL central** do ecossistema ControleOnline. É construída em PHP 8.3+ com Symfony e API Platform 4, expondo recursos para todos os frontends e módulos da plataforma.

```
                    ┌────────────────────────────┐
                    │        Clientes HTTP        │
                    │  (app-community, pdv, etc.) │
                    └────────────┬───────────────┘
                                 │ REST / GraphQL
                    ┌────────────▼───────────────┐
                    │        api-community        │
                    │  Symfony + API Platform 4   │
                    │  PHP 8.3+                   │
                    └────┬──────────────────┬─────┘
                         │                  │
            ┌────────────▼──┐         ┌────▼──────────────┐
            │  Módulos locais│         │  Pacotes Composer  │
            │  modules/      │         │  vendor/           │
            │  controleonline│         │  controleonline/*  │
            └────────────────┘         └────────────────────┘
```

## Stack tecnológica

| Camada | Tecnologia |
|--------|-----------|
| Linguagem | PHP 8.3 / 8.4 |
| Framework | Symfony (MicroKernelTrait) |
| API Layer | API Platform 4 |
| ORM | Doctrine ORM 3 + Migrations |
| Banco de dados | MySQL (via Doctrine) |
| Cache / Fila | Symfony Queue + controleonline/queue |
| WebSocket | controleonline/websocket-server |
| Servidor web | Nginx + PHP-FPM (Docker) |

## Módulos {#modulos}

O `api-community` carrega módulos de dois locais:

1. **`modules/controleonline/<nome>/`** — módulos instalados como path repositories locais (development/monorepo).
2. **`vendor/controleonline/<nome>/`** — pacotes instalados via Composer.

O Kernel garante que o mesmo pacote não seja carregado duas vezes (deduplicação por `composer.json > name`).

### Lista de módulos ativos

| Módulo | Função |
|--------|--------|
| `accounting` | Contabilidade e lançamentos |
| `common` | Utilitários compartilhados |
| `contract` | Gestão de contratos |
| `ead` | Ensino a distância |
| `financial` | Financeiro e fluxo de caixa |
| `integration` | Integrações externas |
| `logistic` | Logística e entregas |
| `messages-sdk` | Envio de mensagens (email, WhatsApp) |
| `multi-tenancy` | Isolamento por empresa/tenant |
| `orders` | Pedidos e cotações |
| `people` | Cadastro de pessoas/empresas |
| `products` | Produtos e catálogo |
| `queue` | Filas de processamento assíncrono |
| `report` | Relatórios |
| `tasks` | Tarefas e agendamentos |
| `users` | Usuários e autenticação |
| `websocket-server` | Comunicação em tempo real |
| `whatsapp-sdk` | Integração WhatsApp |

## Kernel e carregamento de configuração {#kernel}

O `App\Kernel` estende `BaseKernel` com `MicroKernelTrait` e:

- Carrega `config/packages/`, `config/services.*` e `config/routes/` do projeto raiz.
- Varre `modules/*/*/config` e `vendor/controleonline/*/config` para carregar configurações de cada módulo.
- Usa `DoctrineMigrationsComparatorPass` para ordenar migrations de múltiplos módulos.

## Ambiente local {#ambiente-local}

### Requisitos

- Docker + Docker Compose
- PHP 8.3+ (para comandos fora do container)
- Composer 2

### Inicialização

```bash
# Sobe os containers (PHP-FPM + Nginx + DB)
docker-compose up -d

# Instala dependências
docker-compose exec php composer install

# Roda migrations
docker-compose exec php bin/console doctrine:migrations:migrate
```

### Serviços Docker

| Serviço | Imagem | Porta |
|---------|--------|-------|
| `php` | Build local (PHP-FPM) | — |
| `nginx` | nginx:1.12.1 | 80 |

## Segurança {#seguranca}

- Autenticação via token (configurado em `config/routes.yaml`).
- Multi-tenancy: isolamento de dados por empresa via `controleonline/multi-tenancy`.
- Regras transversais de qualidade: [agents-mcp/skills/shared/code-quality.md](https://github.com/ControleOnline/agents-mcp/blob/master/skills/shared/code-quality.md).

## Links relacionados

- [Wiki técnica (GitHub)](https://github.com/ControleOnline/api-community/wiki)
- [CI/CD e automação](./ci-automation.md)
- [Home da documentação](../wiki/Home.md)
