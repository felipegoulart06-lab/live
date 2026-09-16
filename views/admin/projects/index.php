<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Projetos</h1>
            <p class="muted"><?= (int) $total ?> briefings publicados</p>
        </div>
    </div>
    <form class="filters" method="get" action="<?= e(url('/admin/projetos')) ?>">
        <label>Busca<input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>"></label>
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                <?php foreach (['open','in_review','hired','closed','cancelled'] as $st): ?>
                    <option value="<?= e($st) ?>" <?= (($filters['status'] ?? '') === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-ink" type="submit">Filtrar</button>
    </form>
    <?php if (!$rows): ?>
        <?= \App\Core\View::component('empty', ['message' => 'Nenhum projeto encontrado.']) ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Projeto</th><th>Cliente</th><th>Categoria</th><th>Propostas</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><strong><?= e($row['title']) ?></strong></td>
                        <td><?= e($row['buyer_name']) ?></td>
                        <td><?= e($row['category_name']) ?></td>
                        <td><?= (int) $row['proposals_count'] ?></td>
                        <td><span class="status-pill"><?= e($row['status']) ?></span></td>
                        <td class="td-actions">
                            <form class="inline-form" method="post" action="<?= e(url('/admin/projetos/' . $row['id'] . '/status')) ?>">
                                <?= csrf_field() ?>
                                <select name="status">
                                    <?php foreach (['open','in_review','hired','closed','cancelled'] as $st): ?>
                                        <option value="<?= e($st) ?>" <?= $row['status'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-ink" type="submit">Aplicar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $base = '/admin/projetos'; $query = $filters; include BASE_PATH . '/views/admin/partials/pager.php'; ?>
    <?php endif; ?>
</section>
