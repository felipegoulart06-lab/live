<?php $e = $edit ?? []; $val = static fn (string $k, string $d = ''): string => (string) old($k, $e[$k] ?? $d); ?>
<div class="page-title"><div><h1>Categorias</h1><p>Tipos de vídeo usados na busca, no menu e nos anúncios. Categorias ocultas somem do site, mas os anúncios continuam nelas.</p></div></div>

<div class="grid-main">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Categoria</th><th scope="col" class="num">Ordem</th><th scope="col" class="num">Anúncios</th><th scope="col">Situação</th><th scope="col"><span class="sr-only">Ações</span></th></tr></thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td>
                        <div class="person">
                            <?php if ($cat['image_path']): ?><img class="thumb-sm" src="<?= e(media($cat['image_path'])) ?>" alt=""><?php else: ?><span class="thumb-sm" aria-hidden="true"></span><?php endif; ?>
                            <div><strong><?= e($cat['name']) ?></strong><small>/categorias/<?= e($cat['slug']) ?></small></div>
                        </div>
                    </td>
                    <td class="num"><?= (int) $cat['sort_order'] ?></td>
                    <td class="num"><?= (int) $cat['listings_count'] ?></td>
                    <td><?= status_badge('visibility', $cat['status']) ?></td>
                    <td>
                        <div class="actions-menu">
                            <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/categorias?editar=' . (int) $cat['id'])) ?>">Editar</a>
                            <?php if ((int) $cat['listings_count'] === 0): ?>
                                <form method="post" action="<?= e(url('/admin/categorias/' . (int) $cat['id'] . '/excluir')) ?>" class="inline" data-confirm="Excluir a categoria <?= e($cat['name']) ?>?"><?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Excluir</button></form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form class="card card-pad form" method="post" action="<?= e(url('/admin/categorias')) ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) ($e['id'] ?? 0) ?>">
        <h2 class="panel-title mb-0"><?= $edit ? 'Editar categoria' : 'Nova categoria' ?></h2>
        <?= view('field', ['name' => 'name', 'label' => 'Nome', 'value' => $val('name'), 'attrs' => 'required maxlength="60"']) ?>
        <?= view('field', ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea', 'optional' => true, 'value' => $val('description'), 'attrs' => 'rows="3" maxlength="300"']) ?>
        <div class="form-grid">
            <?= view('field', ['name' => 'status', 'label' => 'Situação', 'type' => 'select', 'value' => $val('status', 'visible'), 'options' => ['visible' => 'Visível', 'hidden' => 'Oculta']]) ?>
            <?= view('field', ['name' => 'sort_order', 'label' => 'Ordem', 'type' => 'number', 'value' => $val('sort_order', '0'), 'attrs' => 'min="0" max="999"']) ?>
        </div>
        <label class="field"><span>Imagem <small>(opcional, JPG/PNG/WebP)</small></span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
        <?php if ($edit && $edit['name'] !== ''): ?><p class="muted small mb-0">Mudar o nome altera o endereço da categoria.</p><?php endif; ?>
        <div class="form-actions">
            <button class="btn btn-ink" type="submit">Salvar categoria</button>
            <?php if ($edit): ?><a class="btn btn-ghost" href="<?= e(url('/admin/categorias')) ?>">Cancelar</a><?php endif; ?>
        </div>
    </form>
</div>
