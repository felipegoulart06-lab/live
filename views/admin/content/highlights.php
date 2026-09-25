<?php
use App\Controllers\Admin\ContentController;

$typeLabel = ['creator' => 'criador', 'listing' => 'anúncio', 'category' => 'categoria'][$type];
$inSection = array_map('intval', array_column($items, 'item_id'));
?>
<div class="page-title"><div><h1>Destaques da home</h1><p>Escolha e ordene o que aparece em cada seção da página inicial. Seções vazias usam a ordenação automática.</p></div></div>

<nav class="tabs" aria-label="Seções">
    <?php foreach (ContentController::SECTIONS as $key => [$label]): ?>
        <a href="<?= e(url('/admin/destaques?secao=' . $key)) ?>" class="<?= $section === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>

<div class="grid-2">
    <section class="card">
        <div class="card-head"><h2><?= e(ContentController::SECTIONS[$section][0]) ?></h2><span class="muted small"><?= count($items) ?> itens</span></div>
        <?php if ($items === []): ?>
            <p class="list-empty">Nenhum item escolhido. A home mostra a seleção automática.</p>
        <?php else: ?>
            <ol class="list">
                <?php foreach ($items as $i => $item): ?>
                    <li>
                        <span><strong><?= $i + 1 ?>. <?= e($item['label']) ?></strong></span>
                        <div class="actions-menu">
                            <?php if ($i > 0): ?><form method="post" action="<?= e(url('/admin/destaques/' . (int) $item['id'] . '/mover')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="direction" value="up"><button class="btn btn-ghost btn-sm" type="submit" aria-label="Subir <?= e($item['label']) ?>">↑</button></form><?php endif; ?>
                            <?php if ($i < count($items) - 1): ?><form method="post" action="<?= e(url('/admin/destaques/' . (int) $item['id'] . '/mover')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="direction" value="down"><button class="btn btn-ghost btn-sm" type="submit" aria-label="Descer <?= e($item['label']) ?>">↓</button></form><?php endif; ?>
                            <form method="post" action="<?= e(url('/admin/destaques/' . (int) $item['id'] . '/remover')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Remover</button></form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>

    <section class="card card-pad">
        <h2 class="panel-title">Adicionar <?= e($typeLabel) ?></h2>
        <?php if ($type !== 'category'): ?>
            <form class="toolbar" method="get" action="<?= e(url('/admin/destaques')) ?>">
                <input type="hidden" name="secao" value="<?= e($section) ?>">
                <label class="field grow"><span>Buscar <?= e($typeLabel) ?></span><input type="search" name="q" value="<?= e($q) ?>" required></label>
                <button class="btn btn-ghost" type="submit">Buscar</button>
            </form>
        <?php endif; ?>
        <?php if ($candidates === [] && ($q !== '' || $type === 'category')): ?>
            <p class="muted small mb-0">Nada encontrado.</p>
        <?php elseif ($candidates === []): ?>
            <p class="muted small mb-0">Busque pelo nome. Só aparecem itens ativos.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($candidates as $cand): ?>
                    <li>
                        <span><?= e($cand['label']) ?></span>
                        <?php if (in_array((int) $cand['id'], $inSection, true)): ?>
                            <span class="muted small">Já na seção</span>
                        <?php else: ?>
                            <form method="post" action="<?= e(url('/admin/destaques')) ?>" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="section" value="<?= e($section) ?>">
                                <input type="hidden" name="item_id" value="<?= (int) $cand['id'] ?>">
                                <button class="btn btn-ghost btn-sm" type="submit">Adicionar</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
