<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Auditoria</h1>
            <p class="muted">Ações do painel e tentativas de login</p>
        </div>
    </div>
    <h2>Ações administrativas</h2>
    <?php if (!$audits): ?>
        <p class="muted">Nenhuma ação registrada ainda.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Quando</th><th>Admin</th><th>Ação</th><th>Objeto</th></tr></thead>
                <tbody>
                <?php foreach ($audits as $row): ?>
                    <tr>
                        <td><?= e($row['created_at']) ?></td>
                        <td><?= e($row['admin_name'] ?? '#' . $row['admin_id']) ?></td>
                        <td><?= e($row['action']) ?></td>
                        <td><?= e($row['object_type']) ?> <?= e((string) ($row['object_id'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $base = '/admin/auditoria'; $query = []; include BASE_PATH . '/views/admin/partials/pager.php'; ?>
    <?php endif; ?>

    <h2>Logins recentes</h2>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Quando</th><th>E-mail</th><th>IP</th><th>Resultado</th></tr></thead>
            <tbody>
            <?php foreach ($logins as $row): ?>
                <tr>
                    <td><?= e($row['created_at']) ?></td>
                    <td><?= e($row['email_attempt']) ?></td>
                    <td><?= e($row['ip_address']) ?></td>
                    <td><?= !empty($row['success']) ? 'ok' : e($row['failure_reason'] ?? 'falha') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
