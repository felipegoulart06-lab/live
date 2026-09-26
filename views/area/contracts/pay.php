<?php
$c = $contract;
$addons = $addons ?? [];
$support = (string) setting('support_email', 'suporte@cinquentaconto.com.br');
$days = (int) ($c['delivery_days'] ?? 0);
?>
<nav class="crumb" aria-label="Você está em">
    <a href="<?= e(url('/empresa/contratos')) ?>">Contratos</a>
    <span aria-hidden="true">/</span>
    <span><?= e($c['code']) ?></span>
</nav>

<ol class="pay-steps" aria-label="Etapas da compra">
    <li class="is-done">Pedido conferido</li>
    <li class="is-current">Pagamento seguro</li>
    <li>Gravação após a confirmação</li>
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
        <ul class="pay-assurances">
            <li>Checkout transparente oficial do Mercado Pago (cartão e Pix).</li>
            <li>Número, validade e CVV ficam no Mercado Pago. A <?= e(brand_name()) ?> só recebe o token da cobrança.</li>
            <li>O valor cobrado é o total deste pedido (<?= e(money($c['total_cents'])) ?>). Não há acréscimo na hora do pagamento.</li>
        </ul>
    </div>
    <aside class="stack">
        <section class="card card-pad pay-summary">
            <h2 class="panel-title">Resumo da compra</h2>
            <div class="pay-item">
                <?php if (!empty($c['listing_cover'])): ?>
                    <img src="<?= e(media($c['listing_cover'])) ?>" alt="">
                <?php endif; ?>
                <div>
                    <p class="mb-0"><strong><?= e($c['listing_title'] ?? '') ?></strong></p>
                    <p class="muted small mb-0"><?= (int) $c['hours'] ?>h · <?= e(public_first_name($c['creator_name'] ?? '')) ?><?= $days > 0 ? ' · prazo ' . $days . ' dias' : '' ?></p>
                    <p class="muted small mb-0">Pedido <?= e($c['code']) ?></p>
                </div>
            </div>
            <div class="money-lines">
                <div><span>Pacote de <?= (int) $c['hours'] ?>h</span><span><?= e(money($c['package_price_cents'])) ?></span></div>
                <?php foreach ($addons as $addon): ?>
                    <div><span><?= e($addon['name']) ?></span><span><?= e(money($addon['price_cents'])) ?></span></div>
                <?php endforeach; ?>
                <div class="total"><span>Total a pagar</span><span><?= e(money($c['total_cents'])) ?></span></div>
            </div>
        </section>
        <section class="pay-guarantee">
            <strong>Compra protegida <?= e(brand_name()) ?></strong>
            <p>O criador só é acionado depois que o Mercado Pago confirmar o pagamento. A conversa e a entrega ficam nesta plataforma — sem contato externo.</p>
            <p class="mb-0">Prazo combinado no contrato<?= $days > 0 ? ' (' . $days . ' dias)' : '' ?>. Dúvida ou estorno: <?= e($support) ?>.</p>
        </section>
        <p class="pay-methods" aria-label="Formas aceitas">Pix · Crédito · Débito</p>
        <details class="reveal" <?= error_field('reason') ? 'open' : '' ?>>
            <summary>Cancelar este pedido</summary>
            <form class="reveal-body" method="post" action="<?= e(url('/empresa/contratos/' . $c['uuid'] . '/cancelar')) ?>" data-confirm="Cancelar este contrato?">
                <?= csrf_field() ?>
                <?= view('field', ['name' => 'reason', 'label' => 'Motivo', 'type' => 'textarea', 'attrs' => 'required minlength="5" maxlength="500" rows="3"']) ?>
                <button class="btn btn-danger" type="submit">Cancelar contrato</button>
            </form>
        </details>
    </aside>
</div>
