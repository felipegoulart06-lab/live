<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Pedidos</h1>
            <p class="muted"><?= (int) $total ?> registros</p>
        </div>
    </div>
    <form class="filters" method="get" action="<?= e(url('/admin/pedidos')) ?>">
        <label>Busca<input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Código ou nome"></label>
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                <?php foreach (['awaiting_payment','paid','in_progress','delivered','completed','cancelled','disputed'] as $st): ?>
                    <option value="<?= e($st) ?>" <?= (($filters['status'] ?? '') === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-ink" type="submit">Filtrar</button>
    </form>
    <?php if (!$rows): ?>
        <?= \App\Core\View::component('empty', ['message' => 'Nenhum pedido encontrado.']) ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Código</th><th>Serviço</th><th>Cliente</th><th>Profissional</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['public_code']) ?></td>
                        <td><?= e($row['service_title']) ?></td>
                        <td><?= e($row['buyer_name']) ?></td>
                        <td><?= e($row['seller_name']) ?></td>
                        <td><?= e(money((int) $row['total_cents'])) ?></td>
                        <td><span class="status-pill"><?= e($row['status']) ?></span></td>
                        <td class="td-actions"><a class="btn btn-ghost" href="<?= e(url('/admin/pedidos/' . $row['id'])) ?>">Abrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $base = '/admin/pedidos'; $query = $filters; include BASE_PATH . '/views/admin/partials/pager.php'; ?>
    <?php endif; ?>
</section>
