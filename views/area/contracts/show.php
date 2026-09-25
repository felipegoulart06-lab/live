<?php
$isCreator = $area === '/painel';
$c = $contract;
$base = $area . '/contratos/' . $c['uuid'];
?>
<a class="back-link" href="<?= e(url($area . '/contratos')) ?>">← Contratos</a>
<div class="page-title">
    <div>
        <h1>Contrato <?= e($c['code']) ?></h1>
        <p class="row"><?= status_badge('contract', $c['status']) ?><?php if ($payment): ?> <?= status_badge('payment', $payment['status']) ?><?php endif; ?></p>
    </div>
    <?php if ($c['conversation_uuid']): ?><a class="btn btn-ghost" href="<?= e(url($area . '/mensagens/' . $c['conversation_uuid'])) ?>">Mensagens</a><?php endif; ?>
</div>

<?php if ($c['status'] === 'awaiting_payment'): ?>
    <div class="alert alert-warn">
        <?= $isCreator
            ? 'Aguardando o pagamento da empresa. Não comece a gravar antes da confirmação: ela aparece aqui e nas suas notificações.'
            : 'Contrato criado. O pagamento é conferido pela equipe da plataforma; assim que ele for confirmado, o criador recebe o aviso para começar.' ?>
    </div>
<?php elseif ($c['status'] === 'confirmed' && $isCreator): ?>
    <div class="alert alert-info">Pagamento confirmado. Combine a data de gravação e marque o contrato como em andamento quando começar.</div>
<?php endif; ?>

