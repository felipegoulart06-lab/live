# Nexo

Marketplace de profissionais em PHP (MVC). Sem React, sem servidor MySQL: os dados ficam em um arquivo SQLite gerado pelo próprio PHP.

## Requisitos

- PHP 8.3 ou superior, com extensões: `pdo_sqlite`, `sqlite3`, `curl`, `mbstring`, `openssl`, `fileinfo`
- Composer é opcional (o projeto já carrega as classes sozinho)

## Subir localmente

Na pasta do projeto:

```bash
copy .env.example .env
php database/install.php
php -S localhost:8080 -t public public/router.php
```

No macOS/Linux use `cp .env.example .env`.

Abra [http://localhost:8080](http://localhost:8080).

Depois do `install.php`, o admin de demonstração é:

- e-mail: `admin@nexo.test`
- senha: `NexoAdmin!234`

Troque essa senha se o ambiente não for só local.

## Configurar

Edite `.env` (esse arquivo **não** vai para o GitHub):

- `APP_URL` — URL pública do site
- `APP_DEBUG` — `false` em produção

Login com Google: no painel, **Configurações**, cole Client ID e secret e ative. A URI de retorno é `{APP_URL}/entrar/google/retorno`.

## Painel

[http://localhost:8080/admin](http://localhost:8080/admin) — usuários, profissionais, serviços, pedidos, financeiro, CMS e auditoria.

## Produção (resumo)

1. Aponte o document root para `public/`
2. `APP_DEBUG=false` e `APP_URL` com HTTPS
3. Rode `php database/install.php` uma vez (ou só `migrate.php` + seeds, se preferir)
4. Garanta permissão de escrita em `storage/`

## Licença

Uso proprietário do projeto.
