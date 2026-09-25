<?php /** @var string $content */ ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? brand_name()) ?></title>
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <?= view('favicon') ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,620&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="auth-body">
<header class="auth-top">
    <a class="brand" href="<?= e(url('/')) ?>">
        <img src="<?= e(asset('images/favicon-32.png')) ?>" alt="" width="26" height="26">
        <span><?= e(brand_name()) ?></span>
    </a>
    <a class="text-link small" href="<?= e(url('/anuncios')) ?>">Ver anúncios</a>
</header>
<main class="auth-shell" id="conteudo">
    <div class="auth-card">
        <?= view('flash', ['success' => $success ?? null, 'error' => $error ?? null]) ?>
        <?= $content ?>
    </div>
</main>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
