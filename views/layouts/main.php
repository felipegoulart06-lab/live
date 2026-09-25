<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= \App\Core\View::component('favicon') ?>
    <title><?= e($title ?? brand_name()) ?></title>
    <meta name="description" content="<?= e($metaDescription ?? setting('meta_description', '')) ?>">
    <link rel="canonical" href="<?= e(isset($canonicalPath) ? url($canonicalPath) : url($_SERVER['REQUEST_URI'] ?? '/')) ?>">
    <meta property="og:title" content="<?= e($title ?? brand_name()) ?>">
    <meta property="og:description" content="<?= e($metaDescription ?? setting('meta_description', '')) ?>">
    <meta property="og:type" content="<?= !empty($ogImage) ? 'product' : 'website' ?>">
    <meta property="og:url" content="<?= e(isset($canonicalPath) ? url($canonicalPath) : url($_SERVER['REQUEST_URI'] ?? '/')) ?>">
    <?php if (!empty($ogImage)): ?>
        <meta property="og:image" content="<?= e($ogImage) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="csrf-token" content="<?= e($csrf ?? csrf_token()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,620&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => brand_name(),
        'url' => env('APP_URL'),
        'description' => setting('meta_description', ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>
    <?php if (!empty($jsonLd) && is_array($jsonLd)): ?>
    <script type="application/ld+json">
    <?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>
    <?php endif; ?>
</head>
<body>
<?= \App\Core\View::component('header', ['authUser' => $authUser ?? null, 'menuCategories' => $menuCategories ?? []]) ?>
<main id="conteudo">
    <?= \App\Core\View::component('flash', ['success' => $success ?? null, 'error' => $error ?? null]) ?>
    <?= $content ?>
</main>
<?= \App\Core\View::component('footer', ['footerPages' => $footerPages ?? []]) ?>
<div id="toast-region" class="toast-region" aria-live="polite"></div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
