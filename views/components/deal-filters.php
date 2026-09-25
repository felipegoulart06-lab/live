<?php
/** @var string $action @var string $domain @var string $placeholder */
$g = static fn (string $k): string => is_string($_GET[$k] ?? null) ? (string) $_GET[$k] : '';
?>
<nav class="tabs" aria-label="Status">
    <a href="<?= e(url($action)) ?>" class="<?= $g('status') === '' ? 'is-active' : '' ?>">Todos</a>
    <?php foreach (status_map($domain) as $k => [$label]): ?>
        <a href="<?= e(url($action . '?status=' . $k)) ?>" class="<?= $g('status') === $k ? 'is-active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
<form class="toolbar" method="get" action="<?= e(url($action)) ?>">
    <?php if ($g('status') !== ''): ?><input type="hidden" name="status" value="<?= e($g('status')) ?>"><?php endif; ?>
    <label class="field grow"><span>Buscar</span><input type="search" name="q" value="<?= e($g('q')) ?>" placeholder="<?= e($placeholder) ?>"></label>
    <label class="field"><span>De</span><input type="date" name="de" value="<?= e($g('de')) ?>"></label>
    <label class="field"><span>Até</span><input type="date" name="ate" value="<?= e($g('ate')) ?>"></label>
    <button class="btn btn-ink" type="submit">Filtrar</button>
</form>
