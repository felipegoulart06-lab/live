<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Pedido <?= e($order['public_code']) ?></h1>
            <p class="muted"><?= e($order['service_title']) ?></p>
        </div>
        <a class="btn btn-ghost" href="<?= e(url('/admin/pedidos')) ?>">Voltar</a>
    </div>
    <div class="facts">
        <div><dt>Cliente</dt><dd><?= e($order['buyer_name']) ?></dd></div>
        <div><dt>Profissional</dt><dd><?= e($order['seller_name']) ?></dd></div>
        <div><dt>Total</dt><dd><?= e(money((int) $order['total_cents'])) ?></dd></div>
        <div><dt>Taxa</dt><dd><?= e(money((int) $order['fee_cents'])) ?></dd></div>
        <div><dt>Repasse</dt><dd><?= e(money((int) $order['seller_amount_cents'])) ?></dd></div>
        <div><dt>Criado</dt><dd><?= e($order['created_at']) ?></dd></div>
    </div>
    <form class="admin-form" method="post" action="<?= e(url('/admin/pedidos/' . $order['id'] . '/status')) ?>">
        <?= csrf_field() ?>
        <fieldset>
            <legend>Status operacional</legend>
            <label>Alterar para
                <select name="status">
                    <?php foreach (['awaiting_payment','paid','in_progress','delivered','completed','cancelled','disputed'] as $st): ?>
                        <option value="<?= e($st) ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="form-actions"><button class="btn btn-accent" type="submit">Atualizar pedido</button></div>
        </fieldset>
    </form>
</section>
