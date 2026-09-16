<?php $e = $editing ?? []; ?>
<section class="dash-page">
    <div class="section-head"><h1>Depoimentos</h1></div>
    <form class="admin-form" method="post" action="<?= e(url('/admin/depoimentos')) ?>">
        <?= csrf_field() ?>
        <?php if (!empty($e['id'])): ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><?php endif; ?>
        <fieldset>
            <legend><?= !empty($e['id']) ? 'Editar depoimento' : 'Novo depoimento' ?></legend>
            <div class="form-grid">
                <label>Nome<input name="author_name" required value="<?= e($e['author_name'] ?? '') ?>"></label>
                <label>Papel<input name="author_role" value="<?= e($e['author_role'] ?? '') ?>"></label>
            </div>
            <label>Texto<textarea name="quote" rows="4" required><?= e($e['quote'] ?? '') ?></textarea></label>
            <div class="form-grid">
                <label>Nota<input type="number" min="1" max="5" name="rating" value="<?= e($e['rating'] ?? 5) ?>"></label>
                <label>Avatar<input name="avatar_path" value="<?= e($e['avatar_path'] ?? '') ?>"></label>
            </div>
            <label>Ordem<input type="number" name="sort_order" value="<?= e($e['sort_order'] ?? 0) ?>"></label>
            <label class="check"><input type="checkbox" name="is_active" value="1" <?= !isset($e['is_active']) || !empty($e['is_active']) ? 'checked' : '' ?>> Ativo</label>
            <div class="form-actions"><button class="btn btn-accent" type="submit">Salvar</button></div>
        </fieldset>
    </form>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Autor</th><th>Nota</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['author_name']) ?></td>
                    <td><?= (int) $row['rating'] ?></td>
                    <td class="td-actions">
                        <a class="btn btn-ghost" href="<?= e(url('/admin/depoimentos?id=' . $row['id'])) ?>">Editar</a>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/depoimentos/' . $row['id'] . '/excluir')) ?>">
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
