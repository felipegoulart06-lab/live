<?php
/** @var array{total: int, page: int, pages: int, per_page: int} $result */
$page = (int) $result['page'];
$pages = (int) $result['pages'];
if ($pages <= 1) {
    return;
}
$from = max(1, $page - 2);
$to = min($pages, $page + 2);
?>
<nav class="pager" aria-label="Paginação">
    <?php if ($page > 1): ?>
        <a href="<?= e(query_url(['pagina' => $page - 1])) ?>" rel="prev">Anterior</a>
    <?php else: ?>
        <span class="is-disabled" aria-hidden="true">Anterior</span>
    <?php endif; ?>
    <?php if ($from > 1): ?><a href="<?= e(query_url(['pagina' => 1])) ?>">1</a><?php if ($from > 2): ?><span aria-hidden="true">…</span><?php endif; ?><?php endif; ?>
    <?php for ($i = $from; $i <= $to; $i++): ?>
        <?php if ($i === $page): ?>
            <span class="is-active" aria-current="page"><?= $i ?></span>
        <?php else: ?>
            <a href="<?= e(query_url(['pagina' => $i])) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($to < $pages): ?><?php if ($to < $pages - 1): ?><span aria-hidden="true">…</span><?php endif; ?><a href="<?= e(query_url(['pagina' => $pages])) ?>"><?= $pages ?></a><?php endif; ?>
    <?php if ($page < $pages): ?>
        <a href="<?= e(query_url(['pagina' => $page + 1])) ?>" rel="next">Próxima</a>
    <?php else: ?>
        <span class="is-disabled" aria-hidden="true">Próxima</span>
    <?php endif; ?>
    <span class="pager-info"><?= (int) $result['total'] ?> resultados</span>
</nav>
