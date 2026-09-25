<div class="empty">
    <strong><?= e($title ?? 'Nada por aqui ainda.') ?></strong>
    <?php if (!empty($message)): ?><p><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($action) && !empty($actionUrl)): ?>
        <a class="btn btn-ghost" href="<?= e($actionUrl) ?>"><?= e($action) ?></a>
    <?php endif; ?>
</div>
