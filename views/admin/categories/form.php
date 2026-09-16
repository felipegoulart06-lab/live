<?php $c = $category ?? []; ?>
<section class="dash-page">
    <div class="section-head">
        <div>
            <h1><?= $category ? 'Editar categoria' : 'Nova categoria' ?></h1>
        </div>
        <a class="btn btn-ghost" href="<?= e(url('/admin/categorias')) ?>">Voltar</a>
    </div>
    <form class="admin-form" method="post" action="<?= e($category ? url('/admin/categorias/' . $category['id']) : url('/admin/categorias')) ?>">
        <?= csrf_field() ?>
        <fieldset>
            <legend>Dados</legend>
            <div class="form-grid">
                <label>Nome<input name="name" required value="<?= e(old('name', $c['name'] ?? '')) ?>"></label>
                <label>Slug<input name="slug" value="<?= e(old('slug', $c['slug'] ?? '')) ?>"></label>
            </div>
            <label>Resumo<textarea name="short_description" rows="2"><?= e(old('short_description', $c['short_description'] ?? '')) ?></textarea></label>
            <label>Descrição<textarea name="description" rows="4"><?= e(old('description', $c['description'] ?? '')) ?></textarea></label>
            <label>Imagem (caminho em assets)<input name="image_path" value="<?= e(old('image_path', $c['image_path'] ?? '')) ?>" placeholder="images/cat-code.jpg"></label>
            <div class="form-grid">
                <label>Ordem<input type="number" name="sort_order" value="<?= e(old('sort_order', $c['sort_order'] ?? 0)) ?>"></label>
                <label>Meta título<input name="meta_title" value="<?= e(old('meta_title', $c['meta_title'] ?? '')) ?>"></label>
            </div>
            <label>Meta descrição<input name="meta_description" value="<?= e(old('meta_description', $c['meta_description'] ?? '')) ?>"></label>
            <div class="checks">
                <label class="check"><input type="checkbox" name="is_active" value="1" <?= !empty($c['is_active']) || !$category ? 'checked' : '' ?>> Ativa</label>
                <label class="check"><input type="checkbox" name="is_featured" value="1" <?= !empty($c['is_featured']) ? 'checked' : '' ?>> Destaque</label>
            </div>
        </fieldset>
        <div class="form-actions"><button class="btn btn-accent" type="submit">Salvar</button></div>
    </form>

    <?php if ($category): ?>
        <h2>Subcategorias</h2>
        <form class="filters" method="post" action="<?= e(url('/admin/categorias/' . $category['id'] . '/subcategorias')) ?>">
            <?= csrf_field() ?>
            <label>Nome<input name="name" required></label>
            <label>Slug<input name="slug"></label>
            <label>Ordem<input type="number" name="sort_order" value="0"></label>
            <label class="check" style="margin-bottom:0.4rem"><input type="checkbox" name="is_active" value="1" checked> Ativa</label>
            <button class="btn btn-ink" type="submit">Adicionar</button>
        </form>
        <?php if ($subs): ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Nome</th><th>Slug</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($subs as $sub): ?>
                        <tr>
                            <td><?= e($sub['name']) ?></td>
                            <td><?= e($sub['slug']) ?></td>
                            <td class="td-actions">
                                <form method="post" action="<?= e(url('/admin/categorias/' . $category['id'] . '/subcategorias/' . $sub['id'] . '/excluir')) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-ghost" type="submit">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
