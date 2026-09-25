<nav class="crumb" aria-label="Você está em"><a href="<?= e(url('/')) ?>">Início</a><span aria-hidden="true">/</span><span><?= e($page['title']) ?></span></nav>
<article class="panel prose" style="max-width:52rem">
    <h1><?= e($page['title']) ?></h1>
    <?= nl2p($page['content']) ?>
    <p class="muted small mb-0">Atualizado em <?= e(fmt_date($page['updated_at'])) ?></p>
</article>
