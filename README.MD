# Portal Lúcia Ramos

Tema WordPress customizado para o portal de consultoras/revendedoras da Lúcia Ramos. O projeto nasceu sobre TailPress, usa Tailwind CSS v4, Vite e Alpine.js, mas a regra de negócio do portal ainda está majoritariamente em protótipo visual.

## Estado Atual

- Portal protegido para usuárias com role `consultora`.
- Páginas principais: Dashboard (`front-page.php`), Estoque, Vendas, Catálogo, Perfil e Entrar.
- Login real usa o fluxo nativo do WordPress em `/entrar/`.
- Dados de estoque, pré-vendas e resumo de estoque do dashboard vêm da API de consignação por uma rota REST interna assíncrona.
- Existe uma rota server-to-server `POST /wp-json/lucia-integracao/v1/consultoras` para criação e sincronização de contas `consultora`.
- CPF de consultora é salvo no `user_meta` `consultora_cpf` e usado nas consultas da API por CPF.
- Existem rotas REST internas para carregar consignação e marcar/desmarcar pré-venda; não existem CPTs, tabelas customizadas ou integração AJAX própria.
- `dist/` é gerado pelo build do Vite e não deve ser editado manualmente.

## Stack

- WordPress theme clássico com templates PHP.
- TailPress Framework via Composer.
- Tailwind CSS v4 via `@tailwindcss/vite`.
- Vite para build dos assets.
- Alpine.js para interações simples.
- PHP >= 8.0.2.

## Instalação Local

```bash
composer install
pnpm install
pnpm run dev
```

Para produção:

```bash
pnpm run build
```

O Vite está configurado para `http://portal-lucia-ramos.test` na porta `3000`.

> Atenção: o diretório atual do tema é `lucia-ramos-portal`, mas o `base` de produção em `vite.config.mjs` aponta para `/wp-content/themes/lucia_ramos_portal/dist/`. Antes de publicar, alinhe esse caminho com o nome real da pasta ativa no WordPress.

## Configuração da API

Configure a API no `wp-config.php`, fora do tema:

```php
define('LUCIA_CONSIGNACAO_API_BASE_URL', 'http://ensoft.ddns.com.br:9138/consignacao');
define('LUCIA_CONSIGNACAO_API_KEY', 'sua-chave');
define('LUCIA_ERP_INTEGRATION_API_KEY', 'sua-chave-de-integracao');
```

A chave nunca deve ser impressa no HTML nem versionada no repositório.

## Pré-requisitos WordPress

Além de ativar o tema, crie no admin do WordPress as páginas com estes slugs:

- `/entrar/` para o template `page-entrar.php`.
- `/estoque/` para o template `page-estoque.php`.
- `/vendas/` para o template `page-vendas.php`.
- `/catalogo/` para o template `page-catalogo.php`.
- `/perfil/` para o template `page-perfil.php`.

A home/front page do WordPress funciona como dashboard do portal por meio de `front-page.php`. As rotas protegidas dependem desses slugs em `tailpress_portal_page_slugs()`.

## Arquivos Mais Importantes

- `functions.php`: registra TailPress, assets e toda a proteção de rotas do portal.
- `inc/consultora-user-fields.php`: adiciona e valida o CPF administrativo da consultora.
- `inc/consignacao-api.php`: encapsula chamadas à API, normalização de consignações e rotas REST de consignação/pré-venda.
- `inc/consultora-provisioning.php`: expõe a rota de integração do ERP para criar/sincronizar consultoras via REST autenticada por chave.
- `header.php` e `footer.php`: escondem header/footer globais nas telas do portal e carregam o bootstrap visual.
- `template-parts/sidebar-nav.php`: shell do portal com sidebar desktop/mobile, navegação, logout e alternância claro/escuro.
- `front-page.php`: dashboard da consultora, com resumo de estoque carregado via REST e skeleton.
- `page-entrar.php`: tela de login usando `wp_login_url`.
- `page-estoque.php`: listagem e filtro de estoque carregado via REST em Alpine.
- `page-vendas.php`: lista itens da consignação via REST e marca/desmarca pré-venda pela rota interna.
- `page-catalogo.php`: materiais de apoio estáticos.
- `page-perfil.php`: formulário visual de perfil e senha, sem persistência.
- `resources/js/app.js`: inicializa Alpine, store global de tema e menu mobile do header padrão.
- `resources/css/theme.css`: tokens Tailwind baseados nas cores do `theme.json`.

## Documentação Técnica

- [Mapa do projeto](docs/PROJECT_MAP.md)
- [Contexto para IA](AGENTS.md)

Leia esses arquivos antes de implementar novas features. Eles documentam as convenções atuais, limitações e o caminho esperado para transformar o protótipo em portal persistente.

## Fonte de Verdade Atual vs Futuro

Estoque, pré-vendas e o resumo de estoque do dashboard usam a API externa por CPF através de endpoints REST internos. Catálogo, perfil, lucro/meta e avisos ainda não têm fonte persistente própria. Antes de adicionar novas regras complexas, mova dados e cálculos persistentes para uma camada PHP clara, como funções/repositórios do tema usando APIs nativas do WordPress.
