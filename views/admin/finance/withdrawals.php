<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Saques</h1>
            <p class="muted"><?= (int) $total ?> solicitações</p>
        </div>
    </div>
    <div class="stat-grid stats-admin">
        <div><span>Em análise</span><strong><?= e(money((int) ($snapshot['pending_withdrawals'] ?? 0))) ?></strong></div>
        <div><span>Pagos</span><strong><?= e(money((int) ($snapshot['paid_withdrawals'] ?? 0))) ?></strong></div>
    </div>
    <form class="filters" method="get" action="<?= e(url('/admin/saques')) ?>">
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                <?php foreach (['requested','reviewing','paid','rejected'] as $st): ?>
                    <option value="<?= e($st) ?>" <?= (($filters['status'] ?? '') === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-ink" type="submit">Filtrar</button>
    </form>
    <?php if (!$rows): ?>
        <?= \App\Core\View::component('empty', ['message' => 'Nenhum saque no momento.']) ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Profissional</th><th>Valor</th><th>Método</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['display_name']) ?><small style="display:block" class="muted"><?= e($row['email']) ?></small></td>
                        <td><?= e(money((int) $row['amount_cents'])) ?></td>
                        <td><?= e($row['method']) ?></td>
                        <td><span class="status-pill"><?= e($row['status']) ?></span></td>
                        <td class="td-actions">
                            <?php if (in_array($row['status'], ['requested', 'reviewing'], true)): ?>
                                <form class="inline-form" method="post" action="<?= e(url('/admin/saques/' . $row['id'])) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="decision" value="approve">
                                    <button class="btn btn-accent" type="submit">Pagar</button>
                                </form>
                                <form class="inline-form" method="post" action="<?= e(url('/admin/saques/' . $row['id'])) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="decision" value="reject">
                                    <button class="btn btn-ghost" type="submit">Recusar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $base = '/admin/saques'; $query = $filters; include BASE_PATH . '/views/admin/partials/pager.php'; ?>
    <?php endif; ?>
</section>
