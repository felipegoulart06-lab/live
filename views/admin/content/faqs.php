<?php $e = $editing ?? []; ?>
<section class="dash-page">
    <div class="section-head"><h1>Perguntas frequentes</h1></div>
    <form class="admin-form" method="post" action="<?= e(url('/admin/faqs')) ?>">
        <?= csrf_field() ?>
        <?php if (!empty($e['id'])): ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><?php endif; ?>
        <fieldset>
            <legend><?= !empty($e['id']) ? 'Editar FAQ' : 'Nova FAQ' ?></legend>
            <label>Pergunta<input name="question" required value="<?= e($e['question'] ?? '') ?>"></label>
            <label>Resposta<textarea name="answer" rows="4" required><?= e($e['answer'] ?? '') ?></textarea></label>
            <div class="form-grid">
                <label>Onde
                    <select name="placement">
                        <?php foreach (['both' => 'Home e ajuda', 'home' => 'Home', 'help' => 'Ajuda'] as $k => $label): ?>
                            <option value="<?= e($k) ?>" <?= (($e['placement'] ?? 'both') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Ordem<input type="number" name="sort_order" value="<?= e($e['sort_order'] ?? 0) ?>"></label>
            </div>
            <label class="check"><input type="checkbox" name="is_active" value="1" <?= !isset($e['is_active']) || !empty($e['is_active']) ? 'checked' : '' ?>> Ativa</label>
            <div class="form-actions"><button class="btn btn-accent" type="submit">Salvar</button></div>
        </fieldset>
    </form>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Pergunta</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['question']) ?></td>
                    <td class="td-actions">
                        <a class="btn btn-ghost" href="<?= e(url('/admin/faqs?id=' . $row['id'])) ?>">Editar</a>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/faqs/' . $row['id'] . '/excluir')) ?>">
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
