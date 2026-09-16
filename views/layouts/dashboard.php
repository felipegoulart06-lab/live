<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Painel') ?> · <?= e(setting('platform_name', 'CinquentaConto')) ?></title>
    <meta name="csrf-token" content="<?= e($csrf ?? csrf_token()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,520&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="dash-body">
<div class="dash">
    <aside class="dash-side" id="dash-side">
        <a class="brand brand--dash" href="<?= e(url('/')) ?>"><?= e(setting('platform_name', 'CinquentaConto')) ?></a>
        <?php $seller = ($user ?? $authUser)?->isSeller(); ?>
        <nav class="side-nav">
            <a href="<?= e(url('/conta')) ?>" class="is-active">Visão geral</a>
            <?php if ($seller): ?>
                <a href="<?= e(url('/conta')) ?>">Meus serviços</a>
                <a href="<?= e(url('/conta')) ?>">Pedidos</a>
                <a href="<?= e(url('/conta')) ?>">Propostas</a>
                <a href="<?= e(url('/conta')) ?>">Ganhos</a>
            <?php else: ?>
                <a href="<?= e(url('/conta')) ?>">Pedidos</a>
                <a href="<?= e(url('/conta')) ?>">Projetos</a>
                <a href="<?= e(url('/conta')) ?>">Favoritos</a>
            <?php endif; ?>
            <a href="<?= e(url('/conta')) ?>">Mensagens</a>
            <a href="<?= e(url('/conta')) ?>">Configurações</a>
        </nav>
        <form method="post" action="<?= e(url('/sair')) ?>"><?= csrf_field() ?><button class="btn btn-ghost" type="submit">Sair</button></form>
    </aside>
    <div class="dash-main">
        <header class="dash-top">
            <button class="icon-btn" data-drawer="#dash-side" type="button" aria-label="Abrir menu">☰</button>
            <strong><?= e(($user ?? $authUser)?->displayName() ?? '') ?></strong>
        </header>
        <?= \App\Core\View::component('flash', ['success' => $success ?? null, 'error' => $error ?? null]) ?>
        <?= $content ?>
    </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
