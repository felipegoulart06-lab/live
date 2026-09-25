# CinquentaConto

Marketplace em PHP para empresas contratarem **horas de vídeo** com criadores. O tema é definido por quem paga, e telefone e WhatsApp do criador não aparecem no anúncio.

Três perfis, cada um com o seu painel:

- **Empresa** (`/empresa`): solicita horas, acompanha contratos, conversa com o criador, avalia e guarda favoritos.
- **Criador** (`/painel`): cria anúncios em 5 etapas (serviço, apresentação, horas e preços, adicionais, publicação), responde solicitações, conduz contratos e acompanha o financeiro.
- **Admin** (`/admin`): modera anúncios, criadores, empresas, contratos, pagamentos, denúncias, avaliações, conteúdo e configurações. Toda ação fica na auditoria, que não pode ser editada pelo painel.

As permissões são verificadas no servidor: cada consulta de anúncio, solicitação, contrato ou conversa é filtrada pelo dono, e o acesso a um registro de outro usuário responde "não encontrado".

## Requisitos

- PHP 8.3 ou superior, com `pdo_sqlite` (local) ou `pdo_pgsql` (Supabase), `curl`, `mbstring`, `openssl` e `fileinfo`
- Sem Composer: o projeto carrega as próprias classes

## Subir localmente

```bash
copy .env.example .env
php -S localhost:8080 -t public public/router.php
```

No macOS/Linux use `cp .env.example .env`. Abra [http://localhost:8080](http://localhost:8080).

No primeiro acesso o banco SQLite (`storage/cinquentaconto.sqlite`) é criado com dados de demonstração. Para recomeçar do zero: `php database/seed.php --fresh`.

## Contas de demonstração

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Admin master | `admin@cinquentaconto.com.br` | `CinquentaAdmin!234` |
| Admin staff | `moderacao@cinquentaconto.com.br` | `Demo12345` |
| Criador | `ana.freire@criador.demo` (e os outros `@criador.demo`) | `Demo12345` |
| Empresa | `contato@casa-aurora-cosmeticos.demo` (e os outros `contato@*.demo`) | `Demo12345` |

Troque as senhas antes de abrir o site ao público.

## Produção (Vercel + Supabase)

O repositório já tem `vercel.json` e `api/index.php`. No projeto da Vercel, deixe **Root Directory** vazio e o framework como **Other**.

Variáveis de ambiente:

- `APP_URL`: `https://seu-dominio`
- `APP_DEBUG`: `false`
- `APP_KEY`: uma string aleatória longa
- `DATABASE_URL`: connection string do Supabase (Project Settings > Database, pooler na porta 6543)
- `SUPABASE_URL` e `SUPABASE_SERVICE_ROLE_KEY`: para guardar uploads no Supabase Storage
- `SESSION_SECURE`: `true`

No Supabase Storage, crie os buckets `public-media` (público: fotos de anúncios, avatares e categorias) e `private-files` (privado: anexos de contratos).

As tabelas têm RLS ligado e sem políticas: a API pública do Supabase não lê nada, e só a aplicação (conexão direta ao Postgres) acessa os dados. Sem `DATABASE_URL`, a Vercel usa um SQLite temporário em `/tmp`, que se perde quando a instância reinicia.

Para popular um Postgres vazio: `php database/seed.php` com `DATABASE_URL` definido. Para gerar o SQL do schema: `php database/schema_sql.php`.

## Licença

Uso proprietário do projeto.
