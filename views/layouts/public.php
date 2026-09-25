<?php
/** @var string $content */
$pageTitle = $title ?? brand_name();
$description = $metaDescription ?? (string) setting('meta_description', '');
$navCategories = \App\Core\Db::all("SELECT name, slug FROM categories WHERE status = 'visible' ORDER BY sort_order, name LIMIT 10");
$searchQuery = is_string($_GET['q'] ?? null) ? mb_substr($_GET['q'], 0, 80) : '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <?php if ($description !== ''): ?><meta name="description" content="<?= e($description) ?>"><?php endif; ?>
    <?php if (!empty($canonicalPath)): ?><link rel="canonical" href="<?= e(url($canonicalPath)) ?>"><?php endif; ?>
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
    <?php if (!empty($noindex)): ?><meta name="robots" content="noindex"><?php endif; ?>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <?= view('favicon') ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,620&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<?php if (!empty($preview) && !empty($listing)): ?>
    <div class="preview-bar" role="status">
        <span><strong>Pré-visualização.</strong> Status atual: <?= e(status_label('listing', $listing['status'])) ?>. Empresas só veem este anúncio quando ele estiver ativo.</span>
        <a href="<?= e(url(($authUser && $authUser->isAdmin() ? '/admin/anuncios/' : '/painel/anuncios/') . $listing['uuid'])) ?>">Voltar para a edição</a>
    </div>
<?php endif; ?>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?= e(url('/')) ?>">
            <img src="<?= e(asset('images/favicon-32.png')) ?>" alt="" width="26" height="26">
            <span><?= e(brand_name()) ?></span>
        </a>
        <form class="header-search" action="<?= e(url('/anuncios')) ?>" method="get" role="search">
            <label class="sr-only" for="busca-topo">Buscar anúncios</label>
            <input id="busca-topo" type="search" name="q" value="<?= e($searchQuery) ?>" placeholder="Buscar por tipo de vídeo, criador ou tema">
            <button class="btn btn-ink" type="submit">Buscar</button>
        </form>
        <nav class="header-actions" aria-label="Principal">
            <a class="text-link hide-md" href="<?= e(url('/anuncios')) ?>">Anúncios</a>
            <a class="text-link hide-md" href="<?= e(url('/criadores')) ?>">Criadores</a>
            <?php if ($authUser): ?>
                <a class="btn btn-ghost hide-md" href="<?= e(url($authUser->homePath())) ?>">Meu painel</a>
            <?php else: ?>
                <a class="text-link hide-md" href="<?= e(url('/login')) ?>">Entrar</a>
                <a class="btn btn-accent hide-md" href="<?= e(url('/cadastro')) ?>">Criar conta</a>
            <?php endif; ?>
            <button class="icon-btn only-mobile" type="button" data-drawer="#menu-publico" aria-controls="menu-publico" aria-expanded="false">Menu</button>
        </nav>
    </div>
    <?php if ($navCategories !== []): ?>
        <nav class="cat-bar" aria-label="Categorias">
            <div class="cat-bar-inner">
                <?php foreach ($navCategories as $cat): ?>
                    <a href="<?= e(category_url($cat['slug'])) ?>" class="<?= nav_is('/categorias/' . $cat['slug'], true) ? 'is-active' : '' ?>"><?= e($cat['name']) ?></a>
                <?php endforeach; ?>
            </div>
        </nav>
    <?php endif; ?>
</header>

<div class="drawer" id="menu-publico" hidden>
    <nav class="drawer-panel" aria-label="Menu">
        <button class="icon-btn" type="button" data-close-drawer>Fechar</button>
        <a href="<?= e(url('/anuncios')) ?>">Anúncios</a>
        <a href="<?= e(url('/criadores')) ?>">Criadores</a>
        <a href="<?= e(url('/como-funciona')) ?>">Como funciona</a>
        <a href="<?= e(url('/ajuda')) ?>">Ajuda</a>
        <?php if ($authUser): ?>
            <a href="<?= e(url($authUser->homePath())) ?>">Meu painel</a>
        <?php else: ?>
            <a href="<?= e(url('/login')) ?>">Entrar</a>
            <a href="<?= e(url('/cadastro')) ?>">Criar conta</a>
        <?php endif; ?>
        <?php if ($navCategories !== []): ?>
            <span class="drawer-label">Categorias</span>
            <?php foreach ($navCategories as $cat): ?>
                <a href="<?= e(category_url($cat['slug'])) ?>"><?= e($cat['name']) ?></a>
            <?php endforeach; ?>
        <?php endif; ?>
    </nav>
</div>

<main class="site-main" id="conteudo">
    <?= view('flash', ['success' => $success ?? null, 'error' => $error ?? null]) ?>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="footer-grid">
        <div>
            <p class="brand"><?= e(brand_name()) ?></p>
            <p class="muted small"><?= e((string) setting('platform_tagline', '')) ?></p>
        </div>
        <div>
            <p class="footer-heading">Empresas</p>
            <a href="<?= e(url('/anuncios')) ?>">Encontrar criadores</a>
            <a href="<?= e(url('/como-funciona')) ?>">Como funciona</a>
            <a href="<?= e(url('/cadastro?tipo=empresa')) ?>">Cadastrar empresa</a>
        </div>
        <div>
            <p class="footer-heading">Criadores</p>
            <a href="<?= e(url('/cadastro?tipo=criador')) ?>">Anunciar minhas horas</a>
            <a href="<?= e(url('/criadores')) ?>">Criadores na plataforma</a>
            <a href="<?= e(url('/ajuda')) ?>">Perguntas frequentes</a>
        </div>
        <div>
            <p class="footer-heading">Institucional</p>
            <a href="<?= e(url('/sobre')) ?>">Sobre</a>
            <a href="<?= e(url('/termos')) ?>">Termos de uso</a>
            <a href="<?= e(url('/privacidade')) ?>">Privacidade</a>
            <a href="<?= e(url('/contato')) ?>">Contato</a>
        </div>
    </div>
    <p class="footer-copy">© <?= date('Y') ?> <?= e(brand_name()) ?>. Contratações e pagamentos acontecem dentro da plataforma.</p>
</footer>
<div class="toast-region" id="toast-region" aria-live="polite"></div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
