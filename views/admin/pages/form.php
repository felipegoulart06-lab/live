<?php $p = $page ?? []; ?>
<section class="dash-page">
    <div class="section-head">
        <h1><?= $page ? 'Editar página' : 'Nova página' ?></h1>
        <a class="btn btn-ghost" href="<?= e(url('/admin/paginas')) ?>">Voltar</a>
    </div>
    <form class="admin-form" method="post" action="<?= e($page ? url('/admin/paginas/' . $page['id']) : url('/admin/paginas')) ?>">
        <?= csrf_field() ?>
        <fieldset>
            <legend>Conteúdo</legend>
            <div class="form-grid">
                <label>Título<input name="title" required value="<?= e(old('title', $p['title'] ?? '')) ?>"></label>
                <label>Slug<input name="slug" value="<?= e(old('slug', $p['slug'] ?? '')) ?>"></label>
            </div>
            <label>HTML
                <textarea name="content" rows="12"><?= e(old('content', $p['content'] ?? '')) ?></textarea>
            </label>
            <div class="form-grid">
                <label>Status
                    <select name="status">
                        <option value="published" <?= (($p['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>Publicada</option>
                        <option value="draft" <?= (($p['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Rascunho</option>
                    </select>
                </label>
                <label>Ordem<input type="number" name="sort_order" value="<?= e(old('sort_order', $p['sort_order'] ?? 0)) ?>"></label>
            </div>
            <label>Meta título<input name="meta_title" value="<?= e(old('meta_title', $p['meta_title'] ?? '')) ?>"></label>
            <label>Meta descrição<input name="meta_description" value="<?= e(old('meta_description', $p['meta_description'] ?? '')) ?>"></label>
        </fieldset>
        <div class="form-actions"><button class="btn btn-accent" type="submit">Salvar</button></div>
    </form>
</section>
