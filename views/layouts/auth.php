<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Entrar') ?> · <?= e(setting('platform_name', 'CinquentaConto')) ?></title>
    <meta name="csrf-token" content="<?= e($csrf ?? csrf_token()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,520;9..144,620&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="auth-body">
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<header class="auth-top">
    <a class="brand" href="<?= e(url('/')) ?>"><?= e(setting('platform_name', 'CinquentaConto')) ?></a>
</header>
<main id="conteudo" class="auth-shell">
    <?= \App\Core\View::component('flash', ['success' => $success ?? null, 'error' => $error ?? null]) ?>
    <?= $content ?>
</main>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
