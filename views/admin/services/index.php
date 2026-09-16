<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Serviços</h1>
            <p class="muted"><?= (int) $total ?> anúncios</p>
        </div>
    </div>
    <form class="filters" method="get" action="<?= e(url('/admin/servicos')) ?>">
        <label>Busca<input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>"></label>
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                <?php foreach (['draft' => 'Rascunho', 'pending_review' => 'Em análise', 'published' => 'Publicado', 'paused' => 'Pausado', 'rejected' => 'Recusado'] as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= (($filters['status'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-ink" type="submit">Filtrar</button>
    </form>
    <?php if (!$rows): ?>
        <?= \App\Core\View::component('empty', ['message' => 'Nenhum serviço encontrado.']) ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Serviço</th><th>Profissional</th><th>Status</th><th>Preço</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <strong><?= e($row['title']) ?></strong>
                            <small class="muted" style="display:block"><?= e($row['category_name']) ?><?= !empty($row['is_featured']) ? ' · destaque' : '' ?></small>
                        </td>
                        <td><?= e($row['seller_name']) ?></td>
                        <td><span class="status-pill"><?= e($row['status']) ?></span></td>
                        <td><?= e(money((int) $row['starting_price_cents'])) ?></td>
                        <td class="td-actions">
                            <form class="inline-form" method="post" action="<?= e(url('/admin/servicos/' . $row['id'] . '/status')) ?>">
                                <?= csrf_field() ?>
                                <select name="status">
                                    <?php foreach (['pending_review','published','paused','rejected','draft'] as $st): ?>
                                        <option value="<?= e($st) ?>" <?= $row['status'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-ink" type="submit">Aplicar</button>
                            </form>
                            <form class="inline-form" method="post" action="<?= e(url('/admin/servicos/' . $row['id'] . '/destaque')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-ghost" type="submit"><?= !empty($row['is_featured']) ? 'Tirar destaque' : 'Destacar' ?></button>
                            </form>
                            <a class="btn btn-ghost" href="<?= e(url('/servico/' . $row['category_slug'] . '/' . $row['slug'])) ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $base = '/admin/servicos'; $query = $filters; include BASE_PATH . '/views/admin/partials/pager.php'; ?>
    <?php endif; ?>
</section>
