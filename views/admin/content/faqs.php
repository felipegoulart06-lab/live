<?php
$e = $edit ?? [];
$val = static fn (string $k, string $d = ''): string => (string) old($k, $e[$k] ?? $d);
$audiences = ['all' => 'Todos', 'company' => 'Empresas', 'creator' => 'Criadores'];
?>
<div class="page-title"><div><h1>Perguntas frequentes</h1><p>Aparecem na <a href="<?= e(url('/ajuda')) ?>">central de ajuda</a> e, para empresas, na home.</p></div></div>
<div class="grid-main">
    <div>
        <?php if ($faqs === []): ?>
            <?= view('empty', ['title' => 'Nenhuma pergunta cadastrada']) ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th scope="col">Pergunta</th><th scope="col">Público</th><th scope="col" class="num">Ordem</th><th scope="col">Situação</th><th scope="col"><span class="sr-only">Ações</span></th></tr></thead>
                    <tbody>
                    <?php foreach ($faqs as $f): ?>
                        <tr>
                            <td><strong><?= e($f['question']) ?></strong><small class="clamp"><?= e($f['answer']) ?></small></td>
                            <td><?= e($audiences[$f['audience']] ?? $f['audience']) ?></td>
                            <td class="num"><?= (int) $f['sort_order'] ?></td>
                            <td><?= status_badge('visibility', $f['status']) ?></td>
                            <td>
                                <div class="actions-menu">
                                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/faq?editar=' . (int) $f['id'])) ?>">Editar</a>
                                    <form method="post" action="<?= e(url('/admin/faq/' . (int) $f['id'] . '/excluir')) ?>" class="inline" data-confirm="Excluir esta pergunta?"><?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Excluir</button></form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <form class="card card-pad form" method="post" action="<?= e(url('/admin/faq')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) ($e['id'] ?? 0) ?>">
        <h2 class="panel-title mb-0"><?= $edit ? 'Editar pergunta' : 'Nova pergunta' ?></h2>
        <?= view('field', ['name' => 'question', 'label' => 'Pergunta', 'value' => $val('question'), 'attrs' => 'required maxlength="200"']) ?>
        <?= view('field', ['name' => 'answer', 'label' => 'Resposta', 'type' => 'textarea', 'value' => $val('answer'), 'attrs' => 'required rows="5" maxlength="2000"']) ?>
        <div class="form-grid">
            <?= view('field', ['name' => 'audience', 'label' => 'Público', 'type' => 'select', 'value' => $val('audience', 'all'), 'options' => $audiences]) ?>
            <?= view('field', ['name' => 'status', 'label' => 'Situação', 'type' => 'select', 'value' => $val('status', 'visible'), 'options' => ['visible' => 'Visível', 'hidden' => 'Oculta']]) ?>
            <?= view('field', ['name' => 'sort_order', 'label' => 'Ordem', 'type' => 'number', 'value' => $val('sort_order', '0'), 'attrs' => 'min="0" max="999"']) ?>
        </div>
        <div class="form-actions">
            <button class="btn btn-ink" type="submit">Salvar pergunta</button>
            <?php if ($edit): ?><a class="btn btn-ghost" href="<?= e(url('/admin/faq')) ?>">Cancelar</a><?php endif; ?>
        </div>
    </form>
</div>
