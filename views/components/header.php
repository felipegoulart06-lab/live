<?php
/** @var \App\Models\User|null $authUser */
$cats = $menuCategories ?? [];
if ($cats === []) {
    try {
        $cats = (new \App\Repositories\CategoryRepository())->menuTree();
    } catch (\Throwable) {
        $cats = [];
    }
}
?>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<header class="site-header">
    <div class="header-inner">
        <button class="icon-btn only-mobile" data-drawer="#mobile-drawer" type="button" aria-label="Abrir menu">☰</button>
        <a class="brand" href="<?= e(url('/')) ?>"><?= e(setting('platform_name', 'Nexo')) ?></a>
        <form class="header-search" action="<?= e(url('/buscar')) ?>" method="get" role="search">
            <label class="sr-only" for="q">O que você precisa hoje?</label>
            <input id="q" name="q" type="search" placeholder="O que você precisa hoje?" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
            <button class="btn btn-ink" type="submit">Buscar</button>
        </form>
        <nav class="header-actions">
            <a class="text-link" href="<?= e(url('/buscar')) ?>">Explorar serviços</a>
            <a class="text-link" href="<?= e(url('/p/como-funciona')) ?>">Publicar projeto</a>
            <?php if ($authUser): ?>
                <a class="btn btn-ghost" href="<?= e(url('/conta')) ?>">Painel</a>
                <?php if ($authUser->isStaff()): ?>
                    <a class="text-link" href="<?= e(url('/admin')) ?>">Admin</a>
                <?php endif; ?>
            <?php else: ?>
                <a class="text-link" href="<?= e(url('/entrar')) ?>">Entrar</a>
                <a class="btn btn-ghost" href="<?= e(url('/criar-conta')) ?>">Criar conta</a>
                <a class="btn btn-accent" href="<?= e(url('/criar-conta?intent=seller')) ?>">Quero vender</a>
            <?php endif; ?>
        </nav>
    </div>
    <div class="mega-bar">
        <div class="mega-bar-inner">
            <?php foreach (array_slice($cats, 0, 8) as $cat): ?>
                <div class="mega-item">
                    <a href="<?= e(url('/buscar?categoria=' . $cat['slug'])) ?>"><?= e($cat['name']) ?></a>
                    <?php if (!empty($cat['children'])): ?>
                        <div class="mega-panel">
                            <p class="mega-title"><?= e($cat['name']) ?></p>
                            <ul>
                                <?php foreach ($cat['children'] as $child): ?>
                                    <li><a href="<?= e(url('/buscar?categoria=' . $cat['slug'] . '&sub=' . $child['slug'])) ?>"><?= e($child['name']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</header>
<div class="drawer" id="mobile-drawer" hidden>
    <div class="drawer-panel">
        <button class="icon-btn" data-close-drawer type="button">Fechar</button>
        <a class="brand" href="<?= e(url('/')) ?>"><?= e(setting('platform_name', 'Nexo')) ?></a>
        <?php foreach ($cats as $cat): ?>
            <details>
                <summary><?= e($cat['name']) ?></summary>
                <a href="<?= e(url('/buscar?categoria=' . $cat['slug'])) ?>">Ver categoria</a>
                <?php foreach ($cat['children'] ?? [] as $child): ?>
                    <a href="<?= e(url('/buscar?categoria=' . $cat['slug'] . '&sub=' . $child['slug'])) ?>"><?= e($child['name']) ?></a>
                <?php endforeach; ?>
            </details>
        <?php endforeach; ?>
    </div>
</div>
