<?php
$c = $contract;
$base = '/admin/contratos/' . $c['uuid'];
$isMaster = $authUser->isMaster();
$methods = ['pix' => 'Pix', 'boleto' => 'Boleto', 'cartao' => 'Cartão', 'transferencia' => 'Transferência'];
?>
<a class="back-link" href="<?= e(url('/admin/contratos')) ?>">← Contratos</a>
<div class="page-title">
    <div>
        <h1>Contrato <?= e($c['code']) ?></h1>
        <p class="row"><?= status_badge('contract', $c['status']) ?><?php if ($payment): ?> <?= status_badge('payment', $payment['status']) ?><?php endif; ?>
            <span><a href="<?= e(url('/admin/empresas/' . $people['company_uuid'])) ?>"><?= e($c['company_name']) ?></a> → <a href="<?= e(url('/admin/criadores/' . $people['creator_uuid'])) ?>"><?= e($c['creator_name']) ?></a></span></p>
    </div>
    <?php if ($c['conversation_uuid']): ?><a class="btn btn-ghost" href="<?= e(url('/admin/mensagens/' . $c['conversation_uuid'])) ?>">Conversa (acesso auditado)</a><?php endif; ?>
</div>

<div class="grid-main">
    <div class="stack">
        <?= view('contract-body', ['contract' => $c, 'events' => $events, 'attachments' => $attachments, 'viewer' => 'admin']) ?>
        <?php if ($review): ?>
            <section class="card card-pad">
                <h2 class="panel-title">Avaliação</h2>
                <p class="stars mb-0"><?= rating_stars((float) $review['rating']) ?></p>
                <p class="pre"><?= e($review['comment']) ?></p>
                <p class="mb-0"><?= status_badge('review', $review['status']) ?> <a class="small" href="<?= e(url('/admin/avaliacoes')) ?>">Moderar avaliações</a></p>
            </section>
        <?php endif; ?>
    </div>

    <div class="stack">
        <section class="card card-pad">
            <h2 class="panel-title">Financeiro</h2>
            <div class="money-lines">
                <div><span>Pacote de <?= (int) $c['hours'] ?>h</span><span><?= e(money($c['package_price_cents'])) ?></span></div>
                <?php foreach ($addons as $addon): ?><div><span><?= e($addon['name']) ?></span><span><?= e(money($addon['price_cents'])) ?></span></div><?php endforeach; ?>
                <div class="total"><span>Total pago pela empresa</span><span><?= e(money($c['total_cents'])) ?></span></div>
                <div><span>Taxa da plataforma</span><span><?= e(money($c['fee_cents'])) ?></span></div>
                <div><span>Líquido do criador</span><span><?= e(money($c['creator_amount_cents'])) ?></span></div>
            </div>
            <?php if ($payment): ?>
                <dl class="kv" style="margin-top:1rem">
                    <dt>Status</dt><dd><?= status_badge('payment', $payment['status']) ?></dd>
                    <dt>Forma</dt><dd><?= e($methods[$payment['method']] ?? ($payment['method'] ?: '—')) ?></dd>
                    <dt>Gateway</dt><dd><?= e($payment['gateway'] ?: '—') ?></dd>
                    <dt>Referência</dt><dd><?= e($payment['gateway_reference'] ?: '—') ?></dd>
                    <dt>Pago em</dt><dd><?= e(fmt_datetime($payment['paid_at'])) ?></dd>
                    <dt>Repassado em</dt><dd><?= e(fmt_datetime($payment['paid_out_at'])) ?></dd>
                    <?php if ($payment['refunded_at']): ?><dt>Reembolsado em</dt><dd><?= e(fmt_datetime($payment['refunded_at'])) ?></dd><?php endif; ?>
                </dl>
            <?php endif; ?>
        </section>

        <?php if ($c['status'] === 'awaiting_payment' && $payment && $payment['status'] === 'pending'): ?>
            <section class="card card-pad">
                <h2 class="panel-title">Registrar pagamento</h2>
                <?php if ($isMaster): ?>
                    <p class="muted small">Use depois de conferir o recebimento no banco ou no gateway. O contrato passa para "Confirmado" e o criador é avisado.</p>
                    <form class="stack-sm" method="post" action="<?= e(url($base . '/pagamento')) ?>" data-confirm="Confirmar o recebimento de <?= e(money($c['total_cents'])) ?>?">
                        <?= csrf_field() ?>
                        <label class="field"><span>Forma de pagamento</span>
                            <select name="method" required><?php foreach ($methods as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
                        </label>
                        <label class="field"><span>Referência <small>(ID da transação, comprovante)</small></span><input name="reference" maxlength="120"></label>
                        <button class="btn btn-accent" type="submit">Confirmar pagamento</button>
                    </form>
                <?php else: ?>
                    <p class="muted small mb-0">Somente administradores master confirmam pagamentos.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($payment && $payment['status'] === 'awaiting_payout'): ?>
            <section class="card card-pad">
                <h2 class="panel-title">Repasse ao criador</h2>
                <?php if ($isMaster): ?>
                    <form class="stack-sm" method="post" action="<?= e(url('/admin/pagamentos/' . $payment['uuid'] . '/repasse')) ?>" data-confirm="Registrar o repasse de <?= e(money($payment['net_cents'])) ?> ao criador?">
                        <?= csrf_field() ?>
                        <label class="field"><span>Referência da transferência</span><input name="reference" maxlength="120"></label>
                        <button class="btn btn-accent" type="submit">Registrar repasse de <?= e(money($payment['net_cents'])) ?></button>
                    </form>
                <?php else: ?>
                    <p class="muted small mb-0">Somente administradores master registram repasses.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php $canCancel = $c['status'] === 'awaiting_payment' || (in_array($c['status'], ['confirmed', 'in_progress'], true) && $isMaster); ?>
        <?php if ($canCancel): ?>
            <details class="reveal">
                <summary>Cancelar contrato</summary>
                <form class="reveal-body" method="post" action="<?= e(url($base . '/cancelar')) ?>" data-confirm="Cancelar o contrato <?= e($c['code']) ?>?">
                    <?= csrf_field() ?>
                    <?php if ($c['status'] !== 'awaiting_payment'): ?><p class="alert alert-warn mb-0">O pagamento já foi recebido: ele será marcado como reembolsado. Faça a devolução pelo meio original.</p><?php endif; ?>
                    <label class="field"><span>Motivo (as duas partes vão ver)</span><textarea name="reason" required minlength="5" maxlength="1000" rows="3"></textarea></label>
                    <button class="btn btn-danger" type="submit">Cancelar contrato</button>
                </form>
            </details>
        <?php endif; ?>

        <details class="reveal">
            <summary>Observação interna</summary>
            <form class="reveal-body" method="post" action="<?= e(url($base . '/observacao')) ?>">
                <?= csrf_field() ?>
                <label class="field"><span>Observação (visível só para a equipe)</span><textarea name="note" required minlength="3" maxlength="1000" rows="3"></textarea></label>
                <button class="btn btn-ghost" type="submit">Registrar</button>
            </form>
        </details>
    </div>
</div>
