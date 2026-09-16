<?php
$base = $base ?? '/';
$query = $query ?? [];
$pages = (int) ($pages ?? 1);
$page = (int) ($page ?? 1);
if ($pages <= 1) {
    return;
}
?>
<nav class="pager">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a class="<?= $i === $page ? 'is-active' : '' ?>" href="<?= e(url($base . '?' . http_build_query(array_merge($query, ['page' => $i])))) ?>"><?= $i ?></a>
    <?php endfor; ?>
</nav>
