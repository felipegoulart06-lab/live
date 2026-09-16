<?php $e = $editing ?? []; ?>
<section class="dash-page">
    <div class="section-head">
        <h1>Banners</h1>
        <a class="btn btn-ghost" href="<?= e(url('/admin/banners')) ?>">Novo</a>
    </div>
    <form class="admin-form" method="post" action="<?= e(url('/admin/banners')) ?>">
        <?= csrf_field() ?>
        <?php if (!empty($e['id'])): ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><?php endif; ?>
        <fieldset>
            <legend><?= !empty($e['id']) ? 'Editar banner' : 'Novo banner' ?></legend>
            <div class="form-grid">
                <label>Posição<input name="placement" value="<?= e($e['placement'] ?? 'home_hero') ?>"></label>
                <label>Título<input name="title" required value="<?= e($e['title'] ?? '') ?>"></label>
            </div>
            <label>Subtítulo<input name="subtitle" value="<?= e($e['subtitle'] ?? '') ?>"></label>
            <div class="form-grid">
                <label>CTA<input name="cta_label" value="<?= e($e['cta_label'] ?? '') ?>"></label>
                <label>URL<input name="cta_url" value="<?= e($e['cta_url'] ?? '') ?>"></label>
            </div>
            <label>Imagem<input name="image_path" value="<?= e($e['image_path'] ?? '') ?>"></label>
            <div class="form-grid">
                <label>Início<input type="datetime-local" name="starts_at" value="<?= e(!empty($e['starts_at']) ? str_replace(' ', 'T', substr((string) $e['starts_at'], 0, 16)) : '') ?>"></label>
                <label>Fim<input type="datetime-local" name="ends_at" value="<?= e(!empty($e['ends_at']) ? str_replace(' ', 'T', substr((string) $e['ends_at'], 0, 16)) : '') ?>"></label>
            </div>
            <label>Ordem<input type="number" name="sort_order" value="<?= e($e['sort_order'] ?? 0) ?>"></label>
            <label class="check"><input type="checkbox" name="is_active" value="1" <?= !isset($e['is_active']) || !empty($e['is_active']) ? 'checked' : '' ?>> Ativo</label>
            <div class="form-actions"><button class="btn btn-accent" type="submit">Salvar banner</button></div>
        </fieldset>
    </form>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Título</th><th>Posição</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['title']) ?></td>
                    <td><?= e($row['placement']) ?></td>
                    <td class="td-actions">
                        <a class="btn btn-ghost" href="<?= e(url('/admin/banners?id=' . $row['id'])) ?>">Editar</a>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/banners/' . $row['id'] . '/excluir')) ?>">
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
