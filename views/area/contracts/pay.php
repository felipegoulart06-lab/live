<?php
$c = $contract;
$addons = $addons ?? [];
?>
<nav class="crumb" aria-label="Você está em">
    <a href="<?= e(url('/empresa/contratos')) ?>">Contratos</a>
    <span aria-hidden="true">/</span>
    <span><?= e($c['code']) ?></span>
</nav>

<ol class="pay-steps" aria-label="Etapas da compra">
    <li class="is-done">Pedido montado</li>
    <li class="is-current">Pagamento</li>
    <li>Envie o briefing</li>
</ol>

<div class="pay-grid">
    <div class="stack">
        <?php if (empty($mpEnabled)): ?>
            <div class="alert alert-warn">O checkout do Mercado Pago ainda não está ativo neste ambiente. Sem as chaves o brick não aparece.</div>
        <?php else: ?>
            <?= view('area/contracts/checkout', [
                'c' => $c,
                'mpPublicKey' => $mpPublicKey,
                'payerEmail' => $payerEmail,
                'payerDocument' => $payerDocument ?? '',
            ]) ?>
        <?php endif; ?>
    </div>
    <aside class="stack">
        <section class="card card-pad">
            <h2 class="panel-title">Resumo da compra</h2>
            <p class="mb-0"><strong><?= e($c['listing_title'] ?? '') ?></strong></p>
            <p class="muted small"><?= (int) $c['hours'] ?>h · <?= e(public_first_name($c['creator_name'] ?? '')) ?></p>
            <div class="money-lines">
                <div><span>Pacote de <?= (int) $c['hours'] ?>h</span><span><?= e(money($c['package_price_cents'])) ?></span></div>
                <?php foreach ($addons as $addon): ?>
                    <div><span><?= e($addon['name']) ?></span><span><?= e(money($addon['price_cents'])) ?></span></div>
                <?php endforeach; ?>
                <div class="total"><span>Total</span><span><?= e(money($c['total_cents'])) ?></span></div>
            </div>
            <p class="privacy-note" style="margin-top:1rem">Cartão e Pix no checkout transparente. O criador só grava depois da confirmação.</p>
            <details class="reveal" <?= error_field('reason') ? 'open' : '' ?>>
                <summary>Cancelar este pedido</summary>
                <form class="reveal-body" method="post" action="<?= e(url('/empresa/contratos/' . $c['uuid'] . '/cancelar')) ?>" data-confirm="Cancelar este contrato?">
                    <?= csrf_field() ?>
                    <?= view('field', ['name' => 'reason', 'label' => 'Motivo', 'type' => 'textarea', 'attrs' => 'required minlength="5" maxlength="500" rows="3"']) ?>
                    <button class="btn btn-danger" type="submit">Cancelar contrato</button>
                </form>
            </details>
        </section>
    </aside>
</div>
