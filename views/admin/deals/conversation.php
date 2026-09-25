<a class="back-link" href="<?= e(url('/admin/mensagens')) ?>">← Mensagens</a>
<div class="page-title">
    <div>
        <h1><?= e($conversation['company_name']) ?> e <?= e($conversation['creator_name']) ?></h1>
        <p>Somente leitura · acesso registrado com o motivo: "<?= e($reason) ?>"</p>
    </div>
</div>
<div class="inbox has-thread" style="grid-template-columns:1fr">
    <section class="thread">
        <header class="thread-head"><strong><?= e($conversation['listing_title'] ?? 'Conversa geral') ?></strong></header>
        <div class="thread-body">
            <?php if ($messages === []): ?><p class="muted">Nenhuma mensagem.</p><?php endif; ?>
            <?php foreach ($messages as $m): ?>
                <div class="bubble<?= $m['sender_role'] === 'creator' ? ' is-mine' : '' ?>">
                    <?= e($m['body']) ?>
                    <small><?= e($m['sender_name']) ?> (<?= $m['sender_role'] === 'creator' ? 'criador' : 'empresa' ?>) · <?= e(fmt_datetime($m['created_at'])) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
