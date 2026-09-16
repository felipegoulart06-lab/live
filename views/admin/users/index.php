<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Usuários</h1>
            <p class="muted"><?= (int) $total ?> contas na plataforma</p>
        </div>
        <a class="btn btn-accent" href="<?= e(url('/admin/profissionais/novo')) ?>">Novo profissional</a>
    </div>
    <form class="filters" method="get" action="<?= e(url('/admin/usuarios')) ?>">
        <label>Busca
            <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Nome ou e-mail">
        </label>
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                <?php foreach (['active' => 'Ativo', 'pending' => 'Pendente', 'suspended' => 'Suspenso', 'banned' => 'Banido'] as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= (($filters['status'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Papel
            <select name="papel">
                <option value="">Todos</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= e($role['slug']) ?>" <?= (($filters['papel'] ?? '') === $role['slug']) ? 'selected' : '' ?>><?= e($role['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-ink" type="submit">Filtrar</button>
    </form>
    <?php if (!$rows): ?>
        <?= \App\Core\View::component('empty', ['message' => 'Nenhum usuário encontrado.']) ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Conta</th><th>E-mail</th><th>Papéis</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <div class="person-cell">
                                <?php if (!empty($row['avatar_path'])): ?><img src="<?= e(media($row['avatar_path'])) ?>" alt="" width="36" height="36"><?php endif; ?>
                                <span><strong><?= e($row['display_name'] ?: '—') ?></strong><small><?= e($row['slug'] ?? '') ?></small></span>
                            </div>
                        </td>
                        <td><?= e($row['email']) ?></td>
                        <td><?= e($row['roles'] ?? '') ?></td>
                        <td><span class="status-pill"><?= e($row['status']) ?></span></td>
                        <td class="td-actions"><a class="btn btn-ghost" href="<?= e(url('/admin/usuarios/' . $row['id'] . '/editar')) ?>">Abrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $base = '/admin/usuarios'; $query = $filters; include BASE_PATH . '/views/admin/partials/pager.php'; ?>
    <?php endif; ?>
</section>
