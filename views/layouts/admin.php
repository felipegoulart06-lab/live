<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Admin') ?> · <?= e(setting('platform_name', 'Nexo')) ?></title>
    <meta name="csrf-token" content="<?= e($csrf ?? csrf_token()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,520&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="dash-body">
<div class="dash">
    <aside class="dash-side dash-side--admin" id="dash-side">
        <a class="brand brand--dash" href="<?= e(url('/admin')) ?>"><?= e(setting('platform_name', 'Nexo')) ?> <span>Master</span></a>
        <nav class="side-nav">
            <a class="<?= nav_is('/admin') ? 'is-active' : '' ?>" href="<?= e(url('/admin')) ?>">Dashboard</a>
            <span class="side-label">Operação</span>
            <a class="<?= nav_is('/admin/usuarios') ? 'is-active' : '' ?>" href="<?= e(url('/admin/usuarios')) ?>">Usuários</a>
            <a class="<?= nav_is('/admin/profissionais') ? 'is-active' : '' ?>" href="<?= e(url('/admin/profissionais')) ?>">Profissionais</a>
            <a class="<?= nav_is('/admin/servicos') ? 'is-active' : '' ?>" href="<?= e(url('/admin/servicos')) ?>">Serviços</a>
            <a class="<?= nav_is('/admin/pedidos') ? 'is-active' : '' ?>" href="<?= e(url('/admin/pedidos')) ?>">Pedidos</a>
            <a class="<?= nav_is('/admin/projetos') ? 'is-active' : '' ?>" href="<?= e(url('/admin/projetos')) ?>">Projetos</a>
            <span class="side-label">Financeiro</span>
            <a class="<?= nav_is('/admin/pagamentos') ? 'is-active' : '' ?>" href="<?= e(url('/admin/pagamentos')) ?>">Pagamentos</a>
            <a class="<?= nav_is('/admin/saques') ? 'is-active' : '' ?>" href="<?= e(url('/admin/saques')) ?>">Saques</a>
            <span class="side-label">Conteúdo</span>
            <a class="<?= nav_is('/admin/categorias') ? 'is-active' : '' ?>" href="<?= e(url('/admin/categorias')) ?>">Categorias</a>
            <a class="<?= nav_is('/admin/paginas') ? 'is-active' : '' ?>" href="<?= e(url('/admin/paginas')) ?>">Páginas</a>
            <a class="<?= nav_is('/admin/banners') ? 'is-active' : '' ?>" href="<?= e(url('/admin/banners')) ?>">Banners</a>
            <a class="<?= nav_is('/admin/faqs') ? 'is-active' : '' ?>" href="<?= e(url('/admin/faqs')) ?>">FAQ</a>
            <a class="<?= nav_is('/admin/depoimentos') ? 'is-active' : '' ?>" href="<?= e(url('/admin/depoimentos')) ?>">Depoimentos</a>
            <span class="side-label">Sistema</span>
            <a class="<?= nav_is('/admin/configuracoes') ? 'is-active' : '' ?>" href="<?= e(url('/admin/configuracoes')) ?>">Configurações</a>
            <a class="<?= nav_is('/admin/auditoria') ? 'is-active' : '' ?>" href="<?= e(url('/admin/auditoria')) ?>">Auditoria</a>
        </nav>
        <div class="side-foot">
            <a class="btn btn-ghost" href="<?= e(url('/')) ?>">Ver site</a>
            <form method="post" action="<?= e(url('/sair')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-ghost" type="submit">Sair</button>
            </form>
        </div>
    </aside>
    <div class="dash-main">
        <header class="dash-top">
            <button class="icon-btn" data-drawer="#dash-side" type="button" aria-label="Abrir menu">☰</button>
            <strong><?= e(($user ?? $authUser)?->displayName() ?? 'Admin') ?></strong>
            <span class="muted"><?= e($title ?? '') ?></span>
        </header>
        <?= \App\Core\View::component('flash', ['success' => $success ?? null, 'error' => $error ?? null]) ?>
        <?= $content ?>
    </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
