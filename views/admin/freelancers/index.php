<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Profissionais</h1>
            <p class="muted"><?= (int) $total ?> cadastros com papel de freelancer</p>
        </div>
        <a class="btn btn-accent" href="<?= e(url('/admin/profissionais/novo')) ?>">Adicionar profissional</a>
    </div>

    <form class="filters" method="get" action="<?= e(url('/admin/profissionais')) ?>">
        <label>Busca
            <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Nome, e-mail ou slug">
        </label>
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                <?php foreach (['active' => 'Ativo', 'pending' => 'Pendente', 'suspended' => 'Suspenso', 'banned' => 'Banido'] as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= (($filters['status'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Verificação
            <select name="verificado">
                <option value="">Todos</option>
                <option value="1" <?= (($filters['verificado'] ?? '') === '1') ? 'selected' : '' ?>>Verificados</option>
                <option value="0" <?= (($filters['verificado'] ?? '') === '0') ? 'selected' : '' ?>>Não verificados</option>
            </select>
        </label>
        <button class="btn btn-ink" type="submit">Filtrar</button>
    </form>

    <?php if (!$rows): ?>
        <?= \App\Core\View::component('empty', ['message' => 'Nenhum profissional encontrado.']) ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Profissional</th>
                        <th>E-mail</th>
                        <th>Status</th>
                        <th>Serviços</th>
                        <th>Pedidos</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $statusLabel = ['active' => 'Ativo', 'pending' => 'Pendente', 'suspended' => 'Suspenso', 'banned' => 'Banido'];
                    foreach ($rows as $row):
                    ?>
                        <tr>
                            <td>
                                <div class="person-cell">
                                    <?php if (!empty($row['avatar_path'])): ?>
                                        <img src="<?= e(media($row['avatar_path'])) ?>" alt="" width="36" height="36">
                                    <?php endif; ?>
                                    <span>
                                        <strong><?= e($row['display_name']) ?></strong>
                                        <small><?= e($row['slug']) ?><?= !empty($row['is_verified']) ? ' · verificado' : '' ?></small>
                                    </span>
                                </div>
                            </td>
                            <td><?= e($row['email']) ?></td>
                            <td><?= e($statusLabel[$row['status']] ?? $row['status']) ?></td>
                            <td><?= (int) $row['services_count'] ?></td>
                            <td><?= (int) $row['orders_completed'] ?></td>
                            <td class="td-actions">
                                <a class="btn btn-ghost" href="<?= e(url('/admin/profissionais/' . $row['id'] . '/editar')) ?>">Configurar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
            <nav class="pager">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a class="<?= $i === $page ? 'is-active' : '' ?>" href="<?= e(url('/admin/profissionais?' . http_build_query(array_merge($filters, ['page' => $i])))) ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
