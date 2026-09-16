<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Categorias</h1>
            <p class="muted">Estrutura do catálogo e do menu</p>
        </div>
        <a class="btn btn-accent" href="<?= e(url('/admin/categorias/nova')) ?>">Nova categoria</a>
    </div>
    <?php if (!$rows): ?>
        <?= \App\Core\View::component('empty', ['message' => 'Nenhuma categoria.']) ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Nome</th><th>Slug</th><th>Subs</th><th>Serviços</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><strong><?= e($row['name']) ?></strong></td>
                        <td><?= e($row['slug']) ?></td>
                        <td><?= (int) $row['sub_count'] ?></td>
                        <td><?= (int) $row['service_count'] ?></td>
                        <td><?= !empty($row['is_active']) ? 'Ativa' : 'Oculta' ?></td>
                        <td class="td-actions"><a class="btn btn-ghost" href="<?= e(url('/admin/categorias/' . $row['id'] . '/editar')) ?>">Editar</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
