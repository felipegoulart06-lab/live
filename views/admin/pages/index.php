<section class="dash-page">
    <div class="section-head">
        <div>
            <h1>Páginas</h1>
            <p class="muted">Conteúdo institucional em /p/{slug}</p>
        </div>
        <a class="btn btn-accent" href="<?= e(url('/admin/paginas/nova')) ?>">Nova página</a>
    </div>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Título</th><th>Slug</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['title']) ?></td>
                    <td>/p/<?= e($row['slug']) ?></td>
                    <td><?= e($row['status']) ?></td>
                    <td class="td-actions">
                        <a class="btn btn-ghost" href="<?= e(url('/admin/paginas/' . $row['id'] . '/editar')) ?>">Editar</a>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/paginas/' . $row['id'] . '/excluir')) ?>" onsubmit="return confirm('Excluir esta página?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-ghost" type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