<div class="grid-main">
    <div class="stack">
        <?= view('contract-body', ['contract' => $c, 'events' => $events, 'attachments' => $attachments, 'viewer' => $isCreator ? 'creator' : 'company']) ?>
    </div>

    <div class="stack">
        <section class="card card-pad">
            <h2 class="panel-title">Valores</h2>
            <div class="money-lines">
                <div><span>Pacote de <?= (int) $c['hours'] ?>h</span><span><?= e(money($c['package_price_cents'])) ?></span></div>
                <?php foreach ($addons as $addon): ?><div><span><?= e($addon['name']) ?></span><span><?= e(money($addon['price_cents'])) ?></span></div><?php endforeach; ?>
                <div class="total"><span>Total do contrato</span><span><?= e(money($c['total_cents'])) ?></span></div>
                <?php if ($isCreator): ?>
                    <div><span>Taxa da plataforma</span><span>− <?= e(money($c['fee_cents'])) ?></span></div>
                    <div class="total"><span>Você recebe</span><span><?= e(money($c['creator_amount_cents'])) ?></span></div>
                <?php endif; ?>
            </div>
            <?php if ($payment): ?>
                <dl class="kv" style="margin-top:1rem">
                    <dt>Pagamento</dt><dd><?= status_badge('payment', $payment['status']) ?></dd>
                    <?php if ($payment['paid_at']): ?><dt>Pago em</dt><dd><?= e(fmt_datetime($payment['paid_at'])) ?></dd><?php endif; ?>
                    <?php if ($isCreator && $payment['paid_out_at']): ?><dt>Repassado em</dt><dd><?= e(fmt_datetime($payment['paid_out_at'])) ?></dd><?php endif; ?>
                    <?php if ($payment['refunded_at']): ?><dt>Reembolsado em</dt><dd><?= e(fmt_datetime($payment['refunded_at'])) ?></dd><?php endif; ?>
                </dl>
            <?php endif; ?>
        </section>

        <?php if ($isCreator && in_array($c['status'], ['awaiting_payment', 'confirmed', 'in_progress'], true)): ?>
            <section class="card card-pad stack-sm">
                <h2 class="panel-title mb-0">Ações</h2>
                <?php if ($c['status'] === 'confirmed'): ?>
                    <form method="post" action="<?= e(url($base . '/iniciar')) ?>"><?= csrf_field() ?><button class="btn btn-accent btn-block" type="submit">Marcar como em andamento</button></form>
                <?php elseif ($c['status'] === 'in_progress'): ?>
                    <form method="post" action="<?= e(url($base . '/concluir')) ?>" class="stack-sm" data-confirm="Concluir o contrato? A empresa será convidada a avaliar.">
                        <?= csrf_field() ?>
                        <?= view('field', ['name' => 'note', 'label' => 'Observação da entrega', 'type' => 'textarea', 'optional' => true, 'attrs' => 'rows="3" maxlength="1000"']) ?>
                        <button class="btn btn-accent btn-block" type="submit">Concluir contrato</button>
                    </form>
                <?php endif; ?>
                <details class="reveal" <?= error_field('scheduled_date') ? 'open' : '' ?>>
                    <summary>Definir data de gravação</summary>
                    <form class="reveal-body" method="post" action="<?= e(url($base . '/agenda')) ?>">
                        <?= csrf_field() ?>
                        <div class="form-grid">
                            <?= view('field', ['name' => 'scheduled_date', 'label' => 'Data', 'type' => 'date', 'value' => $c['scheduled_date'] ?? '', 'attrs' => 'required min="' . date('Y-m-d', strtotime('+1 day')) . '"']) ?>
                            <?= view('field', ['name' => 'scheduled_time', 'label' => 'Horário', 'type' => 'time', 'value' => $c['scheduled_time'] ? substr($c['scheduled_time'], 0, 5) : '']) ?>
                        </div>
                        <button class="btn btn-ghost" type="submit">Salvar agenda</button>
                    </form>
                </details>
            </section>
        <?php endif; ?>

        <?php if ($c['status'] === 'awaiting_payment'): ?>
            <details class="reveal" <?= error_field('reason') ? 'open' : '' ?>>
                <summary>Cancelar contrato</summary>
                <form class="reveal-body" method="post" action="<?= e(url($base . '/cancelar')) ?>" data-confirm="Cancelar este contrato?">
                    <?= csrf_field() ?>
                    <?= view('field', ['name' => 'reason', 'label' => 'Motivo', 'type' => 'textarea', 'attrs' => 'required minlength="5" maxlength="500" rows="3"']) ?>
                    <button class="btn btn-danger" type="submit">Cancelar contrato</button>
                </form>
            </details>
        <?php elseif (in_array($c['status'], ['confirmed', 'in_progress'], true)): ?>
            <p class="muted small">Depois do pagamento, cancelamentos e reembolsos são tratados pelo suporte (<?= e((string) setting('support_email', '')) ?>).</p>
        <?php endif; ?>

        <?php if (!$isCreator && $c['status'] === 'completed'): ?>
            <section class="card card-pad">
                <h2 class="panel-title">Avaliação</h2>
                <?php if ($review): ?>
                    <p class="stars mb-0" aria-label="<?= (int) $review['rating'] ?> de 5"><?= rating_stars((float) $review['rating']) ?></p>
                    <p class="pre"><?= e($review['comment']) ?></p>
                    <p class="muted small mb-0">Enviada em <?= e(fmt_date($review['created_at'])) ?><?= $review['status'] === 'hidden' ? ' · ocultada pela moderação' : '' ?></p>
                <?php else: ?>
                    <form class="form" method="post" action="<?= e(url($base . '/avaliar')) ?>">
                        <?= csrf_field() ?>
                        <fieldset>
                            <legend>Nota</legend>
                            <div class="row">
                                <?php foreach ([5, 4, 3, 2, 1] as $n): ?>
                                    <label class="check"><input type="radio" name="rating" value="<?= $n ?>" required <?= (string) old('rating') === (string) $n ? 'checked' : '' ?>> <?= $n ?> ★</label>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($m = error_field('rating')): ?><span class="field-err"><?= e($m) ?></span><?php endif; ?>
                        </fieldset>
                        <?= view('field', ['name' => 'comment', 'label' => 'Comentário', 'type' => 'textarea', 'hint' => 'Aparece publicamente no anúncio.', 'attrs' => 'required minlength="10" maxlength="1500" rows="4"']) ?>
                        <button class="btn btn-accent" type="submit">Enviar avaliação</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php elseif ($isCreator && $review && $review['status'] === 'visible'): ?>
            <section class="card card-pad">
                <h2 class="panel-title">Avaliação da empresa</h2>
                <p class="stars mb-0"><?= rating_stars((float) $review['rating']) ?></p>
                <p class="pre mb-0"><?= e($review['comment']) ?></p>
            </section>
        <?php endif; ?>
    </div>
</div>
