<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Pagamentos</h1>
            <p class="muted">Pedidos e volume financeiro</p>
        </div>
    </div>
    <div class="stat-grid stats-admin">
        <div><span>Volume</span><strong><?= e(money((int) ($snapshot['gmv'] ?? 0))) ?></strong></div>
        <div><span>Taxas</span><strong><?= e(money((int) ($snapshot['fees'] ?? 0))) ?></strong></div>
        <div><span>Saques pendentes</span><strong><?= e(money((int) ($snapshot['pending_withdrawals'] ?? 0))) ?></strong></div>
        <div><span>Saques pagos</span><strong><?= e(money((int) ($snapshot['paid_withdrawals'] ?? 0))) ?></strong></div>
    </div>
    <form class="filters" method="get" action="<?= e(url('/admin/pagamentos')) ?>">
        <label>Busca<input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>"></label>
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                <?php foreach (['awaiting_payment','paid','completed','cancelled'] as $st): ?>
                    <option value="<?= e($st) ?>" <?= (($filters['status'] ?? '') === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-ink" type="submit">Filtrar</button>
    </form>
    <?php if (!$rows): ?>
        <?= \App\Core\View::component('empty', ['message' => 'Nenhum pagamento listado.']) ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Código</th><th>Cliente</th><th>Profissional</th><th>Total</th><th>Taxa</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><a href="<?= e(url('/admin/pedidos/' . $row['id'])) ?>"><?= e($row['public_code']) ?></a></td>
                        <td><?= e($row['buyer_name']) ?></td>
                        <td><?= e($row['seller_name']) ?></td>
                        <td><?= e(money((int) $row['total_cents'])) ?></td>
                        <td><?= e(money((int) $row['fee_cents'])) ?></td>
                        <td><?= e($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $base = '/admin/pagamentos'; $query = $filters; include BASE_PATH . '/views/admin/partials/pager.php'; ?>
    <?php endif; ?>
</section>
