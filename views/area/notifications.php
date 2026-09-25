<?php $hasUnread = in_array(null, array_column($result['rows'], 'read_at'), true); ?>
<div class="page-title">
    <div>
        <h1>Notificações</h1>
        <p>Avisos sobre anúncios, solicitações, contratos e pagamentos.</p>
    </div>
    <?php if ($hasUnread): ?>
        <form method="post" action="<?= e(url('/notificacoes/ler')) ?>"><?= csrf_field() ?><button class="btn btn-ghost" type="submit">Marcar todas como lidas</button></form>
    <?php endif; ?>
</div>
<?php if ($result['rows'] === []): ?>
    <?= view('empty', ['title' => 'Nenhuma notificação', 'message' => 'Quando algo acontecer nos seus anúncios, pedidos ou contratos, você é avisado aqui.']) ?>
<?php else: ?>
    <div class="card">
        <ul class="list">
            <?php foreach ($result['rows'] as $n): ?>
                <li>
                    <a href="<?= e(url('/notificacoes/' . (int) $n['id'])) ?>">
                        <strong><?= $n['read_at'] ? '' : '<span class="count" aria-hidden="true" style="min-width:.6rem;height:.6rem;padding:0;margin-right:.4rem"></span><span class="sr-only">Não lida: </span>' ?><?= e($n['title']) ?></strong>
                        <?php if ($n['body']): ?><small><?= e($n['body']) ?></small><?php endif; ?>
                    </a>
                    <small class="nowrap"><?= e(time_ago($n['created_at'])) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?= view('pager', ['result' => $result]) ?>
<?php endif; ?>
