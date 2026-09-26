<div class="card card-pad stack-sm" id="checkout-transparente">
    <h2 class="panel-title mb-0">Pague com Mercado Pago</h2>
    <p class="muted small mb-0">Cartão ou Pix. Os dados do cartão não passam pelo nosso servidor.</p>
    <div id="mp-pix" class="mp-pix" hidden></div>
    <div
        id="mp-checkout"
        data-public-key="<?= e($mpPublicKey) ?>"
        data-amount="<?= e(number_format(((int) $c['total_cents']) / 100, 2, '.', '')) ?>"
        data-email="<?= e($payerEmail) ?>"
        data-doc="<?= e($payerDocument ?? '') ?>"
        data-pay="<?= e(url('/empresa/contratos/' . $c['uuid'] . '/pagar')) ?>"
        data-status="<?= e(url('/empresa/contratos/' . $c['uuid'] . '/pagamento')) ?>"
    ></div>
</div>
<script src="https://sdk.mercadopago.com/js/v2"></script>
<script src="<?= e(asset('js/checkout.js')) ?>" defer></script>
